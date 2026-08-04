import {
  useMemo,
  useState,
} from 'react';

import AddRounded from '@mui/icons-material/AddRounded';

import {
  Alert,
  Button,
  Card,
  CardContent,
  Checkbox,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  Divider,
  FormControlLabel,
  Grid,
  MenuItem,
  Stack,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  TextField,
  Typography,
} from '@mui/material';

import {
  useMutation,
} from '@tanstack/react-query';

import type {
  BranchSummary,
} from '../../commercial/types/commercial.types';

import {
  saveInventoryCategory,
  saveInventoryItem,
  saveInventorySupplier,
  saveInventoryUnit,
} from '../api/inventoryApi';

import type {
  InventoryCategory,
  InventoryItem,
  InventoryItemPayload,
  InventorySupplier,
  InventoryUnit,
} from '../types/inventory.types';

import {
  apiErrorMessage,
} from '../../test-results/utils/apiErrorMessage';

interface Props {
  categories: InventoryCategory[];
  units: InventoryUnit[];
  suppliers: InventorySupplier[];
  items: InventoryItem[];
  branches: BranchSummary[];
  canManage: boolean;
  onChanged: () => Promise<void>;
}

type LookupKind = 'category' | 'unit' | 'supplier';
type LookupRecord = InventoryCategory | InventoryUnit | InventorySupplier;

const initialItem = (): InventoryItemPayload => ({
  sku: '',
  name: '',
  item_type: 'reagent',
  category_uuid: '',
  unit_uuid: '',
  manufacturer: '',
  catalogue_number: '',
  description: '',
  tracks_lots: true,
  tracks_expiry: true,
  default_reorder_level: '0',
  is_active: true,
  branches: [],
});

