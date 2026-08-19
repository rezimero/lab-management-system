import {
  RefreshRounded,
  ReplayRounded,
  RuleRounded,
} from '@mui/icons-material';

import {
  Alert,
  Box,
  Button,
  Card,
  CardContent,
  Chip,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  Divider,
  MenuItem,
  Paper,
  Stack,
  TextField,
  Typography,
} from '@mui/material';

import {
  useMutation,
  useQuery,
  useQueryClient,
} from '@tanstack/react-query';

import {
  useState,
} from 'react';

import {
  useNavigate,
} from 'react-router-dom';

import Layout from '../../../components/Layout/Layout';

import {
  useAuth,
} from '../../../contexts/AuthContext';

import {
  apiErrorMessage,
} from '../../test-results/utils/apiErrorMessage';

import {
  fetchAnalyzerImport,
  fetchAnalyzerImports,
  rejectAnalyzerImport,
  reprocessAnalyzerImport,
} from '../api/analyzerApi';

import {
  roleAllowsAnalyzerAction,
} from '../permissions/analyzerPermissions';

import type {
  AnalyzerImportStatus,
  AnalyzerResultImport,
} from '../types/analyzer.types';

const statuses: AnalyzerImportStatus[] = [
  'received',
  'needs_review',
  'ready',
  'imported',
  'rejected',
  'failed',
  'duplicate',
];

const humanize = (
  value: string
): string => (
  value
    .split('_')
    .map((part) => (
      part.charAt(0).toUpperCase()
      + part.slice(1)
    ))
    .join(' ')
);

const statusColor = (
  status: AnalyzerImportStatus
):
  | 'default'
  | 'success'
  | 'warning'
  | 'error'
  | 'info' => {
  if (status === 'imported') {
    return 'success';
  }

  if (
    status === 'needs_review'
    || status === 'ready'
  ) {
    return 'warning';
  }

  if (
    status === 'failed'
    || status === 'rejected'
  ) {
    return 'error';
  }

  if (status === 'received') {
    return 'info';
  }

  return 'default';
};

const jsonText = (
  value: unknown
): string => (
  value == null
    ? '—'
    : JSON.stringify(value, null, 2)
);

