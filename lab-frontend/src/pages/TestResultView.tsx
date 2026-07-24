import {
  useEffect,
  useState,
  type ReactElement,
} from 'react';

import {
  useNavigate,
  useParams,
} from 'react-router-dom';

import {
  Alert,
  Box,
  Button,
  Chip,
  CircularProgress,
  Divider,
  Grid,
  IconButton,
  Paper,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Typography,
  alpha,
  useTheme,
} from '@mui/material';

import {
  ArrowBack as ArrowBackIcon,
  Assignment as AssignmentIcon,
  Cancel as CancelIcon,
  CheckCircle as CheckCircleIcon,
  HourglassEmpty as HourglassEmptyIcon,
  Pending as PendingIcon,
  Person as PersonIcon,
  Print as PrintIcon,
  Science as ScienceIcon,
} from '@mui/icons-material';

import api from '../api/client';
import Layout from '../components/Layout/Layout';

import type {
  TestResultResource,
  TestResultStatus,
  TestResultUserSummary,
  TestResultValue,
} from '../types';

type ChipColour =
  | 'default'
  | 'error'
  | 'info'
  | 'success'
  | 'warning';

interface StatusVisual {
  label: string;
  colour: ChipColour;
  accent: string;
  description: string;
  icon: ReactElement;
}

interface WrappedResource {
  data: TestResultResource;
}

interface ApiErrorShape {
  response?: {
    data?: {
      message?: unknown;
    };
  };
}

interface WorkflowCardProps {
  label: string;
  person?: TestResultUserSummary | null;
  timestamp?: string | null;
}

const statusVisuals: Record<
  TestResultStatus,
  StatusVisual
> = {
  draft: {
    label: 'Draft',
    colour: 'warning',
    accent: '#DC6803',
    description:
      'The result is still being prepared and may be edited.',
    icon: <PendingIcon />,
  },

  submitted: {
    label: 'Submitted',
    colour: 'info',
    accent: '#1570EF',
    description:
      'The result has been submitted and is awaiting review.',
    icon: <HourglassEmptyIcon />,
  },

  approved: {
    label: 'Approved',
    colour: 'success',
    accent: '#079455',
    description:
      'The result has completed the authorised review workflow.',
    icon: <CheckCircleIcon />,
  },

  rejected: {
    label: 'Rejected',
    colour: 'error',
    accent: '#D92D20',
    description:
      'The result was returned for correction before approval.',
    icon: <CancelIcon />,
  },
};

const formatLabel = (
  value?: string | null
): string => {
  if (!value) {
    return 'Not recorded';
  }

  return value
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (character) =>
      character.toUpperCase()
    );
};

const formatDateTime = (
  value?: string | null
): string => {
  if (!value) {
    return 'Not recorded';
  }

  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return value;
  }

  return new Intl.DateTimeFormat(
    undefined,
    {
      dateStyle: 'medium',
      timeStyle: 'short',
    }
  ).format(date);
};

const unwrapResource = (
  payload: TestResultResource | WrappedResource
): TestResultResource => {
  if ('data' in payload) {
    return payload.data;
  }

  return payload;
};

const getApiErrorMessage = (
  requestError: unknown
): string => {
  if (
    typeof requestError !== 'object' ||
    requestError === null
  ) {
    return 'Failed to load test result';
  }

  const response =
    (requestError as ApiErrorShape).response;

  const message = response?.data?.message;

  return typeof message === 'string'
    ? message
    : 'Failed to load test result';
};

const getFlagColour = (
  flag?: string | null
): ChipColour => {
  const normalized =
    flag?.trim().toLowerCase() ?? '';

  if (
    normalized.includes('critical') ||
    normalized.includes('panic')
  ) {
    return 'error';
  }

  if (
    normalized.includes('high') ||
    normalized.includes('low') ||
    normalized.includes('abnormal')
  ) {
    return 'warning';
  }

  if (
    normalized.includes('normal') ||
    normalized.includes('negative')
  ) {
    return 'success';
  }

  return normalized ? 'info' : 'default';
};

