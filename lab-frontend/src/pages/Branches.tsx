import {
  useMemo,
  useState,
} from 'react';

import AddBusinessRounded from '@mui/icons-material/AddBusinessRounded';
import ApartmentRounded from '@mui/icons-material/ApartmentRounded';
import HubRounded from '@mui/icons-material/HubRounded';
import RefreshRounded from '@mui/icons-material/RefreshRounded';

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
  MenuItem,
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
  useMutation,
  useQuery,
  useQueryClient,
} from '@tanstack/react-query';

import Layout from '../components/Layout/Layout';
import { useAuth } from '../contexts/AuthContext';

import {
  createBranch,
  fetchBranches,
} from '../features/commercial/api/commercialApi';

import type {
  BranchPayload,
  BranchType,
} from '../features/commercial/types/commercial.types';

import {
  apiErrorMessage,
} from '../features/test-results/utils/apiErrorMessage';

const initialForm: BranchPayload = {
  code: '',
  name: '',
  type: 'laboratory',
  country: 'Nigeria',
  is_active: true,
};

const Branches = () => {
  const { user } = useAuth();
  const queryClient = useQueryClient();
  const [dialogOpen, setDialogOpen] = useState(false);
  const [form, setForm] = useState<BranchPayload>(initialForm);
  const [error, setError] = useState<string | null>(null);

  const branchesQuery = useQuery({
    queryKey: ['commercial-branches'],
    queryFn: fetchBranches,
  });

  const branches = branchesQuery.data?.data ?? [];
  const laboratories = useMemo(
    () => branches.filter(
      (branch) => branch.type === 'laboratory' && branch.is_active
    ),
    [branches]
  );

  const createMutation = useMutation({
    mutationFn: () => createBranch(form),
    onSuccess: async () => {
      setDialogOpen(false);
      setForm(initialForm);
      setError(null);
      await queryClient.invalidateQueries({
        queryKey: ['commercial-branches'],
      });
    },
    onError: (requestError: unknown) => {
      setError(apiErrorMessage(
        requestError,
        'Failed to create the branch.'
      ));
    },
  });

  const canManage = user?.role === 'admin';

  return (
    <Layout title="Branches and collection centres">
      <Stack spacing={2.5}>
        <Box
          sx={{
            display: 'flex',
            alignItems: {
              xs: 'flex-start',
              md: 'center',
            },
            justifyContent: 'space-between',
            flexDirection: {
              xs: 'column',
              md: 'row',
            },
            gap: 2,
          }}
        >
          <Box>
            <Typography variant="h5" sx={{ fontWeight: 750 }}>
              Branch network
            </Typography>
            <Typography color="text.secondary" sx={{ mt: 0.5 }}>
              Manage processing laboratories, collection centres and routing.
            </Typography>
          </Box>

          <Stack direction="row" spacing={1}>
            <Button
              startIcon={<RefreshRounded />}
              onClick={() => {
                void branchesQuery.refetch();
              }}
            >
              Refresh
            </Button>

            {canManage && (
              <Button
                variant="contained"
                startIcon={<AddBusinessRounded />}
                onClick={() => setDialogOpen(true)}
              >
                Add location
              </Button>
            )}
          </Stack>
        </Box>

        {branchesQuery.isError && (
          <Alert severity="error">
            {apiErrorMessage(
              branchesQuery.error,
              'Failed to load the branch network.'
            )}
          </Alert>
        )}

        <Box
          sx={{
            display: 'grid',
            gridTemplateColumns: {
              xs: '1fr',
              md: 'repeat(3, minmax(0, 1fr))',
            },
            gap: 2,
          }}
        >
          <Card variant="outlined">
            <CardContent>
              <ApartmentRounded color="primary" />
              <Typography variant="h4" sx={{ fontWeight: 750, mt: 1 }}>
                {branches.filter((branch) => branch.type === 'laboratory').length}
              </Typography>
              <Typography color="text.secondary">
                Processing laboratories
              </Typography>
            </CardContent>
          </Card>

          <Card variant="outlined">
            <CardContent>
              <HubRounded color="secondary" />
              <Typography variant="h4" sx={{ fontWeight: 750, mt: 1 }}>
                {branches.filter((branch) => branch.type === 'collection_centre').length}
              </Typography>
              <Typography color="text.secondary">
                Collection centres
              </Typography>
            </CardContent>
          </Card>

          <Card variant="outlined">
            <CardContent>
              <Typography variant="overline" color="text.secondary">
                Active locations
              </Typography>
              <Typography variant="h4" sx={{ fontWeight: 750, mt: 1 }}>
                {branches.filter((branch) => branch.is_active).length}
              </Typography>
              <Typography color="text.secondary">
                Available for operational routing
              </Typography>
            </CardContent>
          </Card>
        </Box>

        <TableContainer component={Card} variant="outlined">
          <Table>
            <TableHead>
              <TableRow>
                <TableCell>Location</TableCell>
                <TableCell>Type</TableCell>
                <TableCell>Processing route</TableCell>
                <TableCell>Assigned users</TableCell>
                <TableCell>Status</TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {branches.map((branch) => (
                <TableRow key={branch.uuid} hover>
                  <TableCell>
                    <Typography sx={{ fontWeight: 650 }}>{branch.name}</Typography>
                    <Typography variant="caption" color="text.secondary">
                      {branch.code}
                      {branch.city ? ` · ${branch.city}` : ''}
                      {branch.state ? `, ${branch.state}` : ''}
                    </Typography>
                  </TableCell>
                  <TableCell>
                    <Chip
                      size="small"
                      label={branch.type === 'laboratory'
                        ? 'Laboratory'
                        : 'Collection centre'}
                    />
                  </TableCell>
                  <TableCell>
                    {branch.parent?.name ?? (
                      branch.is_head_office ? 'Head office' : 'Processes locally'
                    )}
                  </TableCell>
                  <TableCell>{branch.users_count ?? 0}</TableCell>
                  <TableCell>
                    <Chip
                      size="small"
                      color={branch.is_active ? 'success' : 'default'}
                      label={branch.is_active ? 'Active' : 'Inactive'}
                    />
                  </TableCell>
                </TableRow>
              ))}

              {!branchesQuery.isPending && branches.length === 0 && (
                <TableRow>
                  <TableCell colSpan={5} align="center">
                    No branch locations are available.
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
        </TableContainer>
      </Stack>

      <Dialog
        open={dialogOpen}
        onClose={() => setDialogOpen(false)}
        fullWidth
        maxWidth="sm"
      >
        <DialogTitle>Add branch or collection centre</DialogTitle>
        <DialogContent>
          <Stack spacing={2} sx={{ mt: 1 }}>
            {error && <Alert severity="error">{error}</Alert>}

            <TextField
              label="Location code"
              value={form.code}
              onChange={(event) => setForm({
                ...form,
                code: event.target.value.toUpperCase(),
              })}
              required
            />

            <TextField
              label="Location name"
              value={form.name}
              onChange={(event) => setForm({
                ...form,
                name: event.target.value,
              })}
              required
            />

            <TextField
              select
              label="Location type"
              value={form.type}
              onChange={(event) => setForm({
                ...form,
                type: event.target.value as BranchType,
                parent_branch_uuid: undefined,
              })}
            >
              <MenuItem value="laboratory">Processing laboratory</MenuItem>
              <MenuItem value="collection_centre">Collection centre</MenuItem>
            </TextField>

            {form.type === 'collection_centre' && (
              <TextField
                select
                label="Processing laboratory"
                value={form.parent_branch_uuid ?? ''}
                onChange={(event) => setForm({
                  ...form,
                  parent_branch_uuid: event.target.value,
                })}
                required
              >
                {laboratories.map((branch) => (
                  <MenuItem key={branch.uuid} value={branch.uuid}>
                    {branch.name} ({branch.code})
                  </MenuItem>
                ))}
              </TextField>
            )}

            <Stack direction={{ xs: 'column', sm: 'row' }} spacing={2}>
              <TextField
                fullWidth
                label="City"
                value={form.city ?? ''}
                onChange={(event) => setForm({
                  ...form,
                  city: event.target.value,
                })}
              />
              <TextField
                fullWidth
                label="State"
                value={form.state ?? ''}
                onChange={(event) => setForm({
                  ...form,
                  state: event.target.value,
                })}
              />
            </Stack>
          </Stack>
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setDialogOpen(false)}>Cancel</Button>
          <Button
            variant="contained"
            disabled={
              createMutation.isPending
              || !form.code.trim()
              || !form.name.trim()
              || (
                form.type === 'collection_centre'
                && !form.parent_branch_uuid
              )
            }
            onClick={() => createMutation.mutate()}
          >
            {createMutation.isPending ? 'Saving…' : 'Create location'}
          </Button>
        </DialogActions>
      </Dialog>
    </Layout>
  );
};

export default Branches;
