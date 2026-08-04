import {
  Chip,
  Paper,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Typography,
} from '@mui/material';

import type {
  InventoryStockBalance,
} from '../types/inventory.types';

import {
  inventoryDate,
  inventoryQuantity,
} from '../utils/inventoryDisplay';

interface Props {
  rows: InventoryStockBalance[];
}

const InventoryStockTable = ({ rows }: Props) => (
  <TableContainer component={Paper} variant="outlined">
    <Table size="small">
      <TableHead>
        <TableRow>
          <TableCell>Item</TableCell>
          <TableCell>Branch</TableCell>
          <TableCell>Lot / batch</TableCell>
          <TableCell>Expiry</TableCell>
          <TableCell align="right">On hand</TableCell>
          <TableCell>Last movement</TableCell>
        </TableRow>
      </TableHead>
      <TableBody>
        {rows.length === 0 && (
          <TableRow>
            <TableCell colSpan={6}>
              <Typography color="text.secondary" align="center" sx={{ py: 3 }}>
                No stock matches the current filters.
              </Typography>
            </TableCell>
          </TableRow>
        )}
        {rows.map((row) => (
          <TableRow key={`${row.branch.uuid}-${row.lot.uuid}`} hover>
            <TableCell>
              <Typography sx={{ fontWeight: 680 }}>{row.item.name}</Typography>
              <Typography variant="caption" color="text.secondary">
                {row.item.sku} · {row.item.item_type}
              </Typography>
            </TableCell>
            <TableCell>{row.branch.name}</TableCell>
            <TableCell>
              {row.lot.external_lot_number
                || row.lot.batch_number
                || row.lot.internal_lot_code}
            </TableCell>
            <TableCell>
              {row.lot.is_expired ? (
                <Chip label={`Expired ${inventoryDate(row.lot.expires_at)}`} color="error" size="small" />
              ) : inventoryDate(row.lot.expires_at)}
            </TableCell>
            <TableCell align="right">
              {inventoryQuantity(
                row.quantity_on_hand,
                row.item.unit?.symbol
              )}
            </TableCell>
            <TableCell>{inventoryDate(row.last_movement_at)}</TableCell>
          </TableRow>
        ))}
      </TableBody>
    </Table>
  </TableContainer>
);

export default InventoryStockTable;
