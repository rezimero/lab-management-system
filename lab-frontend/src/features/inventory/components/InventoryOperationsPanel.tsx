import {
  useMemo,
  useState,
} from 'react';

import AddRounded from '@mui/icons-material/AddRounded';
import DeleteOutlineRounded from '@mui/icons-material/DeleteOutlineRounded';
import TuneRounded from '@mui/icons-material/TuneRounded';

import {
  Alert,
  Button,
  Card,
  CardContent,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  Divider,
  Grid,
  IconButton,
  MenuItem,
  Stack,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  TextField,
  Tooltip,
  Typography,
} from '@mui/material';

import {
  useMutation,
  useQuery,
} from '@tanstack/react-query';

import type {
  BranchSummary,
} from '../../commercial/types/commercial.types';

import {
  apiErrorMessage,
} from '../../test-results/utils/apiErrorMessage';

import {
  createInventoryAdjustment,
  createInventoryReceipt,
  fetchInventoryStock,
} from '../api/inventoryApi';

import type {
  InventoryAdjustment,
  InventoryAdjustmentPayload,
  InventoryItem,
  InventoryMovement,
  InventoryReceipt,
  InventoryReceiptPayload,
  InventorySupplier,
} from '../types/inventory.types';

import {
  inventoryDate,
  inventoryQuantity,
  localDateTimeInput,
} from '../utils/inventoryDisplay';

interface Props {
  branches: BranchSummary[];
  suppliers: InventorySupplier[];
  items: InventoryItem[];
  receipts: InventoryReceipt[];
  adjustments: InventoryAdjustment[];
  movements: InventoryMovement[];
  canReceive: boolean;
  canAdjust: boolean;
  onChanged: () => Promise<void>;
}

type ReceiptLine = InventoryReceiptPayload['lines'][number];
type AdjustmentLine = InventoryAdjustmentPayload['lines'][number];

const blankReceiptLine = (): ReceiptLine => ({
  item_uuid: '',
  external_lot_number: '',
  batch_number: '',
  manufactured_at: '',
  expires_at: '',
  quantity: '',
  unit_cost: '',
  currency: 'NGN',
});

const blankAdjustmentLine = (): AdjustmentLine => ({
  lot_uuid: '',
  direction: 'out',
  quantity: '',
});

const initialReceipt = (): InventoryReceiptPayload => ({
  branch_uuid: '',
  supplier_uuid: '',
  supplier_reference: '',
  received_at: localDateTimeInput(),
  notes: '',
  lines: [blankReceiptLine()],
});

const initialAdjustment = (): InventoryAdjustmentPayload => ({
  branch_uuid: '',
  reason: '',
  adjusted_at: localDateTimeInput(),
  notes: '',
  lines: [blankAdjustmentLine()],
});

