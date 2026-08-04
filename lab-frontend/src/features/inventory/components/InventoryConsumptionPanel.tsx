import {
  useEffect,
  useMemo,
  useState,
} from 'react';

import AddRounded from '@mui/icons-material/AddRounded';
import DeleteOutlineRounded from '@mui/icons-material/DeleteOutlineRounded';
import ExpandMoreRounded from '@mui/icons-material/ExpandMoreRounded';
import ScienceRounded from '@mui/icons-material/ScienceRounded';

import {
  Accordion,
  AccordionDetails,
  AccordionSummary,
  Alert,
  Button,
  Card,
  CardContent,
  Chip,
  Divider,
  Grid,
  IconButton,
  MenuItem,
  Stack,
  TextField,
  Tooltip,
  Typography,
} from '@mui/material';

import {
  useMutation,
  useQuery,
  useQueryClient,
} from '@tanstack/react-query';

import type {
  LaboratoryOrderItemResource,
} from '../../laboratory-orders/types/laboratoryOrder.types';

import {
  apiErrorMessage,
} from '../../test-results/utils/apiErrorMessage';

import {
  createOrderItemInventoryConsumption,
  fetchOrderItemInventoryContext,
} from '../api/inventoryApi';

import type {
  InventoryConsumptionPayload,
} from '../types/inventory.types';

import {
  inventoryDate,
  inventoryQuantity,
  localDateTimeInput,
} from '../utils/inventoryDisplay';

interface Props {
  items: LaboratoryOrderItemResource[];
  canRecord: boolean;
}

type ConsumptionLine = InventoryConsumptionPayload['lines'][number];

const blankConsumptionLine = (): ConsumptionLine => ({
  lot_uuid: '',
  quantity: '',
});

