import {
  Box,
  Divider,
  Typography,
} from '@mui/material';

import type {
  LaboratoryReportResource,
} from '../types/laboratoryReport.types';

interface Props {
  report: LaboratoryReportResource;
}

const ReportSignatureFooter = ({
  report,
}: Props) => (
  <Box sx={{ mt: 3 }}>
    <Divider sx={{ mb: 2 }} />

    <Box
      sx={{
        display: 'flex',
        flexDirection: {
          xs: 'column',
          sm: 'row',
        },
        justifyContent: 'space-between',
        gap: 2,
      }}
    >
      <Box>
        <Typography
          variant="subtitle2"
          sx={{ fontWeight: 750 }}
        >
          Laboratory director
        </Typography>

        <Typography
          variant="body2"
          color="text.secondary"
        >
          {report.laboratory
            ?.director_name ??
            'Not recorded'}
        </Typography>
      </Box>

      <Box
        sx={{
          textAlign: {
            xs: 'left',
            sm: 'right',
          },
        }}
      >
        <Typography
          variant="body2"
          sx={{ fontWeight: 700 }}
        >
          {report.laboratory
            ?.lab_name ??
            'Laboratory'}
        </Typography>

        <Typography
          variant="caption"
          color="text.secondary"
        >
          {report.laboratory
            ?.full_address ??
            report.laboratory?.address ??
            'Address not recorded'}
        </Typography>
      </Box>
    </Box>

    {report.laboratory
      ?.report_footer && (
      <Typography
        variant="caption"
        color="text.secondary"
        sx={{
          display: 'block',
          mt: 2,
          textAlign: 'center',
        }}
      >
        {report.laboratory.report_footer}
      </Typography>
    )}
  </Box>
);

export default ReportSignatureFooter;
