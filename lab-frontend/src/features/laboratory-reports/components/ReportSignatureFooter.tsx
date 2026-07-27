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

const laboratoryCredentialText = (
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

const directorCredentialText = (
  report: LaboratoryReportResource
): string | null => {
  const credentials = [
    report.laboratory?.director_qualification,
    report.laboratory?.director_registration_number
      ? (
        `Professional registration: ${
          report.laboratory
            .director_registration_number
        }`
      )
      : null,
  ].filter(
    (value): value is string =>
      Boolean(value)
  );

  return credentials.length > 0
    ? credentials.join(' | ')
    : null;
};

const ReportSignatureFooter = ({
  report,
}: Props) => {
  const laboratoryCredentials =
    laboratoryCredentialText(report);

  const directorCredentials =
    directorCredentialText(report);

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
            {report.laboratory
              ?.signatory_designation ??
              'Laboratory director'}
          </Typography>

          <Typography
            variant="body2"
            color="text.secondary"
          >
            {report.laboratory
              ?.director_name ??
              'Not recorded'}
          </Typography>

          {directorCredentials && (
            <Typography
              variant="caption"
              color="text.secondary"
              sx={{ display: 'block' }}
            >
              {directorCredentials}
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

          {laboratoryCredentials && (
            <Typography
              variant="caption"
              color="text.secondary"
              sx={{ display: 'block' }}
            >
              {laboratoryCredentials}
            </Typography>
          )}
        </Box>
      </Box>

      {report.laboratory
        ?.accreditation_details && (
        <Typography
          variant="caption"
          color="text.secondary"
          sx={{
            display: 'block',
            mt: 2,
            textAlign: 'center',
            fontWeight: 650,
          }}
        >
          {
            report.laboratory
              .accreditation_details
          }
        </Typography>
      )}

      {report.laboratory
        ?.report_footer && (
        <Typography
          variant="caption"
          color="text.secondary"
          sx={{
            display: 'block',
            mt: 1,
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