const AnalyzerImports = () => {
  const auth = useAuth();
  const navigate = useNavigate();
  const queryClient = useQueryClient();

  const [
    search,
    setSearch,
  ] = useState('');

  const [
    status,
    setStatus,
  ] = useState('');

  const [
    selectedUuid,
    setSelectedUuid,
  ] = useState<string | null>(null);

  const [
    detailOpen,
    setDetailOpen,
  ] = useState(false);

  const [
    rejectOpen,
    setRejectOpen,
  ] = useState(false);

  const [
    rejectNote,
    setRejectNote,
  ] = useState('');

  const [
    error,
    setError,
  ] = useState<string | null>(null);

  const canReview =
    roleAllowsAnalyzerAction(
      auth.user?.role,
      'review_imports'
    );

  const canReprocess =
    roleAllowsAnalyzerAction(
      auth.user?.role,
      'reprocess_imports'
    );

  const importsQuery = useQuery({
    queryKey: [
      'analyzer-imports',
      search,
      status,
    ],
    queryFn: () => fetchAnalyzerImports(
      search,
      status
    ),
  });

  const detailQuery = useQuery({
    queryKey: [
      'analyzer-import',
      selectedUuid,
    ],
    queryFn: () => fetchAnalyzerImport(
      selectedUuid ?? ''
    ),
    enabled: Boolean(selectedUuid),
  });

  const reprocessMutation = useMutation({
    mutationFn: (
      item: AnalyzerResultImport
    ) => reprocessAnalyzerImport(
      item.uuid
    ),
    onSuccess: async () => {
      setError(null);

      await Promise.all([
        queryClient.invalidateQueries({
          queryKey: ['analyzer-imports'],
        }),
        queryClient.invalidateQueries({
          queryKey: [
            'analyzer-import',
            selectedUuid,
          ],
        }),
      ]);
    },
    onError: (requestError: unknown) => {
      setError(apiErrorMessage(
        requestError,
        'Analyzer import reprocessing failed.'
      ));
    },
  });

  const rejectMutation = useMutation({
    mutationFn: () => {
      if (!selectedUuid) {
        throw new Error(
          'No analyzer import is selected.'
        );
      }

      return rejectAnalyzerImport(
        selectedUuid,
        rejectNote.trim()
      );
    },
    onSuccess: async () => {
      setRejectOpen(false);
      setRejectNote('');
      setError(null);

      await Promise.all([
        queryClient.invalidateQueries({
          queryKey: ['analyzer-imports'],
        }),
        queryClient.invalidateQueries({
          queryKey: [
            'analyzer-import',
            selectedUuid,
          ],
        }),
      ]);
    },
    onError: (requestError: unknown) => {
      setError(apiErrorMessage(
        requestError,
        'Analyzer import rejection failed.'
      ));
    },
  });

  const imports =
    importsQuery.data?.data ?? [];

  const statistics =
    importsQuery.data?.statistics ?? {};

  const detail =
    detailQuery.data ?? null;

  const openDetail = (
    item: AnalyzerResultImport
  ) => {
    setSelectedUuid(item.uuid);
    setDetailOpen(true);
    setError(null);
  };

  return (
    <Layout title="Analyzer validation queue">
      <Stack spacing={2.5}>
        <Box
          sx={{
            display: 'flex',
            justifyContent: 'space-between',
            gap: 2,
            flexDirection: {
              xs: 'column',
              md: 'row',
            },
          }}
        >
          <Box>
            <Typography
              variant="h4"
              sx={{ fontWeight: 760 }}
            >
              Analyzer validation queue
            </Typography>

            <Typography
              color="text.secondary"
              sx={{ mt: 0.7 }}
            >
              Review imported analyzer candidates, resolve mapping or workflow conflicts and trace machine-to-result provenance.
            </Typography>
          </Box>

          <Button
            startIcon={<RefreshRounded />}
            onClick={() => {
              void importsQuery.refetch();
            }}
          >
            Refresh
          </Button>
        </Box>

        {error && (
          <Alert
            severity="error"
            onClose={() => {
              setError(null);
            }}
          >
            {error}
          </Alert>
        )}

        <Box
          sx={{
            display: 'grid',
            gridTemplateColumns: {
              xs: 'repeat(2, minmax(0, 1fr))',
              lg: 'repeat(4, minmax(0, 1fr))',
            },
            gap: 1.5,
          }}
        >
          {[
            ['Needs review', statistics.needs_review ?? 0],
            ['Imported', statistics.imported ?? 0],
            ['Failed', statistics.failed ?? 0],
            ['Received', statistics.received ?? 0],
          ].map(([label, value]) => (
            <Card
              key={String(label)}
              variant="outlined"
            >
              <CardContent>
                <Typography
                  variant="h4"
                  sx={{ fontWeight: 760 }}
                >
                  {value}
                </Typography>
                <Typography
                  color="text.secondary"
                >
                  {label}
                </Typography>
              </CardContent>
            </Card>
          ))}
        </Box>

        <Stack
          direction={{
            xs: 'column',
            md: 'row',
          }}
          spacing={2}
        >
          <TextField
            size="small"
            label="Search sample, test or analyzer"
            value={search}
            onChange={(event) => {
              setSearch(
                event.target.value
              );
            }}
            sx={{ minWidth: 320 }}
          />

          <TextField
            select
            size="small"
            label="Status"
            value={status}
            onChange={(event) => {
              setStatus(
                event.target.value
              );
            }}
            sx={{ minWidth: 200 }}
          >
            <MenuItem value="">
              All statuses
            </MenuItem>

            {statuses.map((value) => (
              <MenuItem
                key={value}
                value={value}
              >
                {humanize(value)}
              </MenuItem>
            ))}
          </TextField>
        </Stack>

        <Stack spacing={1.25}>
          {imports.map((item) => (
            <Paper
              key={item.uuid}
              variant="outlined"
              sx={{ p: 2 }}
            >
              <Stack
                direction={{
                  xs: 'column',
                  md: 'row',
                }}
                spacing={2}
                sx={{
                  justifyContent: 'space-between',
                  alignItems: {
                    md: 'center',
                  },
                }}
              >
                <Box>
                  <Stack
                    direction="row"
                    spacing={1}
                    sx={{
                      alignItems: 'center',
                      flexWrap: 'wrap',
                    }}
                  >
                    <Typography
                      sx={{ fontWeight: 700 }}
                    >
                      {item.source_sample_identifier}
                    </Typography>

                    <Chip
                      size="small"
                      color={
                        statusColor(item.status)
                      }
                      label={
                        humanize(item.status)
                      }
                    />
                  </Stack>

                  <Typography
                    variant="body2"
                    color="text.secondary"
                    sx={{ mt: 0.5 }}
                  >
                    {item.instrument?.name ?? 'Analyzer'}
                    {' · '}
                    {item.instrument_test_code}
                    {item.order_item?.test_name
                      ? ` → ${item.order_item.test_name}`
                      : ''}
                    {item.order_item?.order_number
                      ? ` · ${item.order_item.order_number}`
                      : ''}
                  </Typography>
                </Box>

                <Stack
                  direction="row"
                  spacing={1}
                >
                  <Button
                    startIcon={<RuleRounded />}
                    onClick={() => {
                      openDetail(item);
                    }}
                  >
                    Review
                  </Button>

                  {canReprocess
                    && [
                      'needs_review',
                      'failed',
                    ].includes(item.status)
                    && (
                      <Button
                        startIcon={<ReplayRounded />}
                        onClick={() => {
                          reprocessMutation.mutate(
                            item
                          );
                        }}
                      >
                        Reprocess
                      </Button>
                    )}
                </Stack>
              </Stack>
            </Paper>
          ))}

          {!importsQuery.isPending
            && imports.length === 0
            && (
              <Paper
                variant="outlined"
                sx={{ p: 4 }}
              >
                <Typography
                  align="center"
                  color="text.secondary"
                >
                  No analyzer imports match the current filters.
                </Typography>
              </Paper>
            )}
        </Stack>
      </Stack>

      <Dialog
        open={detailOpen}
        onClose={() => {
          setDetailOpen(false);
          setSelectedUuid(null);
        }}
        fullWidth
        maxWidth="md"
      >
        <DialogTitle>
          Analyzer import review
        </DialogTitle>

        <DialogContent>
          {detailQuery.isPending && (
            <Typography>
              Loading analyzer import…
            </Typography>
          )}

          {detail && (
            <Stack
              spacing={2}
              sx={{ mt: 1 }}
            >
              <Stack
                direction="row"
                spacing={1}
                sx={{
                  alignItems: 'center',
                }}
              >
                <Typography
                  variant="h6"
                  sx={{ fontWeight: 720 }}
                >
                  {detail.source_sample_identifier}
                </Typography>

                <Chip
                  size="small"
                  color={
                    statusColor(
                      detail.status
                    )
                  }
                  label={
                    humanize(
                      detail.status
                    )
                  }
                />
              </Stack>

              <Box
                sx={{
                  display: 'grid',
                  gridTemplateColumns: {
                    xs: '1fr',
                    sm: 'repeat(2, minmax(0, 1fr))',
                  },
                  gap: 1.5,
                }}
              >
                <Box>
                  <Typography
                    variant="caption"
                    color="text.secondary"
                  >
                    Analyzer
                  </Typography>
                  <Typography>
                    {detail.instrument?.name ?? '—'}
                  </Typography>
                </Box>

                <Box>
                  <Typography
                    variant="caption"
                    color="text.secondary"
                  >
                    Analyzer test code
                  </Typography>
                  <Typography>
                    {detail.instrument_test_code}
                  </Typography>
                </Box>

                <Box>
                  <Typography
                    variant="caption"
                    color="text.secondary"
                  >
                    Specimen
                  </Typography>
                  <Typography>
                    {detail.specimen?.accession_number ?? 'Unresolved'}
                  </Typography>
                </Box>

                <Box>
                  <Typography
                    variant="caption"
                    color="text.secondary"
                  >
                    Order item
                  </Typography>
                  <Typography>
                    {detail.order_item?.test_name ?? 'Unresolved'}
                  </Typography>
                </Box>
              </Box>

              {detail.review_note && (
                <Alert severity="info">
                  Review note: {detail.review_note}
                </Alert>
              )}

              <Divider />

              <Box>
                <Typography
                  sx={{ fontWeight: 700, mb: 1 }}
                >
                  Validation issues
                </Typography>

                <Paper
                  variant="outlined"
                  component="pre"
                  sx={{
                    p: 1.5,
                    m: 0,
                    overflow: 'auto',
                    whiteSpace: 'pre-wrap',
                    fontSize: '0.78rem',
                  }}
                >
                  {jsonText(
                    detail.validation_issues
                  )}
                </Paper>
              </Box>

              <Box>
                <Typography
                  sx={{ fontWeight: 700, mb: 1 }}
                >
                  Normalized analyzer values
                </Typography>

                <Paper
                  variant="outlined"
                  component="pre"
                  sx={{
                    p: 1.5,
                    m: 0,
                    overflow: 'auto',
                    whiteSpace: 'pre-wrap',
                    fontSize: '0.78rem',
                  }}
                >
                  {jsonText(
                    detail.normalized_results
                  )}
                </Paper>
              </Box>

              <Box>
                <Typography
                  sx={{ fontWeight: 700, mb: 1 }}
                >
                  Audit timeline
                </Typography>

                <Stack spacing={1}>
                  {(detail.events ?? []).map(
                    (event, index) => (
                      <Paper
                        key={
                          event.uuid
                          ?? `${event.event_type}-${index}`
                        }
                        variant="outlined"
                        sx={{ p: 1.25 }}
                      >
                        <Typography
                          sx={{ fontWeight: 650 }}
                        >
                          {humanize(
                            event.event_type
                          )}
                        </Typography>

                        <Typography
                          variant="caption"
                          color="text.secondary"
                        >
                          {event.from_status ?? '—'}
                          {' → '}
                          {event.to_status ?? '—'}
                          {event.occurred_at
                            ? ` · ${new Date(event.occurred_at).toLocaleString()}`
                            : ''}
                        </Typography>
                      </Paper>
                    )
                  )}

                  {(detail.events ?? []).length === 0 && (
                    <Typography
                      color="text.secondary"
                    >
                      No audit events are available.
                    </Typography>
                  )}
                </Stack>
              </Box>
            </Stack>
          )}
        </DialogContent>

        <DialogActions>
          {detail?.test_result && (
            <Button
              onClick={() => {
                navigate(
                  `/test-results/${detail.test_result?.uuid}`
                );
              }}
            >
              Open result
            </Button>
          )}

          {detail
            && canReprocess
            && [
              'needs_review',
              'failed',
            ].includes(detail.status)
            && (
              <Button
                startIcon={<ReplayRounded />}
                onClick={() => {
                  reprocessMutation.mutate(
                    detail
                  );
                }}
              >
                Reprocess
              </Button>
            )}

          {detail
            && canReview
            && ![
              'imported',
              'rejected',
            ].includes(detail.status)
            && (
              <Button
                color="error"
                onClick={() => {
                  setRejectNote('');
                  setRejectOpen(true);
                }}
              >
                Reject
              </Button>
            )}

          <Button
            variant="contained"
            onClick={() => {
              setDetailOpen(false);
              setSelectedUuid(null);
            }}
          >
            Close
          </Button>
        </DialogActions>
      </Dialog>

      <Dialog
        open={rejectOpen}
        onClose={() => {
          setRejectOpen(false);
        }}
        fullWidth
        maxWidth="sm"
      >
        <DialogTitle>
          Reject analyzer import
        </DialogTitle>

        <DialogContent>
          <TextField
            autoFocus
            fullWidth
            multiline
            minRows={3}
            label="Review note"
            value={rejectNote}
            onChange={(event) => {
              setRejectNote(
                event.target.value
              );
            }}
            sx={{ mt: 1 }}
          />
        </DialogContent>

        <DialogActions>
          <Button
            onClick={() => {
              setRejectOpen(false);
            }}
          >
            Cancel
          </Button>

          <Button
            color="error"
            variant="contained"
            disabled={
              rejectMutation.isPending
              || rejectNote.trim().length < 5
            }
            onClick={() => {
              rejectMutation.mutate();
            }}
          >
            Reject import
          </Button>
        </DialogActions>
      </Dialog>
    </Layout>
  );
};

export default AnalyzerImports;
