import {
  Box,
  Pagination,
  Typography,
} from '@mui/material';

import type {
  ReportPageMeta,
} from '../types/laboratoryReport.types';

interface Props {
  meta: ReportPageMeta;
  page: number;
  onPageChange: (
    page: number
  ) => void;
}

const ReportPagination = ({
  meta,
  page,
  onPageChange,
}: Props) => {
  if (meta.total === 0) {
    return null;
  }

  return (
    <Box
      sx={{
        mt: 2.5,
        display: 'flex',
        flexDirection: {
          xs: 'column',
          sm: 'row',
        },
        justifyContent: 'space-between',
        alignItems: 'center',
        gap: 1.5,
      }}
    >
      <Typography
        variant="body2"
        color="text.secondary"
      >
        {meta.total} issued report
        {meta.total === 1 ? '' : 's'}
      </Typography>

      <Pagination
        page={page}
        count={Math.max(
          meta.last_page,
          1
        )}
        onChange={(_, nextPage) => {
          onPageChange(nextPage);
        }}
      />
    </Box>
  );
};

export default ReportPagination;
