import {
  useState,
} from 'react';

import ArrowBackRounded from
  '@mui/icons-material/ArrowBackRounded';
import DownloadRounded from
  '@mui/icons-material/DownloadRounded';
import ScienceRounded from
  '@mui/icons-material/ScienceRounded';

import {
  Alert,
  Box,
  Button,
  Card,
  CardContent,
  CircularProgress,
  Divider,
  Stack,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Typography,
} from '@mui/material';

import {
  useQuery,
} from '@tanstack/react-query';

import {
  Link,
  useParams,
} from 'react-router-dom';

import {
  downloadPatientPortalReportPdf,
  fetchPatientPortalReport,
} from '../api/patientPortalApi';

import PatientPortalLoadingState from
  '../components/PatientPortalLoadingState';

import PatientPortalPageHeader from
  '../components/PatientPortalPageHeader';

import PatientPortalStatusChip from
  '../components/PatientPortalStatusChip';

import {
  usePatientPortalAuth,
} from '../context/PatientPortalAuthContext';

import {
  extractPdfFilename,
  formatPortalDate,
  patientPortalErrorMessage,
  savePortalBlob,
} from '../utils/patientPortalDisplay';

const PatientPortalReportDetail = () => {
  const auth = usePatientPortalAuth();

  const {
    reportUuid = '',
  } = useParams<{
    reportUuid: string;
  }>();

  const [
    downloading,
    setDownloading,
  ] = useState(false);

  const [
    downloadError,
    setDownloadError,
  ] = useState<string | null>(null);

  const sessionKey =
    auth.session?.account.uuid ?? 'unknown';

  const query = useQuery({
    queryKey: [
      'patient-portal',
      sessionKey,
      'report',
      reportUuid,
    ],
    queryFn: () => (
      fetchPatientPortalReport(reportUuid)
    ),
    enabled: Boolean(reportUuid),
  });

  const handleDownload =
    async (): Promise<void> => {
      if (!query.data) {
        return;
      }

      setDownloadError(null);
      setDownloading(true);

      try {
        const result =
          await downloadPatientPortalReportPdf(
            query.data.uuid
          );

        const filename = extractPdfFilename(
          result.contentDisposition,
          `${query.data.report_number}.pdf`
        );

        savePortalBlob(
          result.blob,
          filename
        );
      } catch (error) {
        setDownloadError(
          patientPortalErrorMessage(
            error,
            'We could not download this report PDF.'
          )
        );
      } finally {
        setDownloading(false);
      }
    };

  if (query.isLoading) {
    return <PatientPortalLoadingState rows={3} />;
  }

  if (query.error || !query.data) {
    return (
      <Box>
        <Button
          component={Link}
          to="/patient-portal/reports"
          startIcon={<ArrowBackRounded />}
          sx={{ mb: 2 }}
        >
          Back to reports
        </Button>

        <Alert
          severity="error"
          action={
            <Button
              color="inherit"
              size="small"
              onClick={() => {
                void query.refetch();
              }}
            >
              Retry
            </Button>
          }
        >
          {patientPortalErrorMessage(
            query.error,
            'We could not load this laboratory report.'
          )}
        </Alert>
      </Box>
    );
  }

  const report = query.data;

  return (
    <Box>
      <Button
        component={Link}
        to="/patient-portal/reports"
        startIcon={<ArrowBackRounded />}
        sx={{ mb: 2 }}
      >
        Back to reports
      </Button>

      <PatientPortalPageHeader
        eyebrow="Current laboratory report"
        title={report.report_number}
        description="This is the current issued clinical report available through your patient portal."
        action={
          <Button
            variant="contained"
            startIcon={
              downloading
                ? (
                    <CircularProgress
                      size={17}
                      thickness={4}
                      sx={{ color: 'inherit' }}
                    />
                  )
                : <DownloadRounded />
            }
            disabled={downloading}
            onClick={() => {
              void handleDownload();
            }}
          >
            {downloading
              ? 'Preparing PDF'
              : 'Download PDF'}
          </Button>
        }
      />

      {downloadError && (
        <Alert
          severity="error"
          onClose={() => {
            setDownloadError(null);
          }}
          sx={{ mb: 2.5 }}
        >
          {downloadError}
        </Alert>
      )}

      {report.lifecycle_status === 'amended'
        && report.amendment && (
          <Alert
            severity="info"
            sx={{ mb: 2.5 }}
          >
            <Typography
              sx={{ fontWeight: 700 }}
            >
              This report has been amended.
            </Typography>

            <Typography
              sx={{
                mt: 0.35,
                fontSize: '0.82rem',
              }}
            >
              {report.amendment.reason
                || 'The laboratory issued a corrected current version.'}
            </Typography>
          </Alert>
        )}

      <Box
        sx={{
          display: 'grid',
          gridTemplateColumns: {
            xs: '1fr',
            lg: 'minmax(0, 1.35fr) minmax(320px, 0.65fr)',
          },
          gap: 2,
        }}
      >
        <Stack spacing={2}>
          <Card>
            <CardContent>
              <Box
                sx={{
                  display: 'flex',
                  gap: 1.25,
                  alignItems: 'center',
                  justifyContent: 'space-between',
                  flexWrap: 'wrap',
                }}
              >
                <Stack
                  direction="row"
                  spacing={1.15}
                  sx={{ alignItems: 'center' }}
                >
                  <ScienceRounded
                    sx={{ color: 'primary.main' }}
                  />

                  <Typography
                    variant="h6"
                    sx={{ fontWeight: 720 }}
                  >
                    Test results
                  </Typography>
                </Stack>

                <PatientPortalStatusChip
                  value={report.lifecycle_status}
                />
              </Box>

              <Divider sx={{ my: 2 }} />

              <Stack spacing={2}>
                {report.results.map((test, testIndex) => (
                  <Box
                    key={
                      test.order_item_uuid
                      || `${test.test_name ?? 'test'}-${testIndex}`
                    }
                  >
                    <Typography
                      sx={{
                        fontWeight: 720,
                      }}
                    >
                      {test.test_name
                        || 'Laboratory test'}
                    </Typography>

                    {test.category && (
                      <Typography
                        sx={{
                          mt: 0.25,
                          color: 'text.secondary',
                          fontSize: '0.74rem',
                        }}
                      >
                        {test.category}
                      </Typography>
                    )}

                    <TableContainer
                      sx={{
                        mt: 1.25,
                        border: '1px solid',
                        borderColor: 'divider',
                        borderRadius: '12px',
                      }}
                    >
                      <Table size="small">
                        <TableHead>
                          <TableRow>
                            <TableCell>
                              Parameter
                            </TableCell>
                            <TableCell>
                              Result
                            </TableCell>
                            <TableCell>
                              Reference
                            </TableCell>
                            <TableCell>
                              Flag
                            </TableCell>
                          </TableRow>
                        </TableHead>

                        <TableBody>
                          {test.results_data.map(
                            (entry, index) => (
                              <TableRow
                                key={
                                  `${entry.name ?? 'result'}-${index}`
                                }
                              >
                                <TableCell>
                                  {entry.name
                                    || 'Result'}
                                </TableCell>

                                <TableCell>
                                  <Typography
                                    sx={{
                                      fontWeight: 680,
                                    }}
                                  >
                                    {entry.value
                                      === null
                                      || entry.value
                                        === undefined
                                      || entry.value === ''
                                      ? '—'
                                      : String(
                                          entry.value
                                        )}
                                    {entry.unit
                                      ? ` ${entry.unit}`
                                      : ''}
                                  </Typography>
                                </TableCell>

                                <TableCell>
                                  {entry.reference_range
                                    || '—'}
                                </TableCell>

                                <TableCell>
                                  {entry.flag
                                    ? (
                                        <PatientPortalStatusChip
                                          value={
                                            entry.flag
                                          }
                                        />
                                      )
                                    : '—'}
                                </TableCell>
                              </TableRow>
                            )
                          )}
                        </TableBody>
                      </Table>
                    </TableContainer>

                    {test.notes && (
                      <Typography
                        sx={{
                          mt: 1,
                          color: 'text.secondary',
                          fontSize: '0.78rem',
                          lineHeight: 1.6,
                        }}
                      >
                        Note: {test.notes}
                      </Typography>
                    )}
                  </Box>
                ))}

                {report.results.length === 0 && (
                  <Typography
                    sx={{
                      py: 3,
                      color: 'text.secondary',
                      textAlign: 'center',
                    }}
                  >
                    No result rows were included in this report.
                  </Typography>
                )}
              </Stack>
            </CardContent>
          </Card>
        </Stack>

        <Stack spacing={2}>
          <Card>
            <CardContent>
              <Typography
                variant="h6"
                sx={{ fontWeight: 720 }}
              >
                Report details
              </Typography>

              <Divider sx={{ my: 2 }} />

              <Stack spacing={1.6}>
                {[
                  [
                    'Status',
                    report.lifecycle_status,
                  ],
                  [
                    'Version',
                    String(report.version),
                  ],
                  [
                    'Generated',
                    formatPortalDate(
                      report.generated_at
                    ),
                  ],
                  [
                    'Order',
                    report.order.order_number
                      || 'Not recorded',
                  ],
                  [
                    'Referring clinician',
                    report.order.referring_clinician
                      || 'Not recorded',
                  ],
                ].map(([label, value]) => (
                  <Box key={label}>
                    <Typography
                      sx={{
                        color: 'text.secondary',
                        fontSize: '0.7rem',
                        fontWeight: 650,
                      }}
                    >
                      {label}
                    </Typography>

                    <Typography
                      sx={{
                        mt: 0.35,
                        fontSize: '0.84rem',
                        fontWeight: 620,
                      }}
                    >
                      {value}
                    </Typography>
                  </Box>
                ))}
              </Stack>
            </CardContent>
          </Card>

          <Card>
            <CardContent>
              <Typography
                variant="h6"
                sx={{ fontWeight: 720 }}
              >
                Laboratory
              </Typography>

              <Divider sx={{ my: 2 }} />

              <Typography
                sx={{ fontWeight: 680 }}
              >
                {report.laboratory.name
                  || 'Laboratory'}
              </Typography>

              {report.laboratory.tagline && (
                <Typography
                  sx={{
                    mt: 0.35,
                    color: 'text.secondary',
                    fontSize: '0.76rem',
                  }}
                >
                  {report.laboratory.tagline}
                </Typography>
              )}

              {report.laboratory.address && (
                <Typography
                  sx={{
                    mt: 1.25,
                    color: 'text.secondary',
                    fontSize: '0.78rem',
                    lineHeight: 1.6,
                  }}
                >
                  {report.laboratory.address}
                </Typography>
              )}

              {(report.laboratory.phone
                || report.laboratory.email) && (
                  <Typography
                    sx={{
                      mt: 1,
                      color: 'text.secondary',
                      fontSize: '0.78rem',
                      lineHeight: 1.6,
                    }}
                  >
                    {[
                      report.laboratory.phone,
                      report.laboratory.email,
                    ]
                      .filter(Boolean)
                      .join(' · ')}
                  </Typography>
                )}
            </CardContent>
          </Card>
        </Stack>
      </Box>
    </Box>
  );
};

export default PatientPortalReportDetail;
