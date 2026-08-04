import {
  Alert,
  Card,
  CardContent,
  Divider,
  Stack,
  Typography,
} from '@mui/material';

import type {
  InventoryAlerts,
} from '../types/inventory.types';

import {
  inventoryDate,
  inventoryQuantity,
} from '../utils/inventoryDisplay';

interface Props {
  alerts?: InventoryAlerts;
}

const InventoryAlertsPanel = ({ alerts }: Props) => {
  if (!alerts) {
    return <Alert severity="info">Inventory alerts are loading.</Alert>;
  }

  return (
    <Stack spacing={2}>
      <Alert severity={alerts.summary.expired > 0 ? 'error' : 'info'}>
        {alerts.summary.expired} expired lot(s), {alerts.summary.expiring_soon} expiring within 30 days.
      </Alert>

      <Card variant="outlined">
        <CardContent>
          <Typography variant="h6" gutterBottom sx={{ fontWeight: 720 }}>
            Reorder warnings
          </Typography>
          <Stack divider={<Divider flexItem />} spacing={1.5}>
            {[...alerts.out_of_stock, ...alerts.low_stock].map((row) => (
              <Stack
                key={`${row.branch.uuid}-${row.item.uuid}`}
                direction={{ xs: 'column', sm: 'row' }}
               sx={{ justifyContent: "space-between", gap: 1 }}>
                <div>
                  <Typography sx={{ fontWeight: 650 }}>{row.item.name}</Typography>
                  <Typography variant="body2" color="text.secondary">
                    {row.branch.name}
                  </Typography>
                </div>
                <Typography>
                  {inventoryQuantity(row.quantity_on_hand, row.item.unit?.symbol)} on hand · reorder at {inventoryQuantity(row.reorder_level, row.item.unit?.symbol)}
                </Typography>
              </Stack>
            ))}
            {alerts.out_of_stock.length + alerts.low_stock.length === 0 && (
              <Typography color="text.secondary">No reorder warnings.</Typography>
            )}
          </Stack>
        </CardContent>
      </Card>

      <Card variant="outlined">
        <CardContent>
          <Typography variant="h6" gutterBottom sx={{ fontWeight: 720 }}>
            Expiry watch
          </Typography>
          <Stack divider={<Divider flexItem />} spacing={1.5}>
            {alerts.expiring.map((row) => (
              <Stack
                key={`${row.branch.uuid}-${row.lot.uuid}`}
                direction={{ xs: 'column', sm: 'row' }}
               sx={{ justifyContent: "space-between", gap: 1 }}>
                <div>
                  <Typography sx={{ fontWeight: 650 }}>{row.item.name}</Typography>
                  <Typography variant="body2" color="text.secondary">
                    {row.branch.name} · {row.lot.external_lot_number || row.lot.internal_lot_code}
                  </Typography>
                </div>
                <Typography>{inventoryDate(row.lot.expires_at)}</Typography>
              </Stack>
            ))}
            {alerts.expiring.length === 0 && (
              <Typography color="text.secondary">No lots expire within 30 days.</Typography>
            )}
          </Stack>
        </CardContent>
      </Card>
    </Stack>
  );
};

export default InventoryAlertsPanel;
