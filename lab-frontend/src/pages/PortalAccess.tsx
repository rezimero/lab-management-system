import {
  KeyRounded,
  SearchRounded,
} from '@mui/icons-material';

import {
  Alert,
  Box,
  InputAdornment,
  Paper,
  Stack,
  Tab,
  Tabs,
  TextField,
  Typography,
} from '@mui/material';

import {
  useCallback,
  useEffect,
  useState,
} from 'react';

import Layout from '../components/Layout/Layout';

import {
  fetchPortalAccessSubjects,
  issuePortalInvitation,
  revokePortalInvitation,
  setPortalAccountActive,
} from '../features/portal-access/api/portalAccessAdminApi';

import PortalAccessTable from '../features/portal-access/components/PortalAccessTable';
import PortalInvitationDialog from '../features/portal-access/components/PortalInvitationDialog';

import type {
  PortalAccountType,
  PortalAccessRow,
  PortalInvitationIssueResult,
} from '../features/portal-access/types/portalAccessAdmin.types';

import {
  apiErrorMessage,
} from '../features/test-results/utils/apiErrorMessage';

const rowIdentifier = (
  row: PortalAccessRow
): string => row.account?.uuid
  ?? `${row.subject_type}-${row.subject.id ?? row.subject.uuid}`;

const subjectIdentifier = (
  row: PortalAccessRow
): number | string => {
  const identifier = row.subject_type === 'patient'
    ? row.subject.id
    : row.subject.uuid;

  if (identifier == null) {
    throw new Error(
      'The selected record has no portal identifier.'
    );
  }

  return identifier;
};