const ResultFlag = ({
  result,
}: {
  result: TestResultValue;
}) => {
  if (!result.flag) {
    return (
      <Typography
        variant="body2"
        color="text.secondary"
      >
        Not flagged
      </Typography>
    );
  }

  return (
    <Chip
      label={formatLabel(result.flag)}
      color={getFlagColour(result.flag)}
      size="small"
      variant="outlined"
    />
  );
};

const WorkflowCard = ({
  label,
  person,
  timestamp,
}: WorkflowCardProps) => (
  <Paper
    variant="outlined"
    sx={{
      p: 2,
      height: '100%',
      borderRadius: 2.5,
    }}
  >
    <Typography
      variant="caption"
      color="text.secondary"
      sx={{
        display: 'block',
        mb: 0.75,
        fontWeight: 700,
        textTransform: 'uppercase',
        letterSpacing: '0.05em',
      }}
    >
      {label}
    </Typography>

    <Typography
      variant="body2"
      sx={{
        fontWeight: 700,
      }}
    >
      {person?.name ?? 'Not recorded'}
    </Typography>

    {person?.email && (
      <Typography
        variant="caption"
        color="text.secondary"
        sx={{
          display: 'block',
          mt: 0.25,
          wordBreak: 'break-word',
        }}
      >
        {person.email}
      </Typography>
    )}

    <Typography
      variant="caption"
      color="text.secondary"
      sx={{
        display: 'block',
        mt: 1,
      }}
    >
      {formatDateTime(timestamp)}
    </Typography>
  </Paper>
);

