import {
  Alert,
  Box,
  Typography,
} from '@mui/material';

import type {
  ReportResultItem,
} from '../types/laboratoryReport.types';

import ReportResultValues from
  './ReportResultValues';

interface Props {
  results: ReportResultItem[];
}

const ReportResultsSection = ({
  results,
}: Props) => (
  <Box sx={{ mb: 3 }}>
    <Typography
      variant="h6"
      sx={{
        mb: 1.5,
        fontWeight: 760,
      }}
    >
      Approved results
    </Typography>

    {results.length === 0 ? (
      <Alert severity="info">
        No result snapshots are present
        in this report.
      </Alert>
    ) : (
      results.map((item, index) => (
        <ReportResultValues
          key={
            item.result_uuid ??
            `${item.test_name}-${index}`
          }
          item={item}
        />
      ))
    )}
  </Box>
);

export default ReportResultsSection;