const InventoryOperationsPanel = ({
  branches,
  suppliers,
  items,
  receipts,
  adjustments,
  movements,
  canReceive,
  canAdjust,
  onChanged,
}: Props) => {
  const [receiptOpen, setReceiptOpen] = useState(false);
  const [adjustmentOpen, setAdjustmentOpen] = useState(false);
  const [receipt, setReceipt] = useState<InventoryReceiptPayload>(initialReceipt);
  const [adjustment, setAdjustment] = useState<InventoryAdjustmentPayload>(initialAdjustment);
  const [error, setError] = useState<string | null>(null);

  const adjustmentStock = useQuery({
    queryKey: ['inventory', 'adjustment-stock', adjustment.branch_uuid],
    queryFn: () => fetchInventoryStock('', adjustment.branch_uuid),
    enabled: adjustmentOpen && Boolean(adjustment.branch_uuid),
  });

  const availableStock = adjustmentStock.data?.data ?? [];

  const itemByUuid = useMemo(
    () => new Map(items.map((item) => [item.uuid, item])),
    [items]
  );

  const receiptReady = Boolean(
    receipt.branch_uuid
    && receipt.received_at
    && receipt.lines.length > 0
    && receipt.lines.every((line) => {
      const item = itemByUuid.get(line.item_uuid);

      return Boolean(
        line.item_uuid
        && line.quantity
        && (!item?.tracks_expiry || line.expires_at)
      );
    })
  );

  const adjustmentReady = Boolean(
    adjustment.branch_uuid
    && adjustment.reason.trim()
    && adjustment.adjusted_at
    && adjustment.lines.length > 0
    && adjustment.lines.every((line) => line.lot_uuid && line.quantity)
  );

  const receiptMutation = useMutation({
    mutationFn: () => createInventoryReceipt(receipt),
    onSuccess: async () => {
      setReceiptOpen(false);
      setReceipt(initialReceipt());
      setError(null);
      await onChanged();
    },
    onError: (requestError: unknown) => {
      setError(apiErrorMessage(requestError, 'Stock receipt could not be posted.'));
    },
  });

  const adjustmentMutation = useMutation({
    mutationFn: () => createInventoryAdjustment(adjustment),
    onSuccess: async () => {
      setAdjustmentOpen(false);
      setAdjustment(initialAdjustment());
      setError(null);
      await onChanged();
    },
    onError: (requestError: unknown) => {
      setError(apiErrorMessage(requestError, 'Stock adjustment could not be posted.'));
    },
  });

  const updateReceiptLine = (
    index: number,
    patch: Partial<ReceiptLine>
  ): void => {
    setReceipt((current) => ({
      ...current,
      lines: current.lines.map((line, lineIndex) => (
        lineIndex === index
          ? { ...line, ...patch }
          : line
      )),
    }));
  };

  const removeReceiptLine = (index: number): void => {
    setReceipt((current) => ({
      ...current,
      lines: current.lines.filter((_, lineIndex) => lineIndex !== index),
    }));
  };

  const updateAdjustmentLine = (
    index: number,
    patch: Partial<AdjustmentLine>
  ): void => {
    setAdjustment((current) => ({
      ...current,
      lines: current.lines.map((line, lineIndex) => (
        lineIndex === index
          ? { ...line, ...patch }
          : line
      )),
    }));
  };

  const removeAdjustmentLine = (index: number): void => {
    setAdjustment((current) => ({
      ...current,
      lines: current.lines.filter((_, lineIndex) => lineIndex !== index),
    }));
  };

  return (
    <Stack spacing={2}>
      {error && <Alert severity="error" onClose={() => setError(null)}>{error}</Alert>}

      <Stack direction={{ xs: 'column', sm: 'row' }} sx={{ justifyContent: "space-between", gap: 1 }}>
        <div>
          <Typography variant="h6" sx={{ fontWeight: 720 }}>Stock operations</Typography>
          <Typography color="text.secondary">
            Post multi-line receipts and controlled adjustments. Posted records cannot be edited.
          </Typography>
        </div>
        <Stack direction="row" sx={{ gap: 1 }}>
          {canReceive && (
            <Button
              variant="contained"
              startIcon={<AddRounded />}
              onClick={() => {
                setError(null);
                setReceiptOpen(true);
              }}
            >
              Receive stock
            </Button>
          )}
          {canAdjust && (
            <Button
              variant="outlined"
              startIcon={<TuneRounded />}
              onClick={() => {
                setError(null);
                setAdjustmentOpen(true);
              }}
            >
              Adjust stock
            </Button>
          )}
        </Stack>
      </Stack>

      <Grid container spacing={2}>
        <Grid size={{ xs: 12, xl: 6 }}>
          <Card variant="outlined">
            <CardContent>
              <Typography variant="h6" gutterBottom sx={{ fontWeight: 700 }}>Recent receipts</Typography>
              <TableContainer>
                <Table size="small">
                  <TableHead>
                    <TableRow>
                      <TableCell>Receipt</TableCell>
                      <TableCell>Branch</TableCell>
                      <TableCell>Supplier</TableCell>
                      <TableCell>Date</TableCell>
                    </TableRow>
                  </TableHead>
                  <TableBody>
                    {receipts.slice(0, 20).map((entry) => (
                      <TableRow key={entry.uuid}>
                        <TableCell>{entry.receipt_number}</TableCell>
                        <TableCell>{entry.branch?.name ?? '—'}</TableCell>
                        <TableCell>{entry.supplier?.name ?? 'Unspecified'}</TableCell>
                        <TableCell>{inventoryDate(entry.received_at)}</TableCell>
                      </TableRow>
                    ))}
                    {receipts.length === 0 && (
                      <TableRow>
                        <TableCell colSpan={4} align="center">No receipts posted.</TableCell>
                      </TableRow>
                    )}
                  </TableBody>
                </Table>
              </TableContainer>
            </CardContent>
          </Card>
        </Grid>
        <Grid size={{ xs: 12, xl: 6 }}>
          <Card variant="outlined">
            <CardContent>
              <Typography variant="h6" gutterBottom sx={{ fontWeight: 700 }}>Recent adjustments</Typography>
              <TableContainer>
                <Table size="small">
                  <TableHead>
                    <TableRow>
                      <TableCell>Adjustment</TableCell>
                      <TableCell>Branch</TableCell>
                      <TableCell>Reason</TableCell>
                      <TableCell>Date</TableCell>
                    </TableRow>
                  </TableHead>
                  <TableBody>
                    {adjustments.slice(0, 20).map((entry) => (
                      <TableRow key={entry.uuid}>
                        <TableCell>{entry.adjustment_number}</TableCell>
                        <TableCell>{entry.branch?.name ?? '—'}</TableCell>
                        <TableCell>{entry.reason}</TableCell>
                        <TableCell>{inventoryDate(entry.adjusted_at)}</TableCell>
                      </TableRow>
                    ))}
                    {adjustments.length === 0 && (
                      <TableRow>
                        <TableCell colSpan={4} align="center">No adjustments posted.</TableCell>
                      </TableRow>
                    )}
                  </TableBody>
                </Table>
              </TableContainer>
            </CardContent>
          </Card>
        </Grid>
      </Grid>

      <Card variant="outlined">
        <CardContent>
          <Typography variant="h6" gutterBottom sx={{ fontWeight: 700 }}>Stock movement ledger</Typography>
          <TableContainer>
            <Table size="small">
              <TableHead>
                <TableRow>
                  <TableCell>Date</TableCell>
                  <TableCell>Type</TableCell>
                  <TableCell>Item</TableCell>
                  <TableCell>Branch</TableCell>
                  <TableCell>Lot</TableCell>
                  <TableCell align="right">Change</TableCell>
                  <TableCell align="right">Balance</TableCell>
                </TableRow>
              </TableHead>
              <TableBody>
                {movements.slice(0, 50).map((entry) => (
                  <TableRow key={entry.uuid} hover>
                    <TableCell>{inventoryDate(entry.occurred_at)}</TableCell>
                    <TableCell>{entry.movement_type.replace('_', ' ')}</TableCell>
                    <TableCell>{entry.item?.name ?? '—'}</TableCell>
                    <TableCell>{entry.branch?.name ?? '—'}</TableCell>
                    <TableCell>{entry.lot?.external_lot_number || entry.lot?.internal_lot_code || '—'}</TableCell>
                    <TableCell align="right">{inventoryQuantity(entry.quantity_delta, entry.item?.unit_symbol)}</TableCell>
                    <TableCell align="right">{inventoryQuantity(entry.quantity_after, entry.item?.unit_symbol)}</TableCell>
                  </TableRow>
                ))}
                {movements.length === 0 && (
                  <TableRow>
                    <TableCell colSpan={7} align="center">No stock movements posted.</TableCell>
                  </TableRow>
                )}
              </TableBody>
            </Table>
          </TableContainer>
        </CardContent>
      </Card>

      <Dialog
        open={receiptOpen}
        onClose={() => setReceiptOpen(false)}
        fullWidth
        maxWidth="lg"
      >
        <DialogTitle>Receive stock</DialogTitle>
        <DialogContent>
          <Stack spacing={2.5} sx={{ mt: 0.5 }}>
            <Grid container spacing={2}>
              <Grid size={{ xs: 12, sm: 6 }}>
                <TextField
                  select
                  fullWidth
                  label="Branch"
                  value={receipt.branch_uuid}
                  onChange={(event) => setReceipt({
                    ...receipt,
                    branch_uuid: event.target.value,
                  })}
                >
                  {branches.filter((branch) => branch.is_active).map((branch) => (
                    <MenuItem key={branch.uuid} value={branch.uuid}>
                      {branch.code} · {branch.name}
                    </MenuItem>
                  ))}
                </TextField>
              </Grid>
              <Grid size={{ xs: 12, sm: 6 }}>
                <TextField
                  select
                  fullWidth
                  label="Supplier"
                  value={receipt.supplier_uuid}
                  onChange={(event) => setReceipt({
                    ...receipt,
                    supplier_uuid: event.target.value,
                  })}
                >
                  <MenuItem value="">Unspecified</MenuItem>
                  {suppliers.filter((supplier) => supplier.is_active).map((supplier) => (
                    <MenuItem key={supplier.uuid} value={supplier.uuid}>
                      {supplier.code} · {supplier.name}
                    </MenuItem>
                  ))}
                </TextField>
              </Grid>
              <Grid size={{ xs: 12, sm: 6 }}>
                <TextField
                  fullWidth
                  type="datetime-local"
                  label="Received at"
                  slotProps={{ inputLabel: { shrink: true } }}
                  value={receipt.received_at}
                  onChange={(event) => setReceipt({
                    ...receipt,
                    received_at: event.target.value,
                  })}
                />
              </Grid>
              <Grid size={{ xs: 12, sm: 6 }}>
                <TextField
                  fullWidth
                  label="Supplier reference"
                  value={receipt.supplier_reference}
                  onChange={(event) => setReceipt({
                    ...receipt,
                    supplier_reference: event.target.value,
                  })}
                />
              </Grid>
            </Grid>

            <Divider />

            <Stack spacing={2}>
              {receipt.lines.map((line, index) => {
                const selectedItem = itemByUuid.get(line.item_uuid);

                return (
                  <Card key={index} variant="outlined">
                    <CardContent>
                      <Stack spacing={2}>
                        <Stack direction="row" sx={{ justifyContent: "space-between", alignItems: "center" }}>
                          <Typography sx={{ fontWeight: 700 }}>Receipt line {index + 1}</Typography>
                          {receipt.lines.length > 1 && (
                            <Tooltip title="Remove receipt line">
                              <IconButton
                                color="error"
                                onClick={() => removeReceiptLine(index)}
                              >
                                <DeleteOutlineRounded />
                              </IconButton>
                            </Tooltip>
                          )}
                        </Stack>
                        <Grid container spacing={2}>
                          <Grid size={{ xs: 12, md: 5 }}>
                            <TextField
                              select
                              fullWidth
                              label="Inventory item"
                              value={line.item_uuid}
                              onChange={(event) => updateReceiptLine(index, {
                                item_uuid: event.target.value,
                              })}
                            >
                              {items.filter((item) => item.is_active).map((item) => (
                                <MenuItem key={item.uuid} value={item.uuid}>
                                  {item.sku} · {item.name}
                                </MenuItem>
                              ))}
                            </TextField>
                          </Grid>
                          <Grid size={{ xs: 6, md: 2 }}>
                            <TextField
                              fullWidth
                              label="Quantity"
                              value={line.quantity}
                              onChange={(event) => updateReceiptLine(index, {
                                quantity: event.target.value,
                              })}
                              helperText={selectedItem?.unit?.symbol ?? ' '}
                            />
                          </Grid>
                          <Grid size={{ xs: 6, md: 2 }}>
                            <TextField
                              fullWidth
                              label="Unit cost"
                              value={line.unit_cost}
                              onChange={(event) => updateReceiptLine(index, {
                                unit_cost: event.target.value,
                              })}
                            />
                          </Grid>
                          <Grid size={{ xs: 12, md: 3 }}>
                            <TextField
                              fullWidth
                              label="Currency"
                              value={line.currency}
                              onChange={(event) => updateReceiptLine(index, {
                                currency: event.target.value.toUpperCase(),
                              })}
                              slotProps={{ htmlInput: { maxLength: 3 } }}
                            />
                          </Grid>
                          <Grid size={{ xs: 12, md: 4 }}>
                            <TextField
                              fullWidth
                              label="External lot number"
                              value={line.external_lot_number}
                              onChange={(event) => updateReceiptLine(index, {
                                external_lot_number: event.target.value,
                              })}
                            />
                          </Grid>
                          <Grid size={{ xs: 12, md: 4 }}>
                            <TextField
                              fullWidth
                              label="Batch number"
                              value={line.batch_number}
                              onChange={(event) => updateReceiptLine(index, {
                                batch_number: event.target.value,
                              })}
                            />
                          </Grid>
                          <Grid size={{ xs: 12, sm: 6, md: 2 }}>
                            <TextField
                              fullWidth
                              type="date"
                              label="Manufactured"
                              slotProps={{ inputLabel: { shrink: true } }}
                              value={line.manufactured_at}
                              onChange={(event) => updateReceiptLine(index, {
                                manufactured_at: event.target.value,
                              })}
                            />
                          </Grid>
                          <Grid size={{ xs: 12, sm: 6, md: 2 }}>
                            <TextField
                              fullWidth
                              required={Boolean(selectedItem?.tracks_expiry)}
                              type="date"
                              label="Expiry date"
                              slotProps={{ inputLabel: { shrink: true } }}
                              value={line.expires_at}
                              onChange={(event) => updateReceiptLine(index, {
                                expires_at: event.target.value,
                              })}
                            />
                          </Grid>
                        </Grid>
                      </Stack>
                    </CardContent>
                  </Card>
                );
              })}

              <Button
                variant="outlined"
                startIcon={<AddRounded />}
                onClick={() => setReceipt((current) => ({
                  ...current,
                  lines: [...current.lines, blankReceiptLine()],
                }))}
              >
                Add receipt line
              </Button>
            </Stack>

            <TextField
              fullWidth
              multiline
              minRows={2}
              label="Notes"
              value={receipt.notes}
              onChange={(event) => setReceipt({
                ...receipt,
                notes: event.target.value,
              })}
            />
          </Stack>
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setReceiptOpen(false)}>Cancel</Button>
          <Button
            variant="contained"
            disabled={receiptMutation.isPending || !receiptReady}
            onClick={() => receiptMutation.mutate()}
          >
            Post receipt
          </Button>
        </DialogActions>
      </Dialog>

      <Dialog
        open={adjustmentOpen}
        onClose={() => setAdjustmentOpen(false)}
        fullWidth
        maxWidth="md"
      >
        <DialogTitle>Adjust stock</DialogTitle>
        <DialogContent>
          <Stack spacing={2.5} sx={{ mt: 1 }}>
            <Grid container spacing={2}>
              <Grid size={{ xs: 12, sm: 6 }}>
                <TextField
                  select
                  fullWidth
                  label="Branch"
                  value={adjustment.branch_uuid}
                  onChange={(event) => setAdjustment({
                    ...adjustment,
                    branch_uuid: event.target.value,
                    lines: [blankAdjustmentLine()],
                  })}
                >
                  {branches.filter((branch) => branch.is_active).map((branch) => (
                    <MenuItem key={branch.uuid} value={branch.uuid}>
                      {branch.code} · {branch.name}
                    </MenuItem>
                  ))}
                </TextField>
              </Grid>
              <Grid size={{ xs: 12, sm: 6 }}>
                <TextField
                  fullWidth
                  type="datetime-local"
                  label="Adjusted at"
                  slotProps={{ inputLabel: { shrink: true } }}
                  value={adjustment.adjusted_at}
                  onChange={(event) => setAdjustment({
                    ...adjustment,
                    adjusted_at: event.target.value,
                  })}
                />
              </Grid>
              <Grid size={12}>
                <TextField
                  fullWidth
                  required
                  label="Reason"
                  value={adjustment.reason}
                  onChange={(event) => setAdjustment({
                    ...adjustment,
                    reason: event.target.value,
                  })}
                />
              </Grid>
            </Grid>

            {adjustmentStock.isError && (
              <Alert severity="error">
                {apiErrorMessage(
                  adjustmentStock.error,
                  'Available stock could not be loaded for this branch.'
                )}
              </Alert>
            )}

            <Divider />

            <Stack spacing={2}>
              {adjustment.lines.map((line, index) => {
                const selectedElsewhere = new Set(
                  adjustment.lines
                    .filter((_, lineIndex) => lineIndex !== index)
                    .map((entry) => entry.lot_uuid)
                    .filter(Boolean)
                );
                const selectedBalance = availableStock.find(
                  (row) => row.lot.uuid === line.lot_uuid
                );

                return (
                  <Card key={index} variant="outlined">
                    <CardContent>
                      <Stack spacing={2}>
                        <Stack direction="row" sx={{ justifyContent: "space-between", alignItems: "center" }}>
                          <Typography sx={{ fontWeight: 700 }}>Adjustment line {index + 1}</Typography>
                          {adjustment.lines.length > 1 && (
                            <Tooltip title="Remove adjustment line">
                              <IconButton
                                color="error"
                                onClick={() => removeAdjustmentLine(index)}
                              >
                                <DeleteOutlineRounded />
                              </IconButton>
                            </Tooltip>
                          )}
                        </Stack>
                        <Grid container spacing={2}>
                          <Grid size={{ xs: 12, md: 6 }}>
                            <TextField
                              select
                              fullWidth
                              label="Lot"
                              value={line.lot_uuid}
                              disabled={!adjustment.branch_uuid || adjustmentStock.isPending}
                              onChange={(event) => updateAdjustmentLine(index, {
                                lot_uuid: event.target.value,
                              })}
                            >
                              {availableStock.map((row) => (
                                <MenuItem
                                  key={row.lot.uuid}
                                  value={row.lot.uuid}
                                  disabled={selectedElsewhere.has(row.lot.uuid)}
                                >
                                  {row.item.name} · {row.lot.external_lot_number || row.lot.internal_lot_code} · {inventoryQuantity(row.quantity_on_hand, row.item.unit?.symbol)}
                                </MenuItem>
                              ))}
                            </TextField>
                          </Grid>
                          <Grid size={{ xs: 12, sm: 6, md: 3 }}>
                            <TextField
                              select
                              fullWidth
                              label="Direction"
                              value={line.direction}
                              onChange={(event) => updateAdjustmentLine(index, {
                                direction: event.target.value as 'in' | 'out',
                              })}
                            >
                              <MenuItem value="in">Increase stock</MenuItem>
                              <MenuItem value="out">Decrease stock</MenuItem>
                            </TextField>
                          </Grid>
                          <Grid size={{ xs: 12, sm: 6, md: 3 }}>
                            <TextField
                              fullWidth
                              label="Quantity"
                              value={line.quantity}
                              onChange={(event) => updateAdjustmentLine(index, {
                                quantity: event.target.value,
                              })}
                              helperText={selectedBalance?.item.unit?.symbol ?? ' '}
                            />
                          </Grid>
                        </Grid>
                      </Stack>
                    </CardContent>
                  </Card>
                );
              })}

              <Button
                variant="outlined"
                startIcon={<AddRounded />}
                disabled={!adjustment.branch_uuid || adjustment.lines.length >= availableStock.length}
                onClick={() => setAdjustment((current) => ({
                  ...current,
                  lines: [...current.lines, blankAdjustmentLine()],
                }))}
              >
                Add adjustment line
              </Button>
            </Stack>

            <TextField
              multiline
              minRows={2}
              label="Notes"
              value={adjustment.notes}
              onChange={(event) => setAdjustment({
                ...adjustment,
                notes: event.target.value,
              })}
            />
          </Stack>
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setAdjustmentOpen(false)}>Cancel</Button>
          <Button
            variant="contained"
            disabled={adjustmentMutation.isPending || !adjustmentReady}
            onClick={() => adjustmentMutation.mutate()}
          >
            Post adjustment
          </Button>
        </DialogActions>
      </Dialog>
    </Stack>
  );
};

export default InventoryOperationsPanel;