const InventoryCatalogPanel = ({
  categories,
  units,
  suppliers,
  items,
  branches,
  canManage,
  onChanged,
}: Props) => {
  const [itemOpen, setItemOpen] = useState(false);
  const [itemPayload, setItemPayload] = useState<InventoryItemPayload>(initialItem);
  const [editingItem, setEditingItem] = useState<InventoryItem | null>(null);

  const [lookupKind, setLookupKind] = useState<LookupKind | null>(null);
  const [editingLookupUuid, setEditingLookupUuid] = useState<string | undefined>();
  const [lookupCode, setLookupCode] = useState('');
  const [lookupName, setLookupName] = useState('');
  const [lookupExtra, setLookupExtra] = useState('');
  const [lookupDecimalPlaces, setLookupDecimalPlaces] = useState(6);
  const [lookupEmail, setLookupEmail] = useState('');
  const [lookupPhone, setLookupPhone] = useState('');
  const [lookupAddress, setLookupAddress] = useState('');
  const [lookupTaxIdentifier, setLookupTaxIdentifier] = useState('');
  const [lookupActive, setLookupActive] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const activeBranches = useMemo(
    () => branches.filter((branch) => branch.is_active),
    [branches]
  );

  const itemMutation = useMutation({
    mutationFn: () => saveInventoryItem(
      itemPayload,
      editingItem?.uuid
    ),
    onSuccess: async () => {
      setItemOpen(false);
      setEditingItem(null);
      setItemPayload(initialItem());
      setError(null);
      await onChanged();
    },
    onError: (requestError: unknown) => {
      setError(apiErrorMessage(requestError, 'Inventory item could not be saved.'));
    },
  });

  const lookupMutation = useMutation({
    mutationFn: async () => {
      if (lookupKind === 'category') {
        return saveInventoryCategory(
          {
            code: lookupCode,
            name: lookupName,
            description: lookupExtra || undefined,
            is_active: lookupActive,
          },
          editingLookupUuid
        );
      }

      if (lookupKind === 'unit') {
        return saveInventoryUnit(
          {
            code: lookupCode,
            name: lookupName,
            symbol: lookupExtra,
            decimal_places: lookupDecimalPlaces,
            is_active: lookupActive,
          },
          editingLookupUuid
        );
      }

      return saveInventorySupplier(
        {
          code: lookupCode,
          name: lookupName,
          contact_name: lookupExtra || undefined,
          email: lookupEmail || undefined,
          phone: lookupPhone || undefined,
          address: lookupAddress || undefined,
          tax_identifier: lookupTaxIdentifier || undefined,
          is_active: lookupActive,
        },
        editingLookupUuid
      );
    },
    onSuccess: async () => {
      closeLookup();
      setError(null);
      await onChanged();
    },
    onError: (requestError: unknown) => {
      setError(apiErrorMessage(requestError, 'Master data could not be saved.'));
    },
  });

  const closeLookup = (): void => {
    setLookupKind(null);
    setEditingLookupUuid(undefined);
    setLookupCode('');
    setLookupName('');
    setLookupExtra('');
    setLookupDecimalPlaces(6);
    setLookupEmail('');
    setLookupPhone('');
    setLookupAddress('');
    setLookupTaxIdentifier('');
    setLookupActive(true);
  };

  const startLookup = (
    kind: LookupKind,
    record?: LookupRecord
  ): void => {
    setError(null);
    setLookupKind(kind);
    setEditingLookupUuid(record?.uuid);
    setLookupCode(record?.code ?? '');
    setLookupName(record?.name ?? '');
    setLookupExtra('');
    setLookupDecimalPlaces(6);
    setLookupEmail('');
    setLookupPhone('');
    setLookupAddress('');
    setLookupTaxIdentifier('');
    setLookupActive(record?.is_active ?? true);

    if (kind === 'category') {
      setLookupExtra(record && 'description' in record
        ? record.description ?? ''
        : '');
    } else if (kind === 'unit') {
      setLookupExtra(record && 'symbol' in record
        ? record.symbol
        : '');
      setLookupDecimalPlaces(record && 'decimal_places' in record
        ? record.decimal_places
        : 6);
    } else {
      setLookupExtra(record && 'contact_name' in record
        ? record.contact_name ?? ''
        : '');
      setLookupEmail(record && 'email' in record
        ? record.email ?? ''
        : '');
      setLookupPhone(record && 'phone' in record
        ? record.phone ?? ''
        : '');
      setLookupAddress(record && 'address' in record
        ? record.address ?? ''
        : '');
      setLookupTaxIdentifier(record && 'tax_identifier' in record
        ? record.tax_identifier ?? ''
        : '');
    }
  };

  const startItem = (item?: InventoryItem): void => {
    setError(null);
    setEditingItem(item ?? null);
    setItemPayload(item ? {
      sku: item.sku,
      name: item.name,
      item_type: item.item_type,
      category_uuid: item.category?.uuid ?? '',
      unit_uuid: item.unit?.uuid ?? '',
      manufacturer: item.manufacturer ?? '',
      catalogue_number: item.catalogue_number ?? '',
      description: item.description ?? '',
      tracks_lots: item.tracks_lots,
      tracks_expiry: item.tracks_expiry,
      default_reorder_level: item.default_reorder_level,
      is_active: item.is_active,
      branches: (item.branches ?? []).map((setting) => ({
        branch_uuid: setting.branch.uuid,
        reorder_level: setting.reorder_level ?? '',
        is_active: setting.is_active,
      })),
    } : initialItem());
    setItemOpen(true);
  };

  const toggleBranch = (branchUuid: string): void => {
    setItemPayload((current) => {
      const exists = current.branches.some(
        (branch) => branch.branch_uuid === branchUuid
      );

      return {
        ...current,
        branches: exists
          ? current.branches.filter((branch) => branch.branch_uuid !== branchUuid)
          : [
              ...current.branches,
              {
                branch_uuid: branchUuid,
                reorder_level: current.default_reorder_level,
                is_active: true,
              },
            ],
      };
    });
  };

  const updateBranchReorderLevel = (
    branchUuid: string,
    reorderLevel: string
  ): void => {
    setItemPayload((current) => ({
      ...current,
      branches: current.branches.map((branch) => (
        branch.branch_uuid === branchUuid
          ? { ...branch, reorder_level: reorderLevel }
          : branch
      )),
    }));
  };

  const lookupReady = Boolean(
    lookupKind
    && lookupCode.trim()
    && lookupName.trim()
    && (lookupKind !== 'unit' || lookupExtra.trim())
  );

  return (
    <Stack spacing={2}>
      {error && <Alert severity="error" onClose={() => setError(null)}>{error}</Alert>}

      <Stack
        direction={{ xs: 'column', md: 'row' }}
       sx={{ justifyContent: "space-between", gap: 1 }}>
        <div>
          <Typography variant="h6" sx={{ fontWeight: 720 }}>Inventory catalogue</Typography>
          <Typography color="text.secondary">
            Define reagents, consumables, units, suppliers and branch reorder thresholds.
          </Typography>
        </div>
        {canManage && (
          <Stack direction="row" sx={{ gap: 1, flexWrap: "wrap" }}>
            <Button startIcon={<AddRounded />} variant="outlined" onClick={() => startLookup('category')}>
              Category
            </Button>
            <Button startIcon={<AddRounded />} variant="outlined" onClick={() => startLookup('unit')}>
              Unit
            </Button>
            <Button startIcon={<AddRounded />} variant="outlined" onClick={() => startLookup('supplier')}>
              Supplier
            </Button>
            <Button startIcon={<AddRounded />} variant="contained" onClick={() => startItem()}>
              Inventory item
            </Button>
          </Stack>
        )}
      </Stack>

      <Grid container spacing={2}>
        <Grid size={{ xs: 12, xl: 8 }}>
          <TableContainer component={Card} variant="outlined">
            <Table size="small">
              <TableHead>
                <TableRow>
                  <TableCell>Item</TableCell>
                  <TableCell>Type</TableCell>
                  <TableCell>Category</TableCell>
                  <TableCell>Unit</TableCell>
                  <TableCell>Branches</TableCell>
                  <TableCell>Status</TableCell>
                  {canManage && <TableCell />}
                </TableRow>
              </TableHead>
              <TableBody>
                {items.map((item) => (
                  <TableRow key={item.uuid} hover>
                    <TableCell>
                      <Typography sx={{ fontWeight: 680 }}>{item.name}</Typography>
                      <Typography variant="caption" color="text.secondary">{item.sku}</Typography>
                    </TableCell>
                    <TableCell>{item.item_type}</TableCell>
                    <TableCell>{item.category?.name ?? '—'}</TableCell>
                    <TableCell>{item.unit?.symbol ?? '—'}</TableCell>
                    <TableCell>{item.branches?.filter((entry) => entry.is_active).length ?? 0}</TableCell>
                    <TableCell>{item.is_active ? 'Active' : 'Inactive'}</TableCell>
                    {canManage && (
                      <TableCell align="right">
                        <Button size="small" onClick={() => startItem(item)}>Edit</Button>
                      </TableCell>
                    )}
                  </TableRow>
                ))}
                {items.length === 0 && (
                  <TableRow>
                    <TableCell colSpan={canManage ? 7 : 6} align="center" sx={{ py: 4 }}>
                      No inventory items have been configured.
                    </TableCell>
                  </TableRow>
                )}
              </TableBody>
            </Table>
          </TableContainer>
        </Grid>

        <Grid size={{ xs: 12, xl: 4 }}>
          <Stack spacing={2}>
            <Card variant="outlined">
              <CardContent>
                <Typography gutterBottom sx={{ fontWeight: 700 }}>Categories and units</Typography>
                <Stack divider={<Divider flexItem />}>
                  {categories.map((category) => (
                    <Stack
                      key={category.uuid}
                      direction="row" sx={{ py: 1, justifyContent: "space-between", alignItems: "center", gap: 1 }}
                    >
                      <div>
                        <Typography sx={{ fontWeight: 650 }}>{category.name}</Typography>
                        <Typography variant="caption" color="text.secondary">
                          {category.code} · category
                        </Typography>
                      </div>
                      {canManage && <Button size="small" onClick={() => startLookup('category', category)}>Edit</Button>}
                    </Stack>
                  ))}
                  {units.map((unit) => (
                    <Stack
                      key={unit.uuid}
                      direction="row" sx={{ py: 1, justifyContent: "space-between", alignItems: "center", gap: 1 }}
                    >
                      <div>
                        <Typography sx={{ fontWeight: 650 }}>{unit.name} ({unit.symbol})</Typography>
                        <Typography variant="caption" color="text.secondary">
                          {unit.code} · {unit.decimal_places} decimal places
                        </Typography>
                      </div>
                      {canManage && <Button size="small" onClick={() => startLookup('unit', unit)}>Edit</Button>}
                    </Stack>
                  ))}
                  {categories.length + units.length === 0 && (
                    <Typography color="text.secondary" sx={{ py: 1 }}>
                      No categories or units configured.
                    </Typography>
                  )}
                </Stack>
              </CardContent>
            </Card>

            <Card variant="outlined">
              <CardContent>
                <Typography gutterBottom sx={{ fontWeight: 700 }}>Suppliers</Typography>
                <Stack divider={<Divider flexItem />}>
                  {suppliers.map((supplier) => (
                    <Stack
                      key={supplier.uuid}
                      direction="row" sx={{ py: 1, justifyContent: "space-between", alignItems: "center", gap: 1 }}
                    >
                      <div>
                        <Typography sx={{ fontWeight: 650 }}>{supplier.name}</Typography>
                        <Typography variant="caption" color="text.secondary">
                          {supplier.code}
                          {supplier.contact_name ? ` · ${supplier.contact_name}` : ''}
                        </Typography>
                      </div>
                      {canManage && <Button size="small" onClick={() => startLookup('supplier', supplier)}>Edit</Button>}
                    </Stack>
                  ))}
                  {suppliers.length === 0 && (
                    <Typography color="text.secondary" sx={{ py: 1 }}>
                      No suppliers configured.
                    </Typography>
                  )}
                </Stack>
              </CardContent>
            </Card>

            <Card variant="outlined">
              <CardContent>
                <Typography sx={{ fontWeight: 700 }}>Stock policy</Typography>
                <Typography variant="body2" color="text.secondary" sx={{ mt: 1 }}>
                  Posted movements are append-only. Correct stock through controlled adjustments rather than editing history.
                </Typography>
              </CardContent>
            </Card>
          </Stack>
        </Grid>
      </Grid>

      <Dialog open={itemOpen} onClose={() => setItemOpen(false)} fullWidth maxWidth="md">
        <DialogTitle>{editingItem ? 'Edit inventory item' : 'Create inventory item'}</DialogTitle>
        <DialogContent>
          <Grid container spacing={2} sx={{ mt: 0.5 }}>
            <Grid size={{ xs: 12, sm: 4 }}>
              <TextField
                fullWidth
                label="SKU"
                value={itemPayload.sku}
                onChange={(event) => setItemPayload({ ...itemPayload, sku: event.target.value })}
              />
            </Grid>
            <Grid size={{ xs: 12, sm: 8 }}>
              <TextField
                fullWidth
                label="Name"
                value={itemPayload.name}
                onChange={(event) => setItemPayload({ ...itemPayload, name: event.target.value })}
              />
            </Grid>
            <Grid size={{ xs: 12, sm: 4 }}>
              <TextField
                select
                fullWidth
                label="Type"
                value={itemPayload.item_type}
                onChange={(event) => setItemPayload({
                  ...itemPayload,
                  item_type: event.target.value as InventoryItemPayload['item_type'],
                })}
              >
                <MenuItem value="reagent">Reagent</MenuItem>
                <MenuItem value="consumable">Consumable</MenuItem>
                <MenuItem value="general">General supply</MenuItem>
              </TextField>
            </Grid>
            <Grid size={{ xs: 12, sm: 4 }}>
              <TextField
                select
                fullWidth
                label="Category"
                value={itemPayload.category_uuid}
                onChange={(event) => setItemPayload({
                  ...itemPayload,
                  category_uuid: event.target.value,
                })}
              >
                {categories.filter((entry) => entry.is_active).map((entry) => (
                  <MenuItem key={entry.uuid} value={entry.uuid}>{entry.name}</MenuItem>
                ))}
              </TextField>
            </Grid>
            <Grid size={{ xs: 12, sm: 4 }}>
              <TextField
                select
                fullWidth
                label="Unit"
                value={itemPayload.unit_uuid}
                onChange={(event) => setItemPayload({
                  ...itemPayload,
                  unit_uuid: event.target.value,
                })}
              >
                {units.filter((entry) => entry.is_active).map((entry) => (
                  <MenuItem key={entry.uuid} value={entry.uuid}>
                    {entry.name} ({entry.symbol})
                  </MenuItem>
                ))}
              </TextField>
            </Grid>
            <Grid size={{ xs: 12, sm: 6 }}>
              <TextField
                fullWidth
                label="Manufacturer"
                value={itemPayload.manufacturer}
                onChange={(event) => setItemPayload({
                  ...itemPayload,
                  manufacturer: event.target.value,
                })}
              />
            </Grid>
            <Grid size={{ xs: 12, sm: 6 }}>
              <TextField
                fullWidth
                label="Catalogue number"
                value={itemPayload.catalogue_number}
                onChange={(event) => setItemPayload({
                  ...itemPayload,
                  catalogue_number: event.target.value,
                })}
              />
            </Grid>
            <Grid size={{ xs: 12, sm: 4 }}>
              <TextField
                fullWidth
                label="Default reorder level"
                value={itemPayload.default_reorder_level}
                onChange={(event) => setItemPayload({
                  ...itemPayload,
                  default_reorder_level: event.target.value,
                })}
              />
            </Grid>
            <Grid size={{ xs: 12, sm: 8 }}>
              <Stack direction="row" sx={{ flexWrap: "wrap" }}>
                <FormControlLabel
                  control={(
                    <Checkbox
                      checked={itemPayload.tracks_lots}
                      onChange={(event) => setItemPayload({
                        ...itemPayload,
                        tracks_lots: event.target.checked,
                      })}
                    />
                  )}
                  label="Track lots"
                />
                <FormControlLabel
                  control={(
                    <Checkbox
                      checked={itemPayload.tracks_expiry}
                      onChange={(event) => setItemPayload({
                        ...itemPayload,
                        tracks_expiry: event.target.checked,
                      })}
                    />
                  )}
                  label="Track expiry"
                />
                <FormControlLabel
                  control={(
                    <Checkbox
                      checked={itemPayload.is_active}
                      onChange={(event) => setItemPayload({
                        ...itemPayload,
                        is_active: event.target.checked,
                      })}
                    />
                  )}
                  label="Active"
                />
              </Stack>
            </Grid>
            <Grid size={12}>
              <TextField
                fullWidth
                multiline
                minRows={2}
                label="Description"
                value={itemPayload.description}
                onChange={(event) => setItemPayload({
                  ...itemPayload,
                  description: event.target.value,
                })}
              />
            </Grid>
            <Grid size={12}>
              <Typography gutterBottom sx={{ fontWeight: 680 }}>Branch stocking and reorder levels</Typography>
              <Stack spacing={1}>
                {activeBranches.map((branch) => {
                  const setting = itemPayload.branches.find(
                    (entry) => entry.branch_uuid === branch.uuid
                  );

                  return (
                    <Stack
                      key={branch.uuid}
                      direction={{ xs: 'column', sm: 'row' }}
                     sx={{ alignItems: { sm: 'center' }, gap: 1.5 }}>
                      <FormControlLabel
                        sx={{ flex: 1, m: 0 }}
                        control={(
                          <Checkbox
                            checked={Boolean(setting)}
                            onChange={() => toggleBranch(branch.uuid)}
                          />
                        )}
                        label={`${branch.code} · ${branch.name}`}
                      />
                      <TextField
                        size="small"
                        label="Reorder level"
                        value={setting?.reorder_level ?? ''}
                        disabled={!setting}
                        onChange={(event) => updateBranchReorderLevel(
                          branch.uuid,
                          event.target.value
                        )}
                        sx={{ width: { sm: 210 } }}
                      />
                    </Stack>
                  );
                })}
                {activeBranches.length === 0 && (
                  <Alert severity="warning">
                    Create an active laboratory branch before assigning inventory items.
                  </Alert>
                )}
              </Stack>
            </Grid>
          </Grid>
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setItemOpen(false)}>Cancel</Button>
          <Button
            variant="contained"
            disabled={
              itemMutation.isPending
              || !itemPayload.sku.trim()
              || !itemPayload.name.trim()
              || !itemPayload.category_uuid
              || !itemPayload.unit_uuid
            }
            onClick={() => itemMutation.mutate()}
          >
            Save item
          </Button>
        </DialogActions>
      </Dialog>

      <Dialog open={lookupKind !== null} onClose={closeLookup} fullWidth maxWidth="sm">
        <DialogTitle>
          {editingLookupUuid ? 'Edit' : 'Create'} {lookupKind}
        </DialogTitle>
        <DialogContent>
          <Stack spacing={2} sx={{ mt: 1 }}>
            <TextField
              label="Code"
              value={lookupCode}
              onChange={(event) => setLookupCode(event.target.value)}
            />
            <TextField
              label="Name"
              value={lookupName}
              onChange={(event) => setLookupName(event.target.value)}
            />
            <TextField
              label={lookupKind === 'unit'
                ? 'Symbol'
                : lookupKind === 'supplier'
                  ? 'Contact name'
                  : 'Description'}
              value={lookupExtra}
              onChange={(event) => setLookupExtra(event.target.value)}
              multiline={lookupKind === 'category'}
              minRows={lookupKind === 'category' ? 2 : undefined}
            />
            {lookupKind === 'unit' && (
              <TextField
                type="number"
                label="Decimal places"
                value={lookupDecimalPlaces}
                onChange={(event) => setLookupDecimalPlaces(Number(event.target.value))}
                slotProps={{ htmlInput: { min: 0, max: 6 } }}
              />
            )}
            {lookupKind === 'supplier' && (
              <>
                <TextField
                  type="email"
                  label="Email"
                  value={lookupEmail}
                  onChange={(event) => setLookupEmail(event.target.value)}
                />
                <TextField
                  label="Phone"
                  value={lookupPhone}
                  onChange={(event) => setLookupPhone(event.target.value)}
                />
                <TextField
                  multiline
                  minRows={2}
                  label="Address"
                  value={lookupAddress}
                  onChange={(event) => setLookupAddress(event.target.value)}
                />
                <TextField
                  label="Tax identifier"
                  value={lookupTaxIdentifier}
                  onChange={(event) => setLookupTaxIdentifier(event.target.value)}
                />
              </>
            )}
            <FormControlLabel
              control={(
                <Checkbox
                  checked={lookupActive}
                  onChange={(event) => setLookupActive(event.target.checked)}
                />
              )}
              label="Active"
            />
          </Stack>
        </DialogContent>
        <DialogActions>
          <Button onClick={closeLookup}>Cancel</Button>
          <Button
            variant="contained"
            disabled={lookupMutation.isPending || !lookupReady}
            onClick={() => lookupMutation.mutate()}
          >
            Save
          </Button>
        </DialogActions>
      </Dialog>
    </Stack>
  );
};

export default InventoryCatalogPanel;
