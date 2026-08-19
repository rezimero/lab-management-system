import {
  AddRounded,
  CableRounded,
  KeyRounded,
  RefreshRounded,
  SettingsInputComponentRounded,
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
  FormControlLabel,
  MenuItem,
  Paper,
  Stack,
  Switch,
  TextField,
  Typography,
} from '@mui/material';

import {
  useMutation,
  useQuery,
  useQueryClient,
} from '@tanstack/react-query';

import {
  useMemo,
  useState,
} from 'react';

import Layout from '../../../components/Layout/Layout';
import {
  useAuth,
} from '../../../contexts/AuthContext';

import {
  fetchBranches,
} from '../../commercial/api/commercialApi';

import {
  apiErrorMessage,
} from '../../test-results/utils/apiErrorMessage';

import {
  createAnalyzer,
  deactivateAnalyzer,
  fetchAnalyzerLabTests,
  fetchAnalyzerParameterMappings,
  fetchAnalyzers,
  fetchAnalyzerTestMappings,
  rotateAnalyzerCredential,
  saveAnalyzerParameterMapping,
  saveAnalyzerTestMapping,
} from '../api/analyzerApi';

import {
  roleAllowsAnalyzerAction,
} from '../permissions/analyzerPermissions';

import type {
  AnalyzerInstrument,
  AnalyzerInstrumentPayload,
  AnalyzerLabTest,
  AnalyzerParameterMappingPayload,
  AnalyzerTestMapping,
  AnalyzerTestMappingPayload,
} from '../types/analyzer.types';

const emptyAnalyzer: AnalyzerInstrumentPayload = {
  branch_uuid: '',
  code: '',
  name: '',
  manufacturer: '',
  model: '',
  serial_number: '',
  adapter_key: 'canonical_json_v1',
  transport_mode: 'gateway',
  notes: '',
};

const displayDate = (
  value?: string | null
): string => {
  if (!value) {
    return 'Never';
  }

  const date = new Date(value);

  return Number.isNaN(date.getTime())
    ? '—'
    : date.toLocaleString();
};