const InventoryConsumptionPanel = ({
  items,
  canRecord,
}: Props) => {
  const queryClient = useQueryClient();
  const [selectedItemUuid, setSelectedItemUuid] = useState(items[0]?.uuid ?? '');
  const [lines, setLines] = useState<ConsumptionLine[]>([blankConsumptionLine()]);
  const [consumedAt, setConsumedAt] = useState(localDateTimeInput());
  const [notes, setNotes] = useState('');
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!items.some((item) => item.uuid === selectedItemUuid)) {
      setSelectedItemUuid(items[0]?.uuid ?? '');
      setLines([blankConsumptionLine()]);
    }
  }, [items, selectedItemUuid]);

  const context = useQuery({
    queryKey: ['inventory', 'order-item-consumption', selectedItemUuid],
    queryFn: () => fetchOrderItemInventoryContext(selectedItemUuid),
    enabled: Boolean(selectedItemUuid),
    retry: false,
  });

  const suggestedLines = useMemo(() => {
    if (!context.data) return [];

    const usedLots = new Set<string>();

    return context.data.suggestions.flatMap((suggestion) => {
      const balance = context.data?.available_lots.find((row) => (
        row.item.uuid === suggestion.item.uuid
        && !usedLots.has(row.lot.uuid)
      ));

      if (!balance) return [];

      usedLots.add(balance.lot.uuid);

      return [{
        lot_uuid: balance.lot.uuid,
        quantity: suggestion.quantity,
      }];
    });
  }, [context.data]);

  const ready = Boolean(
    selectedItemUuid
    && consumedAt
    && lines.length > 0
    && lines.every((line) => line.lot_uuid && line.quantity)
  );

  const mutation = useMutation({
    mutationFn: () => createOrderItemInventoryConsumption(
      selectedItemUuid,
      {
        consumed_at: consumedAt,
        notes: notes || undefined,
        lines,
      }
    ),
    onSuccess: async () => {
      setLines([blankConsumptionLine()]);
      setNotes('');
      setConsumedAt(localDateTimeInput());
      setError(null);
      await queryClient.invalidateQueries({
        queryKey: ['inventory'],
      });
    },
    onError: (requestError: unknown) => {
      setError(apiErrorMessage(requestError, 'Inventory consumption could not be posted.'));
    },
  });

  const updateLine = (
    index: number,
    patch: Partial<ConsumptionLine>
  ): void => {
    setLines((current) => current.map((line, lineIndex) => (
      lineIndex === index
        ? { ...line, ...patch }
        : line
    )));
  };

  if (items.length === 0) return null;

  return (
    <Card variant="outlined" sx={{ mt: 3 }}>
      <CardContent>
        <Stack spacing={2}>
          <Stack direction="row" spacing={1.25} sx={{ alignItems: "center" }}>
            <ScienceRounded color="primary" />
            <div>
              <Typography variant="h6" sx={{ fontWeight: 720 }}>
                Reagent and consumable usage
              </Typography>
              <Typography variant="body2" color="text.secondary">
                Post actual lot usage explicitly. Result entry and report approval never deduct stock automatically.
              </Typography>
            </div>
          </Stack>

          {error && <Alert severity="error" onClose={() => setError(null)}>{error}</Alert>}
          {context.isError && (
            <Alert severity="error">
              {apiErrorMessage(context.error, 'Consumption context could not be loaded.')}
            </Alert>
          )}

          <TextField
            select
            fullWidth
            label="Ordered test"
            value={selectedItemUuid}
            onChange={(event) => {
              setSelectedItemUuid(event.target.value);
              setLines([blankConsumptionLine()]);
              setError(null);
            }}
          >
            {items.map((item) => (
              <MenuItem key={item.uuid} value={item.uuid}>
                {item.test_name} · {item.status}
              </MenuItem>
            ))}
          </TextField>

          {context.data && (
            <>
              <Stack direction="row" sx={{ gap: 1, flexWrap: "wrap" }}>
                <Chip label={`Processing branch: ${context.data.branch.name}`} variant="outlined" />
                {context.data.suggestions.map((suggestion) => (
                  <Chip
                    key={suggestion.item.uuid}
                    label={`Suggested: ${suggestion.item.name} · ${inventoryQuantity(suggestion.quantity, suggestion.item.unit?.symbol)}`}
                    color="info"
                    variant="outlined"
                  />
                ))}
              </Stack>

              {canRecord && context.data.order_item.status === 'cancelled' && (
                <Alert severity="warning">
                  Inventory usage cannot be posted to a cancelled laboratory order item.
                </Alert>
              )}

              {canRecord && context.data.order_item.status !== 'cancelled' && (
                <Stack spacing={2}>
                  <Stack
                    direction={{ xs: 'column', sm: 'row' }}
                   sx={{ justifyContent: "space-between", alignItems: { sm: 'center' }, gap: 1 }}>
                    <Typography sx={{ fontWeight: 700 }}>Actual lots and quantities</Typography>
                    {suggestedLines.length > 0 && (
                      <Button
                        size="small"
                        variant="outlined"
                        onClick={() => setLines(suggestedLines)}
                      >
                        Use suggested quantities
                      </Button>
                    )}
                  </Stack>

                  {lines.map((line, index) => {
                    const selectedElsewhere = new Set(
                      lines
                        .filter((_, lineIndex) => lineIndex !== index)
                        .map((entry) => entry.lot_uuid)
                        .filter(Boolean)
                    );
                    const selectedBalance = context.data?.available_lots.find(
                      (row) => row.lot.uuid === line.lot_uuid
                    );

                    return (
                      <Card key={index} variant="outlined">
                        <CardContent>
                          <Grid container spacing={2} sx={{ alignItems: "flex-start" }}>
                            <Grid size={{ xs: 12, md: 7 }}>
                              <TextField
                                select
                                fullWidth
                                label={`Available lot ${index + 1}`}
                                value={line.lot_uuid}
                                onChange={(event) => updateLine(index, {
                                  lot_uuid: event.target.value,
                                })}
                              >
                                {context.data?.available_lots.map((row) => (
                                  <MenuItem
                                    key={row.lot.uuid}
                                    value={row.lot.uuid}
                                    disabled={selectedElsewhere.has(row.lot.uuid)}
                                  >
                                    {row.item.name} · {row.lot.external_lot_number || row.lot.internal_lot_code} · {inventoryQuantity(row.quantity_on_hand, row.item.unit?.symbol)} available
                                  </MenuItem>
                                ))}
                              </TextField>
                            </Grid>
                            <Grid size={{ xs: 10, md: 4 }}>
                              <TextField
                                fullWidth
                                label="Quantity used"
                                value={line.quantity}
                                onChange={(event) => updateLine(index, {
                                  quantity: event.target.value,
                                })}
                                helperText={selectedBalance?.item.unit?.symbol ?? ' '}
                              />
                            </Grid>
                            <Grid size={{ xs: 2, md: 1 }}>
                              <Tooltip title="Remove consumption line">
                                <span>
                                  <IconButton
                                    color="error"
                                    disabled={lines.length === 1}
                                    onClick={() => setLines((current) => current.filter(
                                      (_, lineIndex) => lineIndex !== index
                                    ))}
                                  >
                                    <DeleteOutlineRounded />
                                  </IconButton>
                                </span>
                              </Tooltip>
                            </Grid>
                          </Grid>
                        </CardContent>
                      </Card>
                    );
                  })}

                  <Button
                    variant="outlined"
                    startIcon={<AddRounded />}
                    disabled={lines.length >= context.data.available_lots.length}
                    onClick={() => setLines((current) => [
                      ...current,
                      blankConsumptionLine(),
                    ])}
                  >
                    Add consumption line
                  </Button>

                  <Grid container spacing={2}>
                    <Grid size={{ xs: 12, md: 4 }}>
                      <TextField
                        fullWidth
                        type="datetime-local"
                        label="Consumed at"
                        slotProps={{ inputLabel: { shrink: true } }}
                        value={consumedAt}
                        onChange={(event) => setConsumedAt(event.target.value)}
                      />
                    </Grid>
                    <Grid size={{ xs: 12, md: 8 }}>
                      <TextField
                        fullWidth
                        multiline
                        minRows={2}
                        label="Consumption notes"
                        value={notes}
                        onChange={(event) => setNotes(event.target.value)}
                      />
                    </Grid>
                  </Grid>

                  <Stack direction="row" sx={{ justifyContent: "flex-end" }}>
                    <Button
                      variant="contained"
                      disabled={mutation.isPending || !ready}
                      onClick={() => mutation.mutate()}
                    >
                      Post usage
                    </Button>
                  </Stack>
                </Stack>
              )}

              <Divider />

              <Typography sx={{ fontWeight: 700 }}>
                Posted consumption history
              </Typography>

              {context.data.consumptions.length === 0 ? (
                <Typography color="text.secondary">
                  No inventory usage has been posted for this ordered test.
                </Typography>
              ) : (
                <Stack spacing={1}>
                  {context.data.consumptions.map((entry) => (
                    <Accordion key={entry.uuid} disableGutters variant="outlined">
                      <AccordionSummary expandIcon={<ExpandMoreRounded />}>
                        <Stack
                          direction={{ xs: 'column', sm: 'row' }} sx={{ width: '100%', pr: 2, justifyContent: "space-between" }}
                        >
                          <Typography sx={{ fontWeight: 650 }}>{entry.consumption_number}</Typography>
                          <Typography color="text.secondary">{inventoryDate(entry.consumed_at)}</Typography>
                        </Stack>
                      </AccordionSummary>
                      <AccordionDetails>
                        <Stack spacing={1}>
                          {(entry.movements ?? []).map((movement) => (
                            <Stack key={movement.uuid} direction="row" sx={{ justifyContent: "space-between", gap: 2 }}>
                              <Typography>
                                {movement.item?.name} · {movement.lot?.external_lot_number || movement.lot?.internal_lot_code}
                              </Typography>
                              <Typography sx={{ fontWeight: 650 }}>
                                {inventoryQuantity(movement.quantity_delta, movement.item?.unit_symbol)}
                              </Typography>
                            </Stack>
                          ))}
                          {entry.notes && <Typography color="text.secondary">{entry.notes}</Typography>}
                        </Stack>
                      </AccordionDetails>
                    </Accordion>
                  ))}
                </Stack>
              )}
            </>
          )}
        </Stack>
      </CardContent>
    </Card>
  );
};

export default InventoryConsumptionPanel;
