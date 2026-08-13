import {
  BlockRounded,
  CheckCircleRounded,
  ContentCopyRounded,
  LinkRounded,
} from '@mui/icons-material';

import {
  Box,
  Button,
  Chip,
  CircularProgress,
  Stack,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TablePagination,
  TableRow,
  Typography,
} from '@mui/material';

import type {
  PortalAccessRow,
  PortalInvitationStatus,
} from '../types/portalAccessAdmin.types';

interface Props {
  rows: PortalAccessRow[];
  loading: boolean;
  total: number;
  page: number;
  rowsPerPage: number;
  busyUuid: string | null;
  onPageChange: (page: number) => void;
  onRowsPerPageChange: (rows: number) => void;
  onInvite: (row: PortalAccessRow) => void;
  onToggleAccount: (row: PortalAccessRow) => void;
  onRevokeInvitation: (row: PortalAccessRow) => void;
}

const invitationColor = (
  status: PortalInvitationStatus
): 'success' | 'warning' | 'default' => {
  if (status === 'used') {
    return 'success';
  }

  if (status === 'pending') {
    return 'warning';
  }

  return 'default';
};

const displayDate = (
  value: string | null
): string => {
  if (!value) {
    return '—';
  }

  const date = new Date(value);

  return Number.isNaN(date.getTime())
    ? '—'
    : date.toLocaleString();
};

const PortalAccessTable = ({
  rows,
  loading,
  total,
  page,
  rowsPerPage,
  busyUuid,
  onPageChange,
  onRowsPerPageChange,
  onInvite,
  onToggleAccount,
  onRevokeInvitation,
}: Props) => (
  <>
    <TableContainer>
      <Table>
        <TableHead>
          <TableRow>
            <TableCell>Person</TableCell>
            <TableCell>Portal account</TableCell>
            <TableCell>Invitation</TableCell>
            <TableCell>Last login</TableCell>
            <TableCell align="right">Actions</TableCell>
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
                <CircularProgress size={30} />
              </TableCell>
            </TableRow>
          ) : rows.length === 0 ? (
            <TableRow>
              <TableCell
                colSpan={5}
                align="center"
                sx={{ py: 8 }}
              >
                <Typography color="text.secondary">
                  No matching records found.
                </Typography>
              </TableCell>
            </TableRow>
          ) : rows.map((row) => {
            const account = row.account;
            const invitation = account?.invitation;
            const rowKey = account?.uuid
              ?? `${row.subject_type}-${row.subject.id ?? row.subject.uuid}`;
            const busy = busyUuid === rowKey;
            const canInvite = row.subject.is_active
              && account?.activated_at == null;
            const canRevoke = invitation?.status === 'pending';

            return (
              <TableRow key={rowKey} hover>
                <TableCell>
                  <Typography
                    variant="body2"
                    sx={{ fontWeight: 700 }}
                  >
                    {row.subject.name}
                  </Typography>
                  <Typography
                    variant="caption"
                    color="text.secondary"
                    sx={{ display: 'block' }}
                  >
                    {row.subject.identifier || 'No identifier'}
                  </Typography>
                  {row.subject.organisation && (
                    <Typography
                      variant="caption"
                      color="text.secondary"
                      sx={{ display: 'block' }}
                    >
                      {row.subject.organisation}
                    </Typography>
                  )}
                </TableCell>

                <TableCell>
                  {account ? (
                    <Stack
                      spacing={0.75}
                      sx={{ alignItems: 'flex-start' }}
                    >
                      <Typography variant="body2">
                        {account.email}
                      </Typography>
                      <Chip
                        size="small"
                        icon={account.is_active
                          ? <CheckCircleRounded />
                          : <BlockRounded />}
                        label={account.is_active
                          ? account.activated_at
                            ? 'Active'
                            : 'Awaiting activation'
                          : 'Disabled'}
                        color={account.is_active
                          ? account.activated_at
                            ? 'success'
                            : 'warning'
                          : 'default'}
                      />
                    </Stack>
                  ) : (
                    <Chip
                      size="small"
                      label="Not enabled"
                      variant="outlined"
                    />
                  )}
                </TableCell>

                <TableCell>
                  {invitation ? (
                    <Stack
                      spacing={0.5}
                      sx={{ alignItems: 'flex-start' }}
                    >
                      <Chip
                        size="small"
                        label={invitation.status}
                        color={invitationColor(invitation.status)}
                      />
                      <Typography
                        variant="caption"
                        color="text.secondary"
                      >
                        Expires {displayDate(invitation.expires_at)}
                      </Typography>
                    </Stack>
                  ) : (
                    <Typography
                      variant="body2"
                      color="text.secondary"
                    >
                      —
                    </Typography>
                  )}
                </TableCell>

                <TableCell>
                  <Typography variant="body2">
                    {displayDate(account?.last_login_at ?? null)}
                  </Typography>
                </TableCell>

                <TableCell align="right">
                  <Box
                    sx={{
                      display: 'flex',
                      justifyContent: 'flex-end',
                      flexWrap: 'wrap',
                      gap: 1,
                    }}
                  >
                    {canInvite && (
                      <Button
                        size="small"
                        variant="outlined"
                        startIcon={invitation?.status === 'pending'
                          ? <ContentCopyRounded />
                          : <LinkRounded />}
                        disabled={busy}
                        onClick={() => onInvite(row)}
                      >
                        {invitation?.status === 'pending'
                          ? 'Replace invitation'
                          : 'Create invitation'}
                      </Button>
                    )}

                    {canRevoke && (
                      <Button
                        size="small"
                        color="warning"
                        disabled={busy}
                        onClick={() => onRevokeInvitation(row)}
                      >
                        Revoke
                      </Button>
                    )}

                    {account && (
                      <Button
                        size="small"
                        color={account.is_active
                          ? 'error'
                          : 'success'}
                        disabled={busy || !row.subject.is_active}
                        onClick={() => onToggleAccount(row)}
                      >
                        {account.is_active ? 'Disable' : 'Enable'}
                      </Button>
                    )}
                  </Box>
                </TableCell>
              </TableRow>
            );
          })}
        </TableBody>
      </Table>
    </TableContainer>

    <TablePagination
      component="div"
      count={total}
      page={page}
      rowsPerPage={rowsPerPage}
      rowsPerPageOptions={[10, 25, 50]}
      onPageChange={(_, nextPage) => onPageChange(nextPage)}
      onRowsPerPageChange={(event) => {
        onRowsPerPageChange(Number(event.target.value));
      }}
    />
  </>
);

export default PortalAccessTable;
