import {
  useState,
} from 'react';

import ArrowForwardRounded from
  '@mui/icons-material/ArrowForwardRounded';
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
  Link,
} from 'react-router-dom';

import {
  fetchClinicianPortalReports,
} from '../api/clinicianPortalApi';

import ClinicianPortalEmptyState from
  '../components/ClinicianPortalEmptyState';

import ClinicianPortalLoadingState from
  '../components/ClinicianPortalLoadingState';

import ClinicianPortalPageHeader from
  '../components/ClinicianPortalPageHeader';

import ClinicianPortalStatusChip from
  '../components/ClinicianPortalStatusChip';

import {
  useClinicianPortalAuth,
} from '../context/ClinicianPortalAuthContext';

import {
  formatPortalDate,
  clinicianPortalErrorMessage,
} from '../utils/clinicianPortalDisplay';

const ClinicianPortalReports = () => {
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
      'reports',
      page,
      search,
    ],
    queryFn: () => (
      fetchClinicianPortalReports({
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
        eyebrow="Clinical documents"
        title="Reports"
        description="Access current issued or amended laboratory reports for patients linked to your referred orders. Superseded report versions are not shown here."
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
              label="Search report or order number"
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
            'We could not load your referred-patient laboratory reports.'
          )}
        </Alert>
      ) : (query.data?.data.length ?? 0) === 0 ? (
        <ClinicianPortalEmptyState
          title={
            search.trim()
              ? 'No reports match this search'
              : 'No reports available yet'
          }
          description={
            search.trim()
              ? 'Try a different report number or laboratory order number.'
              : 'Current issued reports for your explicitly linked referred orders will appear here when available.'
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
                  <TableCell>Report</TableCell>
                  <TableCell>Order</TableCell>
                  <TableCell>Patient</TableCell>
                  <TableCell>Status</TableCell>
                  <TableCell>Generated</TableCell>
                  <TableCell align="right">
                    Action
                  </TableCell>
                </TableRow>
              </TableHead>

              <TableBody>
                {query.data?.data.map((report) => (
                  <TableRow
                    key={report.uuid}
                    hover
                  >
                    <TableCell>
                      <Typography
                        sx={{ fontWeight: 680 }}
                      >
                        {report.report_number}
                      </Typography>

                      <Typography
                        sx={{
                          mt: 0.25,
                          color: 'text.secondary',
                          fontSize: '0.72rem',
                        }}
                      >
                        Version {report.version}
                      </Typography>
                    </TableCell>

                    <TableCell>
                      {report.order.order_number
                        || 'Not recorded'}
                    </TableCell>

                    <TableCell>
                      {report.patient.full_name
                        || 'Patient'}
                    </TableCell>

                    <TableCell>
                      <ClinicianPortalStatusChip
                        value={
                          report.lifecycle_status
                        }
                      />
                    </TableCell>

                    <TableCell>
                      {formatPortalDate(
                        report.generated_at
                      )}
                    </TableCell>

                    <TableCell align="right">
                      <Button
                        component={Link}
                        to={
                          `/clinician-portal/reports/${report.uuid}`
                        }
                        size="small"
                        endIcon={
                          <ArrowForwardRounded />
                        }
                      >
                        View
                      </Button>
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
            {query.data?.data.map((report) => (
              <Card key={report.uuid}>
                <CardContent>
                  <Box
                    sx={{
                      display: 'flex',
                      gap: 1,
                      alignItems: 'flex-start',
                      justifyContent: 'space-between',
                    }}
                  >
                    <Box sx={{ minWidth: 0 }}>
                      <Typography
                        sx={{ fontWeight: 700 }}
                      >
                        {report.report_number}
                      </Typography>

                      <Typography
                        sx={{
                          mt: 0.35,
                          color: 'text.secondary',
                          fontSize: '0.74rem',
                        }}
                      >
                        {report.order.order_number
                          || 'Laboratory report'}
                      </Typography>
                    </Box>

                    <ClinicianPortalStatusChip
                      value={
                        report.lifecycle_status
                      }
                    />
                  </Box>

                  <Typography
                    sx={{
                      mt: 1.5,
                      color: 'text.secondary',
                      fontSize: '0.76rem',
                    }}
                  >
                    Generated{' '}
                    {formatPortalDate(
                      report.generated_at
                    )}
                  </Typography>

                  <Button
                    component={Link}
                    to={
                      `/clinician-portal/reports/${report.uuid}`
                    }
                    fullWidth
                    variant="outlined"
                    sx={{ mt: 2 }}
                  >
                    Open report
                  </Button>
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

export default ClinicianPortalReports;
