import {
  AddRounded,
  EditRounded,
  MedicalServicesRounded,
  SearchRounded,
} from '@mui/icons-material';

import {
  Alert,
  Box,
  Button,
  Chip,
  CircularProgress,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  IconButton,
  InputAdornment,
  MenuItem,
  Paper,
  Stack,
  Switch,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TablePagination,
  TableRow,
  TextField,
  Tooltip,
  Typography,
} from '@mui/material';

import {
  useCallback,
  useEffect,
  useMemo,
  useState,
} from 'react';

import Layout from '../components/Layout/Layout';

import {
  createReferringClinician,
  fetchReferringClinicians,
  setReferringClinicianActive,
  updateReferringClinician,
} from '../features/clinicians/api/referringClinicianApi';

import type {
  ReferringClinicianPayload,
  ReferringClinicianResource,
} from '../features/clinicians/types/referringClinician.types';

type StatusFilter =
  | 'all'
  | 'active'
  | 'inactive';

const emptyForm: ReferringClinicianPayload = {
  name: '',
  email: '',
  phone: '',
  organisation: '',
  registration_number: '',
  specialty: '',
  is_active: true,
};

const errorMessage = (
  error: unknown,
  fallback: string
): string => {
  if (
    typeof error === 'object'
    && error !== null
    && 'response' in error
  ) {
    const response = (
      error as {
        response?: {
          data?: {
            message?: unknown;
          };
        };
      }
    ).response;

    if (
      typeof response?.data?.message
      === 'string'
    ) {
      return response.data.message;
    }
  }

  return fallback;
};