const TestResultView = () => {
  const {
    id: uuid,
  } = useParams<{
    id: string;
  }>();

  const navigate = useNavigate();
  const theme = useTheme();

  const [
    result,
    setResult,
  ] = useState<TestResultResource | null>(
    null
  );

  const [
    loading,
    setLoading,
  ] = useState(true);

  const [
    error,
    setError,
  ] = useState<string | null>(null);

  useEffect(() => {
    let active = true;

    const fetchResult = async () => {
      if (!uuid) {
        if (active) {
          setError(
            'A test result UUID was not provided.'
          );

          setLoading(false);
        }

        return;
      }

      setLoading(true);
      setError(null);

      try {
        const response = await api.get<
          TestResultResource | WrappedResource
        >(`/test-results/${uuid}`);

        if (active) {
          setResult(
            unwrapResource(response.data)
          );
        }
      } catch (requestError: unknown) {
        if (active) {
          setResult(null);

          setError(
            getApiErrorMessage(requestError)
          );
        }
      } finally {
        if (active) {
          setLoading(false);
        }
      }
    };

    void fetchResult();

    return () => {
      active = false;
    };
  }, [uuid]);

  const handlePrint = () => {
    window.print();
  };

  if (loading) {
    return (
      <Layout title="Test Result">
        <Box
          sx={{
            display: 'flex',
            justifyContent: 'center',
            py: 8,
          }}
        >
          <CircularProgress />
        </Box>
      </Layout>
    );
  }

  if (error || !result) {
    return (
      <Layout title="Test Result">
        <Alert
          severity="error"
          sx={{
            mt: 2,
          }}
        >
          {error ?? 'Test result not found'}
        </Alert>
      </Layout>
    );
  }

  const statusVisual =
    statusVisuals[result.status];

  const orderItem =
    result.order_item ?? null;

  const order =
    orderItem?.order ?? null;

  const patient =
    order?.patient ?? null;

  const results =
    Array.isArray(result.results_data)
      ? result.results_data
      : [];

  return (
    <Layout title="Test Result">
      <style>
        {`
          @media print {
            .no-print {
              display: none !important;
            }

            .print-paper {
              box-shadow: none !important;
              border: none !important;
              padding: 20px !important;
            }

            body {
              background: white !important;
            }
          }
        `}
      </style>

      <Box
        className="no-print"
        sx={{
          mb: 3,
          display: 'flex',
          flexDirection: {
            xs: 'column',
            sm: 'row',
          },
          justifyContent: 'space-between',
          alignItems: {
            xs: 'stretch',
            sm: 'center',
          },
          gap: 2,
        }}
      >
        <Box
          sx={{
            display: 'flex',
            alignItems: 'center',
            gap: 1.5,
          }}
        >
          <IconButton
            aria-label="Back to test results"
            onClick={() => {
              navigate('/test-results');
            }}
          >
            <ArrowBackIcon />
          </IconButton>

          <Box>
            <Typography
              component="h2"
              variant="h5"
              sx={{
                fontWeight: 750,
              }}
            >
              Test Result Details
            </Typography>

            <Typography
              variant="body2"
              color="text.secondary"
            >
              Review the recorded values and
              workflow history.
            </Typography>
          </Box>
        </Box>

        <Button
          variant="outlined"
          startIcon={<PrintIcon />}
          onClick={handlePrint}
          sx={{
            borderRadius: 2,
            alignSelf: {
              xs: 'stretch',
              sm: 'center',
            },
          }}
        >
          Print result
        </Button>
      </Box>

      <Paper
        className="print-paper"
        sx={{
          p: {
            xs: 2,
            sm: 3,
            lg: 4,
          },
          borderRadius: 3,
          backgroundColor: '#FFFFFF',
          border: `1px solid ${
            alpha(
              theme.palette.divider,
              0.12
            )
          }`,
        }}
      >
        <Box
          sx={{
            mb: 3,
            pb: 2.5,
            borderBottom: `1px solid ${
              alpha(
                theme.palette.divider,
                0.12
              )
            }`,
          }}
        >
          <Typography
            variant="h4"
            sx={{
              fontWeight: 800,
              color:
                theme.palette.primary.main,
            }}
          >
            Laboratory Test Result
          </Typography>

          <Typography
            variant="body2"
            color="text.secondary"
            sx={{
              mt: 0.5,
            }}
          >
            Result UUID: {result.uuid}
          </Typography>

          <Typography
            variant="caption"
            color="text.secondary"
            sx={{
              display: 'block',
              mt: 0.5,
            }}
          >
            Created: {
              formatDateTime(
                result.created_at
              )
            }
          </Typography>
        </Box>

        <Box
          sx={{
            mb: 3,
            p: 2,
            display: 'flex',
            flexDirection: {
              xs: 'column',
              sm: 'row',
            },
            justifyContent: 'space-between',
            alignItems: {
              xs: 'flex-start',
              sm: 'center',
            },
            gap: 2,
            borderRadius: 2,
            borderLeft:
              `4px solid ${
                statusVisual.accent
              }`,
            backgroundColor: alpha(
              statusVisual.accent,
              0.07
            ),
          }}
        >
          <Box>
            <Typography
              variant="caption"
              color="text.secondary"
              sx={{
                display: 'block',
                mb: 0.75,
              }}
            >
              Workflow status
            </Typography>

            <Chip
              icon={statusVisual.icon}
              label={statusVisual.label}
              color={statusVisual.colour}
            />

            <Typography
              variant="body2"
              color="text.secondary"
              sx={{
                mt: 1,
              }}
            >
              {statusVisual.description}
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
              variant="caption"
              color="text.secondary"
            >
              Laboratory order
            </Typography>

            <Typography
              variant="body1"
              sx={{
                fontWeight: 750,
              }}
            >
              {
                order?.order_number ??
                'Not recorded'
              }
            </Typography>

            <Typography
              variant="caption"
              color="text.secondary"
            >
              Priority: {
                formatLabel(
                  order?.priority
                )
              }
            </Typography>
          </Box>
        </Box>

        <Grid
          container
          spacing={2.5}
          sx={{
            mb: 3,
          }}
        >
          <Grid
            size={{
              xs: 12,
              md: 6,
            }}
          >
            <Paper
              variant="outlined"
              sx={{
                p: 2.25,
                height: '100%',
                borderRadius: 2.5,
              }}
            >
              <Typography
                variant="subtitle1"
                color="primary"
                sx={{
                  mb: 1,
                  fontWeight: 750,
                }}
              >
                <PersonIcon
                  sx={{
                    mr: 1,
                    verticalAlign: 'middle',
                  }}
                />
                Patient
              </Typography>

              <Divider
                sx={{
                  mb: 1.5,
                }}
              />

              <Grid
                container
                spacing={1.5}
              >
                <Grid size={{ xs: 12 }}>
                  <Typography
                    variant="caption"
                    color="text.secondary"
                  >
                    Full name
                  </Typography>

                  <Typography
                    variant="body2"
                    sx={{
                      fontWeight: 700,
                    }}
                  >
                    {
                      patient?.full_name ??
                      'Not recorded'
                    }
                  </Typography>
                </Grid>

                <Grid size={{ xs: 12 }}>
                  <Typography
                    variant="caption"
                    color="text.secondary"
                  >
                    Medical record number
                  </Typography>

                  <Typography
                    variant="body2"
                  >
                    {
                      patient
                        ?.medical_record_number ??
                      'Not recorded'
                    }
                  </Typography>
                </Grid>
              </Grid>
            </Paper>
          </Grid>

          <Grid
            size={{
              xs: 12,
              md: 6,
            }}
          >
            <Paper
              variant="outlined"
              sx={{
                p: 2.25,
                height: '100%',
                borderRadius: 2.5,
              }}
            >
              <Typography
                variant="subtitle1"
                color="primary"
                sx={{
                  mb: 1,
                  fontWeight: 750,
                }}
              >
                <ScienceIcon
                  sx={{
                    mr: 1,
                    verticalAlign: 'middle',
                  }}
                />
                Test and order item
              </Typography>

              <Divider
                sx={{
                  mb: 1.5,
                }}
              />

              <Grid
                container
                spacing={1.5}
              >
                <Grid size={{ xs: 12 }}>
                  <Typography
                    variant="caption"
                    color="text.secondary"
                  >
                    Test name
                  </Typography>

                  <Typography
                    variant="body2"
                    sx={{
                      fontWeight: 700,
                    }}
                  >
                    {
                      orderItem?.test_name ??
                      'Not recorded'
                    }
                  </Typography>
                </Grid>

                <Grid size={{ xs: 6 }}>
                  <Typography
                    variant="caption"
                    color="text.secondary"
                  >
                    Category
                  </Typography>

                  <Typography
                    variant="body2"
                  >
                    {
                      orderItem?.category ??
                      'Not recorded'
                    }
                  </Typography>
                </Grid>

                <Grid size={{ xs: 6 }}>
                  <Typography
                    variant="caption"
                    color="text.secondary"
                  >
                    Item status
                  </Typography>

                  <Typography
                    variant="body2"
                  >
                    {
                      formatLabel(
                        orderItem?.status
                      )
                    }
                  </Typography>
                </Grid>

                <Grid size={{ xs: 6 }}>
                  <Typography
                    variant="caption"
                    color="text.secondary"
                  >
                    Order status
                  </Typography>

                  <Typography
                    variant="body2"
                  >
                    {
                      formatLabel(
                        order?.status
                      )
                    }
                  </Typography>
                </Grid>

                <Grid size={{ xs: 6 }}>
                  <Typography
                    variant="caption"
                    color="text.secondary"
                  >
                    Order UUID
                  </Typography>

                  <Typography
                    variant="body2"
                    sx={{
                      wordBreak: 'break-word',
                    }}
                  >
                    {
                      order?.uuid ??
                      'Not recorded'
                    }
                  </Typography>
                </Grid>
              </Grid>
            </Paper>
          </Grid>
        </Grid>

        <Box
          sx={{
            mb: 3,
          }}
        >
          <Typography
            variant="subtitle1"
            sx={{
              mb: 1.5,
              fontWeight: 750,
            }}
          >
            <AssignmentIcon
              sx={{
                mr: 1,
                verticalAlign: 'middle',
              }}
            />
            Recorded parameters
          </Typography>

          {results.length === 0 ? (
            <Alert severity="info">
              No parameter values have been
              recorded for this result.
            </Alert>
          ) : (
            <TableContainer
              component={Paper}
              variant="outlined"
            >
              <Table>
                <TableHead
                  sx={{
                    backgroundColor: alpha(
                      theme.palette.primary.main,
                      0.05
                    ),
                  }}
                >
                  <TableRow>
                    <TableCell>
                      Parameter
                    </TableCell>

                    <TableCell>
                      Value
                    </TableCell>

                    <TableCell>
                      Unit
                    </TableCell>

                    <TableCell>
                      Reference range
                    </TableCell>

                    <TableCell>
                      Flag
                    </TableCell>

                    <TableCell>
                      Notes
                    </TableCell>
                  </TableRow>
                </TableHead>

                <TableBody>
                  {results.map(
                    (
                      item,
                      index
                    ) => (
                      <TableRow
                        key={
                          `${item.name}-${index}`
                        }
                      >
                        <TableCell
                          sx={{
                            fontWeight: 700,
                          }}
                        >
                          {
                            item.name ||
                            `Parameter ${
                              index + 1
                            }`
                          }
                        </TableCell>

                        <TableCell>
                          {
                            item.value === null ||
                            item.value === ''
                              ? 'Not recorded'
                              : String(
                                  item.value
                                )
                          }
                        </TableCell>

                        <TableCell>
                          {
                            item.unit ||
                            'Not recorded'
                          }
                        </TableCell>

                        <TableCell>
                          {
                            item.reference_range ||
                            'Not recorded'
                          }
                        </TableCell>

                        <TableCell>
                          <ResultFlag
                            result={item}
                          />
                        </TableCell>

                        <TableCell>
                          {
                            item.notes ||
                            '—'
                          }
                        </TableCell>
                      </TableRow>
                    )
                  )}
                </TableBody>
              </Table>
            </TableContainer>
          )}
        </Box>

        {result.notes && (
          <Box
            sx={{
              mb: 3,
            }}
          >
            <Typography
              variant="subtitle2"
              sx={{
                mb: 1,
                fontWeight: 750,
              }}
            >
              Result notes
            </Typography>

            <Paper
              variant="outlined"
              sx={{
                p: 2,
                borderRadius: 2,
              }}
            >
              <Typography
                variant="body2"
                sx={{
                  whiteSpace: 'pre-wrap',
                }}
              >
                {result.notes}
              </Typography>
            </Paper>
          </Box>
        )}

        {result.rejection_reason && (
          <Alert
            severity="error"
            sx={{
              mb: 3,
            }}
          >
            <Typography
              variant="subtitle2"
              sx={{
                mb: 0.5,
                fontWeight: 750,
              }}
            >
              Rejection reason
            </Typography>

            {result.rejection_reason}
          </Alert>
        )}

        <Box>
          <Typography
            variant="subtitle1"
            sx={{
              mb: 1.5,
              fontWeight: 750,
            }}
          >
            Workflow history
          </Typography>

          <Grid
            container
            spacing={2}
          >
            <Grid
              size={{
                xs: 12,
                md: 4,
              }}
            >
              <WorkflowCard
                label="Entered by"
                person={result.entered_by}
                timestamp={
                  result.created_at
                }
              />
            </Grid>

            <Grid
              size={{
                xs: 12,
                md: 4,
              }}
            >
              <WorkflowCard
                label="Submitted by"
                person={result.submitted_by}
                timestamp={
                  result.submitted_at
                }
              />
            </Grid>

            <Grid
              size={{
                xs: 12,
                md: 4,
              }}
            >
              <WorkflowCard
                label="Reviewed by"
                person={result.reviewed_by}
                timestamp={
                  result.reviewed_at
                }
              />
            </Grid>
          </Grid>
        </Box>

        <Box
          sx={{
            mt: 3,
            pt: 2,
            borderTop: `1px solid ${
              alpha(
                theme.palette.divider,
                0.12
              )
            }`,
          }}
        >
          <Typography
            variant="caption"
            color="text.secondary"
          >
            Last updated: {
              formatDateTime(
                result.updated_at
              )
            }
          </Typography>
        </Box>
      </Paper>
    </Layout>
  );
};

export default TestResultView;
