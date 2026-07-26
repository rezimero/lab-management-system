import { Box, Pagination, Typography } from '@mui/material';
import type { LaboratoryOrderPageMeta } from '../types/laboratoryOrder.types';

interface Props {
  meta: LaboratoryOrderPageMeta;
  page: number;
  onPageChange: (page: number) => void;
}

const OrderPagination = ({ meta, page, onPageChange }: Props) => {
  if (meta.total === 0) return null;
  return (
    <Box sx={{
      mt: 2.5,
      display: 'flex',
      flexDirection: { xs: 'column', sm: 'row' },
      justifyContent: 'space-between',
      alignItems: 'center',
      gap: 1.5,
    }}>
      <Typography variant="body2" color="text.secondary">
        {meta.total} laboratory order{meta.total === 1 ? '' : 's'}
      </Typography>
      <Pagination
        page={page}
        count={Math.max(meta.last_page, 1)}
        onChange={(_, nextPage) => onPageChange(nextPage)}
      />
    </Box>
  );
};

export default OrderPagination;