const ReferringClinicians = () => {
  const [
    clinicians,
    setClinicians,
  ] = useState<
    ReferringClinicianResource[]
  >([]);

  const [
    loading,
    setLoading,
  ] = useState(true);

  const [
    saving,
    setSaving,
  ] = useState(false);

  const [
    error,
    setError,
  ] = useState<string | null>(null);

  const [
    search,
    setSearch,
  ] = useState('');

  const [
    statusFilter,
    setStatusFilter,
  ] = useState<StatusFilter>('all');

  const [
    page,
    setPage,
  ] = useState(0);

  const [
    rowsPerPage,
    setRowsPerPage,
  ] = useState(10);

  const [
    total,
    setTotal,
  ] = useState(0);

  const [
    dialogOpen,
    setDialogOpen,
  ] = useState(false);

  const [
    editing,
    setEditing,
  ] = useState<
    ReferringClinicianResource | null
  >(null);

  const [
    form,
    setForm,
  ] = useState<ReferringClinicianPayload>(
    emptyForm
  );

  const activeParam = useMemo<
    0 | 1 | undefined
  >(
    () => (
      statusFilter === 'all'
        ? undefined
        : statusFilter === 'active'
          ? 1
          : 0
    ),
    [statusFilter]
  );

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);

    try {
      const result =
        await fetchReferringClinicians({
          page: page + 1,
          per_page: rowsPerPage,
          search:
            search.trim() || undefined,
          is_active: activeParam,
        });

      setClinicians(result.data);
      setTotal(result.meta.total);
    } catch (requestError: unknown) {
      setError(errorMessage(
        requestError,
        'Failed to load referring clinicians.'
      ));
    } finally {
      setLoading(false);
    }
  }, [
    activeParam,
    page,
    rowsPerPage,
    search,
  ]);

  useEffect(() => {
    const timer = window.setTimeout(() => {
      void load();
    }, 180);

    return () => {
      window.clearTimeout(timer);
    };
  }, [load]);

  const openCreate = () => {
    setEditing(null);
    setForm(emptyForm);
    setError(null);
    setDialogOpen(true);
  };

  const openEdit = (
    clinician: ReferringClinicianResource
  ) => {
    setEditing(clinician);
    setForm({
      name: clinician.name,
      email: clinician.email ?? '',
      phone: clinician.phone ?? '',
      organisation:
        clinician.organisation ?? '',
      registration_number:
        clinician.registration_number ?? '',
      specialty:
        clinician.specialty ?? '',
      is_active: clinician.is_active,
    });
    setError(null);
    setDialogOpen(true);
  };

  const closeDialog = () => {
    if (saving) {
      return;
    }

    setDialogOpen(false);
  };

  const submit = async () => {
    const name = form.name.trim();

    if (!name) {
      setError(
        'Clinician name is required.'
      );
      return;
    }

    setSaving(true);
    setError(null);

    const payload:
      ReferringClinicianPayload = {
        name,
        email:
          form.email?.trim() || null,
        phone:
          form.phone?.trim() || null,
        organisation:
          form.organisation?.trim() || null,
        registration_number:
          form.registration_number?.trim()
          || null,
        specialty:
          form.specialty?.trim() || null,
        is_active:
          form.is_active ?? true,
      };

    try {
      if (editing) {
        await updateReferringClinician(
          editing.uuid,
          payload
        );
      } else {
        await createReferringClinician(
          payload
        );
      }

      setDialogOpen(false);
      await load();
    } catch (requestError: unknown) {
      setError(errorMessage(
        requestError,
        editing
          ? 'Failed to update the referring clinician.'
          : 'Failed to create the referring clinician.'
      ));
    } finally {
      setSaving(false);
    }
  };

  const toggleActive = async (
    clinician: ReferringClinicianResource
  ) => {
    setError(null);

    try {
      await setReferringClinicianActive(
        clinician.uuid,
        !clinician.is_active
      );

      await load();
    } catch (requestError: unknown) {
      setError(errorMessage(
        requestError,
        'Failed to update clinician status.'
      ));
    }
  };

  return (
    <Layout title="Referring clinicians">
      <Stack
        spacing={2.5}
        sx={{ width: '100%' }}
      >
        <Box
          sx={{
            display: 'flex',
            gap: 2,
            alignItems: {
              xs: 'stretch',
              sm: 'center',
            },
            justifyContent:
              'space-between',
            flexDirection: {
              xs: 'column',
              sm: 'row',
            },
          }}
        >
          <Box>
            <Typography
              variant="h4"
              sx={{ fontWeight: 760 }}
            >
              Referring clinicians
            </Typography>

            <Typography
              color="text.secondary"
              sx={{ mt: 0.75 }}
            >
              Maintain the tenant-owned
              clinician directory used for
              durable laboratory-order linkage
              and future clinician portal
              access.
            </Typography>
          </Box>

          <Button
            variant="contained"
            startIcon={<AddRounded />}
            onClick={openCreate}
            sx={{
              alignSelf: {
                xs: 'stretch',
                sm: 'center',
              },
            }}
          >
            Add clinician
          </Button>
        </Box>

        {error && (
          <Alert
            severity="error"
            onClose={() => setError(null)}
          >
            {error}
          </Alert>
        )}

        <Paper
          variant="outlined"
          sx={{ overflow: 'hidden' }}
        >
          <Box
            sx={{
              p: 2,
              display: 'flex',
              gap: 1.5,
              flexDirection: {
                xs: 'column',
                md: 'row',
              },
            }}
          >
            <TextField
              fullWidth
              value={search}
              placeholder={
                'Search name, organisation, registration number…'
              }
              onChange={(event) => {
                setPage(0);
                setSearch(
                  event.target.value
                );
              }}
              slotProps={{
                input: {
                  startAdornment: (
                    <InputAdornment position="start">
                      <SearchRounded
                        sx={{
                          color:
                            'text.secondary',
                        }}
                      />
                    </InputAdornment>
                  ),
                },
              }}
            />

            <TextField
              select
              label="Status"
              value={statusFilter}
              onChange={(event) => {
                setPage(0);
                setStatusFilter(
                  event.target.value as StatusFilter
                );
              }}
              sx={{
                minWidth: {
                  xs: '100%',
                  md: 180,
                },
              }}
            >
              <MenuItem value="all">
                All clinicians
              </MenuItem>
              <MenuItem value="active">
                Active
              </MenuItem>
              <MenuItem value="inactive">
                Inactive
              </MenuItem>
            </TextField>
          </Box>

          <TableContainer>
            <Table>
              <TableHead>
                <TableRow>
                  <TableCell>
                    Clinician
                  </TableCell>
                  <TableCell>
                    Organisation
                  </TableCell>
                  <TableCell>
                    Registration
                  </TableCell>
                  <TableCell>
                    Status
                  </TableCell>
                  <TableCell align="right">
                    Actions
                  </TableCell>
                </TableRow>
              </TableHead>

              <TableBody>
                {loading ? (
                  <TableRow>
                    <TableCell
                      colSpan={5}
                      align="center"
                      sx={{ py: 8 }}
                    >
                      <CircularProgress
                        size={30}
                      />
                    </TableCell>
                  </TableRow>
                ) : clinicians.length === 0 ? (
                  <TableRow>
                    <TableCell
                      colSpan={5}
                      align="center"
                      sx={{ py: 8 }}
                    >
                      <MedicalServicesRounded
                        sx={{
                          mb: 1,
                          color:
                            'text.disabled',
                          fontSize: 38,
                        }}
                      />

                      <Typography
                        color="text.secondary"
                      >
                        No referring clinicians
                        match the current
                        filters.
                      </Typography>
                    </TableCell>
                  </TableRow>
                ) : clinicians.map(
                  (clinician) => (
                    <TableRow
                      key={clinician.uuid}
                      hover
                    >
                      <TableCell>
                        <Typography
                          variant="body2"
                          sx={{
                            fontWeight: 650,
                          }}
                        >
                          {clinician.name}
                        </Typography>

                        <Typography
                          variant="caption"
                          color="text.secondary"
                        >
                          {clinician.specialty
                            ?? clinician.email
                            ?? 'No specialty or email recorded'}
                        </Typography>
                      </TableCell>

                      <TableCell>
                        {clinician.organisation
                          ?? 'Not recorded'}
                      </TableCell>

                      <TableCell>
                        {clinician
                          .registration_number
                          ?? 'Not recorded'}
                      </TableCell>

                      <TableCell>
                        <Chip
                          size="small"
                          label={
                            clinician.is_active
                              ? 'Active'
                              : 'Inactive'
                          }
                          color={
                            clinician.is_active
                              ? 'success'
                              : 'default'
                          }
                        />
                      </TableCell>

                      <TableCell
                        align="right"
                      >
                        <Tooltip
                          title="Edit clinician"
                        >
                          <IconButton
                            size="small"
                            onClick={() => {
                              openEdit(
                                clinician
                              );
                            }}
                          >
                            <EditRounded
                              fontSize="small"
                            />
                          </IconButton>
                        </Tooltip>

                        <Tooltip
                          title={
                            clinician.is_active
                              ? 'Deactivate clinician'
                              : 'Activate clinician'
                          }
                        >
                          <Switch
                            size="small"
                            checked={
                              clinician.is_active
                            }
                            onChange={() => {
                              void toggleActive(
                                clinician
                              );
                            }}
                          />
                        </Tooltip>
                      </TableCell>
                    </TableRow>
                  )
                )}
              </TableBody>
            </Table>
          </TableContainer>

          <TablePagination
            component="div"
            count={total}
            page={page}
            rowsPerPage={rowsPerPage}
            rowsPerPageOptions={[
              10,
              25,
              50,
            ]}
            onPageChange={(
              _,
              nextPage
            ) => {
              setPage(nextPage);
            }}
            onRowsPerPageChange={(
              event
            ) => {
              setRowsPerPage(
                Number(
                  event.target.value
                )
              );
              setPage(0);
            }}
          />
        </Paper>
      </Stack>

      <Dialog
        open={dialogOpen}
        onClose={closeDialog}
        fullWidth
        maxWidth="md"
      >
        <DialogTitle>
          {editing
            ? 'Edit referring clinician'
            : 'Add referring clinician'}
        </DialogTitle>

        <DialogContent>
          <Box
            sx={{
              pt: 1,
              display: 'grid',
              gap: 2,
              gridTemplateColumns: {
                xs: '1fr',
                md:
                  'repeat(2, minmax(0, 1fr))',
              },
            }}
          >
            <TextField
              required
              label="Clinician name"
              value={form.name}
              onChange={(event) => {
                setForm((current) => ({
                  ...current,
                  name:
                    event.target.value,
                }));
              }}
            />

            <TextField
              label="Specialty"
              value={form.specialty ?? ''}
              onChange={(event) => {
                setForm((current) => ({
                  ...current,
                  specialty:
                    event.target.value,
                }));
              }}
            />

            <TextField
              type="email"
              label="Email"
              value={form.email ?? ''}
              onChange={(event) => {
                setForm((current) => ({
                  ...current,
                  email:
                    event.target.value,
                }));
              }}
            />

            <TextField
              label="Phone"
              value={form.phone ?? ''}
              onChange={(event) => {
                setForm((current) => ({
                  ...current,
                  phone:
                    event.target.value,
                }));
              }}
            />

            <TextField
              label="Organisation / facility"
              value={
                form.organisation ?? ''
              }
              onChange={(event) => {
                setForm((current) => ({
                  ...current,
                  organisation:
                    event.target.value,
                }));
              }}
            />

            <TextField
              label="Registration number"
              value={
                form.registration_number
                ?? ''
              }
              onChange={(event) => {
                setForm((current) => ({
                  ...current,
                  registration_number:
                    event.target.value,
                }));
              }}
            />

            <Box
              sx={{
                gridColumn: {
                  xs: 'auto',
                  md: '1 / -1',
                },
                display: 'flex',
                alignItems: 'center',
                justifyContent:
                  'space-between',
                gap: 2,
                px: 0.5,
              }}
            >
              <Box>
                <Typography
                  variant="body2"
                  sx={{
                    fontWeight: 650,
                  }}
                >
                  Active clinician
                </Typography>

                <Typography
                  variant="caption"
                  color="text.secondary"
                >
                  Inactive clinicians
                  cannot be selected for
                  new laboratory orders.
                </Typography>
              </Box>

              <Switch
                checked={
                  form.is_active ?? true
                }
                onChange={(
                  _,
                  checked
                ) => {
                  setForm(
                    (current) => ({
                      ...current,
                      is_active: checked,
                    })
                  );
                }}
              />
            </Box>
          </Box>
        </DialogContent>

        <DialogActions sx={{ p: 2.5 }}>
          <Button
            onClick={closeDialog}
            disabled={saving}
          >
            Cancel
          </Button>

          <Button
            variant="contained"
            disabled={saving}
            onClick={() => {
              void submit();
            }}
          >
            {saving
              ? 'Saving…'
              : editing
                ? 'Save changes'
                : 'Add clinician'}
          </Button>
        </DialogActions>
      </Dialog>
    </Layout>
  );
};

export default ReferringClinicians;