const PortalAccess = () => {
  const [accountType, setAccountType] =
    useState<PortalAccountType>('patient');
  const [rows, setRows] =
    useState<PortalAccessRow[]>([]);
  const [loading, setLoading] =
    useState(true);
  const [error, setError] =
    useState<string | null>(null);
  const [search, setSearch] =
    useState('');
  const [page, setPage] =
    useState(0);
  const [rowsPerPage, setRowsPerPage] =
    useState(10);
  const [total, setTotal] =
    useState(0);
  const [busyUuid, setBusyUuid] =
    useState<string | null>(null);
  const [selectedRow, setSelectedRow] =
    useState<PortalAccessRow | null>(null);
  const [invitationOpen, setInvitationOpen] =
    useState(false);
  const [invitationResult, setInvitationResult] =
    useState<PortalInvitationIssueResult | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);

    try {
      const result = await fetchPortalAccessSubjects(
        accountType,
        {
          page: page + 1,
          per_page: rowsPerPage,
          search: search.trim() || undefined,
        }
      );

      setRows(result.data);
      setTotal(result.meta.total);
    } catch (requestError: unknown) {
      setError(apiErrorMessage(
        requestError,
        'Failed to load portal access records.'
      ));
    } finally {
      setLoading(false);
    }
  }, [accountType, page, rowsPerPage, search]);

  useEffect(() => {
    const timer = window.setTimeout(() => {
      void load();
    }, 180);

    return () => window.clearTimeout(timer);
  }, [load]);

  const openInvitation = (
    row: PortalAccessRow
  ) => {
    setSelectedRow(row);
    setInvitationResult(null);
    setError(null);
    setInvitationOpen(true);
  };

  const closeInvitation = () => {
    if (busyUuid !== null) {
      return;
    }

    setInvitationOpen(false);
    setInvitationResult(null);
    setSelectedRow(null);
  };

  const createInvitation = async (
    email: string
  ) => {
    if (!selectedRow) {
      return;
    }

    const identifier = rowIdentifier(selectedRow);
    setBusyUuid(identifier);
    setError(null);

    try {
      const result = await issuePortalInvitation(
        selectedRow.subject_type,
        subjectIdentifier(selectedRow),
        email
      );

      setInvitationResult(result);
      await load();
    } catch (requestError: unknown) {
      setError(apiErrorMessage(
        requestError,
        'Failed to create the portal invitation.'
      ));
    } finally {
      setBusyUuid(null);
    }
  };

  const toggleAccount = async (
    row: PortalAccessRow
  ) => {
    if (!row.account) {
      return;
    }

    const identifier = rowIdentifier(row);
    setBusyUuid(identifier);
    setError(null);

    try {
      await setPortalAccountActive(
        row.subject_type,
        row.account.uuid,
        !row.account.is_active
      );
      await load();
    } catch (requestError: unknown) {
      setError(apiErrorMessage(
        requestError,
        'Failed to update the portal account.'
      ));
    } finally {
      setBusyUuid(null);
    }
  };

  const revokeInvitation = async (
    row: PortalAccessRow
  ) => {
    const invitation = row.account?.invitation;

    if (!invitation) {
      return;
    }

    const identifier = rowIdentifier(row);
    setBusyUuid(identifier);
    setError(null);

    try {
      await revokePortalInvitation(
        invitation.uuid
      );
      await load();
    } catch (requestError: unknown) {
      setError(apiErrorMessage(
        requestError,
        'Failed to revoke the portal invitation.'
      ));
    } finally {
      setBusyUuid(null);
    }
  };

  return (
    <Layout title="Portal access">
      <Stack spacing={2.5} sx={{ width: '100%' }}>
        <Box>
          <Typography
            variant="h4"
            sx={{ fontWeight: 760 }}
          >
            Patient and clinician portal access
          </Typography>

          <Typography
            color="text.secondary"
            sx={{ mt: 0.75 }}
          >
            Create one-time invitations, monitor activation state, and immediately revoke portal sessions by disabling an account.
          </Typography>
        </Box>

        {error && (
          <Alert
            severity="error"
            onClose={() => setError(null)}
          >
            {error}
          </Alert>
        )}

        <Paper variant="outlined" sx={{ overflow: 'hidden' }}>
          <Box
            sx={{
              px: 2,
              pt: 1,
              borderBottom: 1,
              borderColor: 'divider',
            }}
          >
            <Tabs
              value={accountType}
              onChange={(_, value: PortalAccountType) => {
                setPage(0);
                setAccountType(value);
              }}
              aria-label="Portal account type"
            >
              <Tab value="patient" label="Patients" />
              <Tab value="clinician" label="Clinicians" />
            </Tabs>
          </Box>

          <Box sx={{ p: 2 }}>
            <TextField
              fullWidth
              value={search}
              placeholder={accountType === 'patient'
                ? 'Search patient name, email or MRN…'
                : 'Search clinician, organisation or registration…'}
              onChange={(event) => {
                setPage(0);
                setSearch(event.target.value);
              }}
              slotProps={{
                input: {
                  startAdornment: (
                    <InputAdornment position="start">
                      <SearchRounded color="action" />
                    </InputAdornment>
                  ),
                },
              }}
            />
          </Box>

          <PortalAccessTable
            rows={rows}
            loading={loading}
            total={total}
            page={page}
            rowsPerPage={rowsPerPage}
            busyUuid={busyUuid}
            onPageChange={setPage}
            onRowsPerPageChange={(value) => {
              setRowsPerPage(value);
              setPage(0);
            }}
            onInvite={openInvitation}
            onToggleAccount={(row) => {
              void toggleAccount(row);
            }}
            onRevokeInvitation={(row) => {
              void revokeInvitation(row);
            }}
          />
        </Paper>

        <Alert severity="info" icon={<KeyRounded />}>
          Activation links are displayed once for manual copying. Email, SMS and WhatsApp delivery will be owned by the notifications module.
        </Alert>
      </Stack>

      <PortalInvitationDialog
        row={selectedRow}
        open={invitationOpen}
        saving={busyUuid !== null}
        error={error}
        result={invitationResult}
        onClose={closeInvitation}
        onSubmit={(email) => {
          void createInvitation(email);
        }}
      />
    </Layout>
  );
};

export default PortalAccess;
