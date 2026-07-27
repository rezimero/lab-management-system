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

const credentialText = (
  report: LaboratoryReportResource
): string | null => {
  const credentials = [
    report.laboratory?.license_number
      ? `Licence: ${report.laboratory.license_number}`
      : null,
    report.laboratory?.registration_number
      ? `Registration: ${report.laboratory.registration_number}`
      : null,
  ].filter(
    (value): value is string =>
      value !== null
  );

  return credentials.length > 0
    ? credentials.join(' | ')
    : null;
};

const ReportSignatureFooter = ({
  report,
}: Props) => {
  const credentials =
    credentialText(report);

  return (
    <Box
      className="report-signature-footer"
      sx={{
        mt: 3,
        breakInside: 'avoid',
      }}
    >
      <Divider sx={{ mb: 2 }} />

      <Box
        sx={{
          display: 'flex',
          flexDirection: {
            xs: 'column',
            sm: 'row',
          },
          justifyContent: 'space-between',
          alignItems: {
            xs: 'flex-start',
            sm: 'flex-end',
          },
          gap: 2,
        }}
      >
        <Box>
          {report.laboratory
            ?.director_signature_url && (
            <Box
              component="img"
              src={
                report.laboratory
                  .director_signature_url
              }
              alt="Director signature"
              sx={{
                display: 'block',
                maxWidth: 180,
                maxHeight: 64,
                mb: 0.75,
                objectFit: 'contain',
              }}
            />
          )}

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

          {credentials && (
            <Typography
              variant="caption"
              color="text.secondary"
              sx={{ display: 'block' }}
            >
              {credentials}
            </Typography>
          )}
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
};

export default ReportSignatureFooter;