const Analyzers = () => {
  const auth = useAuth();
  const queryClient = useQueryClient();

  const [
    search,
    setSearch,
  ] = useState('');

  const [
    createOpen,
    setCreateOpen,
  ] = useState(false);

  const [
    analyzerForm,
    setAnalyzerForm,
  ] = useState<AnalyzerInstrumentPayload>(
    emptyAnalyzer
  );

  const [
    selectedAnalyzer,
    setSelectedAnalyzer,
  ] = useState<AnalyzerInstrument | null>(null);

  const [
    mappingOpen,
    setMappingOpen,
  ] = useState(false);

  const [
    mappingForm,
    setMappingForm,
  ] = useState<AnalyzerTestMappingPayload>({
    lab_test_uuid: '',
    instrument_test_code: '',
    instrument_test_name: '',
    is_active: true,
  });

  const [
    selectedMapping,
    setSelectedMapping,
  ] = useState<AnalyzerTestMapping | null>(null);

  const [
    parameterOpen,
    setParameterOpen,
  ] = useState(false);

  const [
    parameterForm,
    setParameterForm,
  ] = useState<AnalyzerParameterMappingPayload>({
    instrument_parameter_code: '',
    instrument_parameter_name: '',
    target_parameter_name: '',
    expected_unit: '',
    is_required: true,
    is_active: true,
  });

  const [
    machineToken,
    setMachineToken,
  ] = useState('');

  const [
    tokenOpen,
    setTokenOpen,
  ] = useState(false);

  const [
    error,
    setError,
  ] = useState<string | null>(null);

  const canManage = roleAllowsAnalyzerAction(
    auth.user?.role,
    'manage_instruments'
  );

  const canManageMappings = roleAllowsAnalyzerAction(
    auth.user?.role,
    'manage_mappings'
  );

  const canRotate = roleAllowsAnalyzerAction(
    auth.user?.role,
    'rotate_credentials'
  );

  const analyzersQuery = useQuery({
    queryKey: [
      'analyzers',
      search,
    ],
    queryFn: () => fetchAnalyzers(search),
  });

  const branchesQuery = useQuery({
    queryKey: ['commercial-branches'],
    queryFn: fetchBranches,
  });

  const labTestsQuery = useQuery({
    queryKey: ['analyzer-lab-tests'],
    queryFn: fetchAnalyzerLabTests,
  });

  const mappingsQuery = useQuery({
    queryKey: [
      'analyzer-test-mappings',
      selectedAnalyzer?.uuid,
    ],
    queryFn: () => fetchAnalyzerTestMappings(
      selectedAnalyzer?.uuid ?? ''
    ),
    enabled: Boolean(selectedAnalyzer?.uuid),
  });

  const parametersQuery = useQuery({
    queryKey: [
      'analyzer-parameter-mappings',
      selectedAnalyzer?.uuid,
      selectedMapping?.uuid,
    ],
    queryFn: () => fetchAnalyzerParameterMappings(
      selectedAnalyzer?.uuid ?? '',
      selectedMapping?.uuid ?? ''
    ),
    enabled: Boolean(
      selectedAnalyzer?.uuid
      && selectedMapping?.uuid
    ),
  });

  const processingBranches = useMemo(
    () => (
      branchesQuery.data?.data.filter(
        (branch) => (
          branch.type === 'laboratory'
          && branch.is_active
        )
      ) ?? []
    ),
    [branchesQuery.data]
  );

  const activeLabTests = useMemo(
    () => (
      labTestsQuery.data?.filter(
        (test) => test.is_active
      ) ?? []
    ),
    [labTestsQuery.data]
  );

  const selectedLabTest = useMemo(
    () => activeLabTests.find(
      (test) => (
        test.uuid === mappingForm.lab_test_uuid
      )
    ),
    [
      activeLabTests,
      mappingForm.lab_test_uuid,
    ]
  );

  const createMutation = useMutation({
    mutationFn: () => createAnalyzer(
      analyzerForm
    ),
    onSuccess: async (result) => {
      setCreateOpen(false);
      setAnalyzerForm(emptyAnalyzer);
      setMachineToken(
        result.machine_token
      );
      setTokenOpen(true);
      setError(null);

      await queryClient.invalidateQueries({
        queryKey: ['analyzers'],
      });
    },
    onError: (requestError: unknown) => {
      setError(apiErrorMessage(
        requestError,
        'Failed to create the analyzer.'
      ));
    },
  });

  const rotateMutation = useMutation({
    mutationFn: (
      analyzer: AnalyzerInstrument
    ) => rotateAnalyzerCredential(
      analyzer.uuid
    ),
    onSuccess: async (result) => {
      setMachineToken(
        result.machine_token
      );
      setTokenOpen(true);
      setError(null);

      await queryClient.invalidateQueries({
        queryKey: ['analyzers'],
      });
    },
    onError: (requestError: unknown) => {
      setError(apiErrorMessage(
        requestError,
        'Failed to rotate the analyzer credential.'
      ));
    },
  });

  const deactivateMutation = useMutation({
    mutationFn: (
      analyzer: AnalyzerInstrument
    ) => deactivateAnalyzer(
      analyzer.uuid
    ),
    onSuccess: async () => {
      setError(null);
      await queryClient.invalidateQueries({
        queryKey: ['analyzers'],
      });
    },
    onError: (requestError: unknown) => {
      setError(apiErrorMessage(
        requestError,
        'Failed to deactivate the analyzer.'
      ));
    },
  });

  const testMappingMutation = useMutation({
    mutationFn: () => {
      if (!selectedAnalyzer) {
        throw new Error(
          'Select an analyzer before saving a mapping.'
        );
      }

      return saveAnalyzerTestMapping(
        selectedAnalyzer.uuid,
        mappingForm
      );
    },
    onSuccess: async () => {
      setMappingForm({
        lab_test_uuid: '',
        instrument_test_code: '',
        instrument_test_name: '',
        is_active: true,
      });
      setError(null);

      await queryClient.invalidateQueries({
        queryKey: [
          'analyzer-test-mappings',
          selectedAnalyzer?.uuid,
        ],
      });
    },
    onError: (requestError: unknown) => {
      setError(apiErrorMessage(
        requestError,
        'Failed to save the analyzer test mapping.'
      ));
    },
  });

  const parameterMutation = useMutation({
    mutationFn: () => {
      if (
        !selectedAnalyzer
        || !selectedMapping
      ) {
        throw new Error(
          'Select an analyzer test mapping first.'
        );
      }

      return saveAnalyzerParameterMapping(
        selectedAnalyzer.uuid,
        selectedMapping.uuid,
        parameterForm
      );
    },
    onSuccess: async () => {
      setParameterForm({
        instrument_parameter_code: '',
        instrument_parameter_name: '',
        target_parameter_name: '',
        expected_unit: '',
        is_required: true,
        is_active: true,
      });
      setError(null);

      await queryClient.invalidateQueries({
        queryKey: [
          'analyzer-parameter-mappings',
          selectedAnalyzer?.uuid,
          selectedMapping?.uuid,
        ],
      });
    },
    onError: (requestError: unknown) => {
      setError(apiErrorMessage(
        requestError,
        'Failed to save the parameter mapping.'
      ));
    },
  });

  const analyzers =
    analyzersQuery.data?.data ?? [];

  const mappings =
    mappingsQuery.data?.data ?? [];

  const parameters =
    parametersQuery.data?.data ?? [];

  const openMappings = (
    analyzer: AnalyzerInstrument
  ) => {
    setSelectedAnalyzer(analyzer);
    setSelectedMapping(null);
    setMappingOpen(true);
    setError(null);
  };

  const openParameters = (
    mapping: AnalyzerTestMapping
  ) => {
    setSelectedMapping(mapping);
    setParameterForm({
      instrument_parameter_code: '',
      instrument_parameter_name: '',
      target_parameter_name: '',
      expected_unit: '',
      is_required: true,
      is_active: true,
    });
    setParameterOpen(true);
    setError(null);
  };

  return (
    <Layout title="Analyzer instruments">
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
              Analyzer instruments
            </Typography>

            <Typography
              color="text.secondary"
              sx={{ mt: 0.7 }}
            >
              Register instruments, manage one-time machine credentials and map analyzer codes to the Rezhel catalogue.
            </Typography>
          </Box>

          <Stack
            direction="row"
            spacing={1}
          >
            <Button
              startIcon={
                <RefreshRounded />
              }
              onClick={() => {
                void analyzersQuery.refetch();
              }}
            >
              Refresh
            </Button>

            {canManage && (
              <Button
                variant="contained"
                startIcon={<AddRounded />}
                onClick={() => {
                  setAnalyzerForm(
                    emptyAnalyzer
                  );
                  setCreateOpen(true);
                  setError(null);
                }}
              >
                Add analyzer
              </Button>
            )}
          </Stack>
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

        <TextField
          size="small"
          label="Search analyzers"
          value={search}
          onChange={(event) => {
            setSearch(
              event.target.value
            );
          }}
          sx={{ maxWidth: 420 }}
        />

        <Box
          sx={{
            display: 'grid',
            gridTemplateColumns: {
              xs: '1fr',
              lg: 'repeat(2, minmax(0, 1fr))',
            },
            gap: 2,
          }}
        >
          {analyzers.map((analyzer) => (
            <Card
              key={analyzer.uuid}
              variant="outlined"
            >
              <CardContent>
                <Stack spacing={2}>
                  <Box
                    sx={{
                      display: 'flex',
                      justifyContent: 'space-between',
                      gap: 2,
                    }}
                  >
                    <Box>
                      <Typography
                        variant="h6"
                        sx={{ fontWeight: 720 }}
                      >
                        {analyzer.name}
                      </Typography>

                      <Typography
                        variant="body2"
                        color="text.secondary"
                      >
                        {analyzer.code}
                        {analyzer.manufacturer
                          ? ` · ${analyzer.manufacturer}`
                          : ''}
                        {analyzer.model
                          ? ` ${analyzer.model}`
                          : ''}
                      </Typography>
                    </Box>

                    <Chip
                      size="small"
                      color={
                        analyzer.is_active
                          ? 'success'
                          : 'default'
                      }
                      label={
                        analyzer.is_active
                          ? 'Active'
                          : 'Inactive'
                      }
                    />
                  </Box>

                  <Divider />

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
                        Processing branch
                      </Typography>
                      <Typography>
                        {analyzer.branch?.name ?? '—'}
                      </Typography>
                    </Box>

                    <Box>
                      <Typography
                        variant="caption"
                        color="text.secondary"
                      >
                        Transport
                      </Typography>
                      <Typography>
                        {analyzer.transport_mode === 'direct_http'
                          ? 'Direct HTTP'
                          : 'Gateway'}
                      </Typography>
                    </Box>

                    <Box>
                      <Typography
                        variant="caption"
                        color="text.secondary"
                      >
                        Last seen
                      </Typography>
                      <Typography>
                        {displayDate(
                          analyzer.last_seen_at
                        )}
                      </Typography>
                    </Box>

                    <Box>
                      <Typography
                        variant="caption"
                        color="text.secondary"
                      >
                        Test mappings
                      </Typography>
                      <Typography>
                        {analyzer.test_mappings_count ?? 0}
                      </Typography>
                    </Box>
                  </Box>

                  {analyzer.last_error_code && (
                    <Alert severity="warning">
                      Last analyzer error: {analyzer.last_error_code}
                    </Alert>
                  )}

                  <Stack
                    direction={{
                      xs: 'column',
                      sm: 'row',
                    }}
                    spacing={1}
                  >
                    <Button
                      startIcon={
                        <SettingsInputComponentRounded />
                      }
                      onClick={() => {
                        openMappings(analyzer);
                      }}
                    >
                      Mappings
                    </Button>

                    {canRotate && analyzer.is_active && (
                      <Button
                        startIcon={<KeyRounded />}
                        onClick={() => {
                          rotateMutation.mutate(
                            analyzer
                          );
                        }}
                      >
                        Rotate token
                      </Button>
                    )}

                    {canManage && analyzer.is_active && (
                      <Button
                        color="warning"
                        onClick={() => {
                          deactivateMutation.mutate(
                            analyzer
                          );
                        }}
                      >
                        Deactivate
                      </Button>
                    )}
                  </Stack>
                </Stack>
              </CardContent>
            </Card>
          ))}

          {!analyzersQuery.isPending
            && analyzers.length === 0
            && (
              <Paper
                variant="outlined"
                sx={{
                  p: 4,
                  gridColumn: '1 / -1',
                }}
              >
                <Typography
                  color="text.secondary"
                  align="center"
                >
                  No analyzer instruments match the current filter.
                </Typography>
              </Paper>
            )}
        </Box>
      </Stack>

      <Dialog
        open={createOpen}
        onClose={() => {
          setCreateOpen(false);
        }}
        fullWidth
        maxWidth="sm"
      >
        <DialogTitle>
          Register analyzer
        </DialogTitle>

        <DialogContent>
          <Stack
            spacing={2}
            sx={{ mt: 1 }}
          >
            <TextField
              select
              label="Processing laboratory"
              required
              value={
                analyzerForm.branch_uuid
              }
              onChange={(event) => {
                setAnalyzerForm({
                  ...analyzerForm,
                  branch_uuid:
                    event.target.value,
                });
              }}
            >
              {processingBranches.map(
                (branch) => (
                  <MenuItem
                    key={branch.uuid}
                    value={branch.uuid}
                  >
                    {branch.name} ({branch.code})
                  </MenuItem>
                )
              )}
            </TextField>

            <Stack
              direction={{
                xs: 'column',
                sm: 'row',
              }}
              spacing={2}
            >
              <TextField
                fullWidth
                required
                label="Analyzer code"
                value={analyzerForm.code}
                onChange={(event) => {
                  setAnalyzerForm({
                    ...analyzerForm,
                    code:
                      event.target.value.toUpperCase(),
                  });
                }}
              />

              <TextField
                fullWidth
                required
                label="Analyzer name"
                value={analyzerForm.name}
                onChange={(event) => {
                  setAnalyzerForm({
                    ...analyzerForm,
                    name:
                      event.target.value,
                  });
                }}
              />
            </Stack>

            <Stack
              direction={{
                xs: 'column',
                sm: 'row',
              }}
              spacing={2}
            >
              <TextField
                fullWidth
                label="Manufacturer"
                value={
                  analyzerForm.manufacturer ?? ''
                }
                onChange={(event) => {
                  setAnalyzerForm({
                    ...analyzerForm,
                    manufacturer:
                      event.target.value,
                  });
                }}
              />

              <TextField
                fullWidth
                label="Model"
                value={
                  analyzerForm.model ?? ''
                }
                onChange={(event) => {
                  setAnalyzerForm({
                    ...analyzerForm,
                    model:
                      event.target.value,
                  });
                }}
              />
            </Stack>

            <TextField
              label="Serial number"
              value={
                analyzerForm.serial_number ?? ''
              }
              onChange={(event) => {
                setAnalyzerForm({
                  ...analyzerForm,
                  serial_number:
                    event.target.value,
                });
              }}
            />

            <TextField
              select
              label="Transport mode"
              value={
                analyzerForm.transport_mode
              }
              onChange={(event) => {
                setAnalyzerForm({
                  ...analyzerForm,
                  transport_mode:
                    event.target.value as
                      'gateway' | 'direct_http',
                });
              }}
            >
              <MenuItem value="gateway">
                Local gateway
              </MenuItem>
              <MenuItem value="direct_http">
                Direct HTTP
              </MenuItem>
            </TextField>

            <TextField
              label="Notes"
              multiline
              minRows={2}
              value={
                analyzerForm.notes ?? ''
              }
              onChange={(event) => {
                setAnalyzerForm({
                  ...analyzerForm,
                  notes:
                    event.target.value,
                });
              }}
            />
          </Stack>
        </DialogContent>

        <DialogActions>
          <Button
            onClick={() => {
              setCreateOpen(false);
            }}
          >
            Cancel
          </Button>

          <Button
            variant="contained"
            disabled={
              createMutation.isPending
              || !analyzerForm.branch_uuid
              || !analyzerForm.code.trim()
              || !analyzerForm.name.trim()
            }
            onClick={() => {
              createMutation.mutate();
            }}
          >
            Register
          </Button>
        </DialogActions>
      </Dialog>

      <Dialog
        open={tokenOpen}
        onClose={() => {
          setTokenOpen(false);
          setMachineToken('');
        }}
        fullWidth
        maxWidth="sm"
      >
        <DialogTitle>
          One-time analyzer machine token
        </DialogTitle>

        <DialogContent>
          <Stack
            spacing={2}
            sx={{ mt: 1 }}
          >
            <Alert severity="warning">
              Copy this token into the trusted analyzer gateway now. Rezhel stores only its hash and will not show this plaintext token again.
            </Alert>

            <TextField
              label="Machine token"
              value={machineToken}
              multiline
              minRows={3}
              slotProps={{
                input: {
                  readOnly: true,
                },
              }}
            />
          </Stack>
        </DialogContent>

        <DialogActions>
          <Button
            variant="contained"
            onClick={() => {
              setTokenOpen(false);
              setMachineToken('');
            }}
          >
            I have stored it securely
          </Button>
        </DialogActions>
      </Dialog>

      <Dialog
        open={mappingOpen}
        onClose={() => {
          setMappingOpen(false);
          setSelectedAnalyzer(null);
          setSelectedMapping(null);
        }}
        fullWidth
        maxWidth="md"
      >
        <DialogTitle>
          Test mappings
          {selectedAnalyzer
            ? ` · ${selectedAnalyzer.name}`
            : ''}
        </DialogTitle>

        <DialogContent>
          <Stack
            spacing={2}
            sx={{ mt: 1 }}
          >
            {canManageMappings && (
              <Paper
                variant="outlined"
                sx={{ p: 2 }}
              >
                <Stack spacing={2}>
                  <Typography
                    sx={{ fontWeight: 700 }}
                  >
                    Add analyzer test mapping
                  </Typography>

                  <Stack
                    direction={{
                      xs: 'column',
                      md: 'row',
                    }}
                    spacing={2}
                  >
                    <TextField
                      select
                      fullWidth
                      label="Rezhel lab test"
                      value={
                        mappingForm.lab_test_uuid
                      }
                      onChange={(event) => {
                        setMappingForm({
                          ...mappingForm,
                          lab_test_uuid:
                            event.target.value,
                        });
                      }}
                    >
                      {activeLabTests.map(
                        (test) => (
                          <MenuItem
                            key={test.uuid}
                            value={test.uuid}
                          >
                            {test.name}
                          </MenuItem>
                        )
                      )}
                    </TextField>

                    <TextField
                      fullWidth
                      label="Analyzer test code"
                      value={
                        mappingForm.instrument_test_code
                      }
                      onChange={(event) => {
                        setMappingForm({
                          ...mappingForm,
                          instrument_test_code:
                            event.target.value.toUpperCase(),
                        });
                      }}
                    />

                    <TextField
                      fullWidth
                      label="Analyzer test name"
                      value={
                        mappingForm.instrument_test_name ?? ''
                      }
                      onChange={(event) => {
                        setMappingForm({
                          ...mappingForm,
                          instrument_test_name:
                            event.target.value,
                        });
                      }}
                    />
                  </Stack>

                  <Box>
                    <Button
                      variant="contained"
                      disabled={
                        testMappingMutation.isPending
                        || !mappingForm.lab_test_uuid
                        || !mappingForm.instrument_test_code.trim()
                      }
                      onClick={() => {
                        testMappingMutation.mutate();
                      }}
                    >
                      Save test mapping
                    </Button>
                  </Box>
                </Stack>
              </Paper>
            )}

            {selectedLabTest && canManageMappings && (
              <Alert severity="info">
                The selected catalogue test currently exposes {selectedLabTest.parameters?.length ?? 0} parameter(s). Parameter mappings are validated against the catalogue and later revalidated against each order-time parameter snapshot.
              </Alert>
            )}

            <Stack spacing={1.25}>
              {mappings.map((mapping) => (
                <Paper
                  key={mapping.uuid}
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
                      alignItems: {
                        md: 'center',
                      },
                      justifyContent: 'space-between',
                    }}
                  >
                    <Box>
                      <Typography
                        sx={{ fontWeight: 700 }}
                      >
                        {mapping.instrument_test_code}
                        {' → '}
                        {mapping.lab_test?.name ?? 'Unknown test'}
                      </Typography>

                      <Typography
                        variant="body2"
                        color="text.secondary"
                      >
                        {mapping.instrument_test_name ?? 'No analyzer test label'}
                        {' · '}
                        {mapping.parameter_mappings_count ?? 0} parameter mapping(s)
                      </Typography>
                    </Box>

                    <Button
                      onClick={() => {
                        openParameters(mapping);
                      }}
                    >
                      Parameters
                    </Button>
                  </Stack>
                </Paper>
              ))}

              {!mappingsQuery.isPending
                && mappings.length === 0
                && (
                  <Typography
                    color="text.secondary"
                  >
                    No test mappings have been configured.
                  </Typography>
                )}
            </Stack>
          </Stack>
        </DialogContent>

        <DialogActions>
          <Button
            onClick={() => {
              setMappingOpen(false);
            }}
          >
            Close
          </Button>
        </DialogActions>
      </Dialog>

      <Dialog
        open={parameterOpen}
        onClose={() => {
          setParameterOpen(false);
          setSelectedMapping(null);
        }}
        fullWidth
        maxWidth="md"
      >
        <DialogTitle>
          Parameter mappings
          {selectedMapping
            ? ` · ${selectedMapping.instrument_test_code}`
            : ''}
        </DialogTitle>

        <DialogContent>
          <Stack
            spacing={2}
            sx={{ mt: 1 }}
          >
            {canManageMappings && (
              <Paper
                variant="outlined"
                sx={{ p: 2 }}
              >
                <Stack spacing={2}>
                  <Typography
                    sx={{ fontWeight: 700 }}
                  >
                    Map analyzer parameter
                  </Typography>

                  <Stack
                    direction={{
                      xs: 'column',
                      md: 'row',
                    }}
                    spacing={2}
                  >
                    <TextField
                      fullWidth
                      label="Analyzer parameter code"
                      value={
                        parameterForm.instrument_parameter_code
                      }
                      onChange={(event) => {
                        setParameterForm({
                          ...parameterForm,
                          instrument_parameter_code:
                            event.target.value.toUpperCase(),
                        });
                      }}
                    />

                    <TextField
                      fullWidth
                      label="Analyzer parameter name"
                      value={
                        parameterForm.instrument_parameter_name ?? ''
                      }
                      onChange={(event) => {
                        setParameterForm({
                          ...parameterForm,
                          instrument_parameter_name:
                            event.target.value,
                        });
                      }}
                    />

                    <TextField
                      select
                      fullWidth
                      label="Rezhel parameter"
                      value={
                        parameterForm.target_parameter_name
                      }
                      onChange={(event) => {
                        const parameterName =
                          event.target.value;

                        const catalogueParameter =
                          selectedMapping?.lab_test?.parameters?.find(
                            (candidate) => (
                              candidate.name === parameterName
                            )
                          );

                        setParameterForm({
                          ...parameterForm,
                          target_parameter_name:
                            parameterName,
                          expected_unit:
                            catalogueParameter?.unit ?? '',
                        });
                      }}
                    >
                      {selectedMapping?.lab_test?.parameters
                        ?.filter(
                          (parameter) => Boolean(parameter.name)
                        )
                        .map((parameter) => (
                          <MenuItem
                            key={parameter.name}
                            value={parameter.name}
                          >
                            {parameter.name}
                          </MenuItem>
                        ))}
                    </TextField>

                    <TextField
                      fullWidth
                      label="Expected unit"
                      value={
                        parameterForm.expected_unit ?? ''
                      }
                      onChange={(event) => {
                        setParameterForm({
                          ...parameterForm,
                          expected_unit:
                            event.target.value,
                        });
                      }}
                    />
                  </Stack>

                  <FormControlLabel
                    control={
                      <Switch
                        checked={
                          parameterForm.is_required ?? true
                        }
                        onChange={(event) => {
                          setParameterForm({
                            ...parameterForm,
                            is_required:
                              event.target.checked,
                          });
                        }}
                      />
                    }
                    label="Required for automatic import"
                  />

                  <Box>
                    <Button
                      variant="contained"
                      disabled={
                        parameterMutation.isPending
                        || !parameterForm.instrument_parameter_code.trim()
                        || !parameterForm.target_parameter_name.trim()
                      }
                      onClick={() => {
                        parameterMutation.mutate();
                      }}
                    >
                      Save parameter mapping
                    </Button>
                  </Box>
                </Stack>
              </Paper>
            )}

            <Stack spacing={1}>
              {parameters.map((parameter) => (
                <Paper
                  key={parameter.uuid}
                  variant="outlined"
                  sx={{ p: 1.5 }}
                >
                  <Stack
                    direction={{
                      xs: 'column',
                      md: 'row',
                    }}
                    spacing={1}
                    sx={{
                      justifyContent: 'space-between',
                    }}
                  >
                    <Box>
                      <Typography
                        sx={{ fontWeight: 650 }}
                      >
                        {parameter.instrument_parameter_code}
                        {' → '}
                        {parameter.target_parameter_name}
                      </Typography>

                      <Typography
                        variant="body2"
                        color="text.secondary"
                      >
                        Expected unit: {parameter.expected_unit || 'not enforced'}
                      </Typography>
                    </Box>

                    <Stack
                      direction="row"
                      spacing={1}
                    >
                      <Chip
                        size="small"
                        label={
                          parameter.is_required
                            ? 'Required'
                            : 'Optional'
                        }
                      />

                      <Chip
                        size="small"
                        color={
                          parameter.is_active
                            ? 'success'
                            : 'default'
                        }
                        label={
                          parameter.is_active
                            ? 'Active'
                            : 'Inactive'
                        }
                      />
                    </Stack>
                  </Stack>
                </Paper>
              ))}

              {!parametersQuery.isPending
                && parameters.length === 0
                && (
                  <Typography
                    color="text.secondary"
                  >
                    No parameter mappings have been configured.
                  </Typography>
                )}
            </Stack>
          </Stack>
        </DialogContent>

        <DialogActions>
          <Button
            onClick={() => {
              setParameterOpen(false);
            }}
          >
            Close
          </Button>
        </DialogActions>
      </Dialog>
    </Layout>
  );
};

export default Analyzers;
