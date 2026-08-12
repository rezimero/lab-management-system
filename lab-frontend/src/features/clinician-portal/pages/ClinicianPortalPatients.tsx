import {
  useState,
} from 'react';

import SearchRounded from
  '@mui/icons-material/SearchRounded';

import {
  Alert,
  Box,
  Button,
  Card,
  CardContent,
  InputAdornment,
  Pagination,
  Stack,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  TextField,
  Typography,
} from '@mui/material';

import {
  useQuery,
} from '@tanstack/react-query';

import {
  fetchClinicianPortalPatients,
} from '../api/clinicianPortalApi';

import ClinicianPortalEmptyState from
  '../components/ClinicianPortalEmptyState';

import ClinicianPortalLoadingState from
  '../components/ClinicianPortalLoadingState';

import ClinicianPortalPageHeader from
  '../components/ClinicianPortalPageHeader';

import {
  useClinicianPortalAuth,
} from '../context/ClinicianPortalAuthContext';

import {
  clinicianPortalErrorMessage,
  formatPortalDate,
} from '../utils/clinicianPortalDisplay';

const ClinicianPortalPatients = () => {
  const auth = useClinicianPortalAuth();

  const [
    page,
    setPage,
  ] = useState(1);

  const [
    search,
    setSearch,
  ] = useState('');

  const sessionKey =
    auth.session?.account.uuid ?? 'unknown';

  const query = useQuery({
    queryKey: [
      'clinician-portal',
      sessionKey,
      'patients',
      page,
      search,
    ],
    queryFn: () => (
      fetchClinicianPortalPatients({
        page,
        per_page: 10,
        ...(search.trim()
          ? { search: search.trim() }
          : {}),
      })
    ),
  });

  const clearSearch = (): void => {
    setSearch('');
    setPage(1);
  };

  return (
    <Box>
      <ClinicianPortalPageHeader
        eyebrow="Referral relationships"
        title="Patients"
        description="View patients derived from laboratory orders that are explicitly linked to your clinician record."
      />

      <Card sx={{ mb: 2.5 }}>
        <CardContent>
          <Box
            sx={{
              display: 'flex',
              gap: 1.5,
              flexDirection: {
                xs: 'column',
                sm: 'row',
              },
              alignItems: {
                xs: 'stretch',
                sm: 'center',
              },
            }}
          >
            <TextField
              fullWidth
              label="Search patient name or MRN"
              value={search}
              onChange={(event) => {
                setSearch(event.target.value);
                setPage(1);
              }}
              slotProps={{
                input: {
                  startAdornment: (
                    <InputAdornment position="start">
                      <SearchRounded
                        sx={{
                          color: 'text.disabled',
                          fontSize: 20,
                        }}
                      />
                    </InputAdornment>
                  ),
                },
              }}
            />

            <Button
              variant="text"
              disabled={!search.trim()}
              onClick={clearSearch}
              sx={{ flexShrink: 0 }}
            >
              Clear search
            </Button>
          </Box>
        </CardContent>
      </Card>

      {query.isLoading ? (
        <ClinicianPortalLoadingState />
      ) : query.error ? (
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
          {clinicianPortalErrorMessage(
            query.error,
            'We could not load your referred patients.'
          )}
        </Alert>
      ) : (query.data?.data.length ?? 0) === 0 ? (
        <ClinicianPortalEmptyState
          title={
            search.trim()
              ? 'No patients match this search'
              : 'No linked patients yet'
          }
          description={
            search.trim()
              ? 'Try a different patient name or medical record number.'
              : 'Patients appear here only when a laboratory order is explicitly linked to your clinician record.'
          }
          {...(
            search.trim()
              ? {
                  actionLabel: 'Clear search',
                  onAction: clearSearch,
                }
              : {}
          )}
        />
      ) : (
        <>
          <TableContainer
            component={Card}
            sx={{
              display: {
                xs: 'none',
                md: 'block',
              },
            }}
          >
            <Table>
              <TableHead>
                <TableRow>
                  <TableCell>Patient</TableCell>
                  <TableCell>MRN</TableCell>
                  <TableCell>Date of birth</TableCell>
                  <TableCell>Gender</TableCell>
                </TableRow>
              </TableHead>

              <TableBody>
                {query.data?.data.map((patient) => (
                  <TableRow
                    key={
                      patient.medical_record_number
                      || `${patient.full_name}-${patient.date_of_birth}`
                    }
                    hover
                  >
                    <TableCell>
                      <Typography
                        sx={{ fontWeight: 680 }}
                      >
                        {patient.full_name
                          || [
                              patient.first_name,
                              patient.last_name,
                            ]
                              .filter(Boolean)
                              .join(' ')
                          || 'Patient'}
                      </Typography>
                    </TableCell>

                    <TableCell>
                      {patient.medical_record_number
                        || 'Not recorded'}
                    </TableCell>

                    <TableCell>
                      {formatPortalDate(
                        patient.date_of_birth,
                        false
                      )}
                    </TableCell>

                    <TableCell>
                      {patient.gender
                        || 'Not recorded'}
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </TableContainer>

          <Stack
            spacing={1.5}
            sx={{
              display: {
                xs: 'flex',
                md: 'none',
              },
            }}
          >
            {query.data?.data.map((patient) => (
              <Card
                key={
                  patient.medical_record_number
                  || `${patient.full_name}-${patient.date_of_birth}`
                }
              >
                <CardContent>
                  <Typography
                    sx={{ fontWeight: 700 }}
                  >
                    {patient.full_name
                      || [
                          patient.first_name,
                          patient.last_name,
                        ]
                          .filter(Boolean)
                          .join(' ')
                      || 'Patient'}
                  </Typography>

                  <Typography
                    sx={{
                      mt: 0.4,
                      color: 'text.secondary',
                      fontSize: '0.76rem',
                    }}
                  >
                    MRN{' '}
                    {patient.medical_record_number
                      || 'not recorded'}
                  </Typography>

                  <Typography
                    sx={{
                      mt: 1.2,
                      color: 'text.secondary',
                      fontSize: '0.76rem',
                    }}
                  >
                    DOB{' '}
                    {formatPortalDate(
                      patient.date_of_birth,
                      false
                    )}
                    {' · '}
                    {patient.gender
                      || 'Gender not recorded'}
                  </Typography>
                </CardContent>
              </Card>
            ))}
          </Stack>

          {(query.data?.meta.last_page ?? 1) > 1 && (
            <Box
              sx={{
                display: 'flex',
                justifyContent: 'center',
                mt: 3,
              }}
            >
              <Pagination
                page={page}
                count={
                  query.data?.meta.last_page ?? 1
                }
                onChange={(_event, value) => {
                  setPage(value);
                  window.scrollTo({
                    top: 0,
                    behavior: 'smooth',
                  });
                }}
                color="primary"
              />
            </Box>
          )}
        </>
      )}
    </Box>
  );
};

export default ClinicianPortalPatients;
