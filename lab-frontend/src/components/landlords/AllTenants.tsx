import React, { useEffect, useState } from 'react';
import {
  Box,
  Typography,
  Paper,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Chip,
  CircularProgress,
  TextField,
  InputAdornment,
  IconButton,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  Button,
  Alert,
  Snackbar,
} from '@mui/material';
import { Search, Delete, Refresh, CheckCircle, Cancel } from '@mui/icons-material';
import api from '../../api/client';

interface Tenant {
  id: number;
  name: string;
  domain: string;
  is_active: boolean;
  trial_ends_at: string | null;
  created_at: string;
  stats?: {
    users: number;
    patients: number;
    lab_tests: number;
    test_results: number;
  };
}

const AllTenants: React.FC = () => {
  const [tenants, setTenants] = useState<Tenant[]>([]);
  const [loading, setLoading] = useState<boolean>(true);
  const [search, setSearch] = useState<string>('');
  const [selectedTenant, setSelectedTenant] = useState<Tenant | null>(null);
  const [deleteDialogOpen, setDeleteDialogOpen] = useState<boolean>(false);
  const [suspendDialogOpen, setSuspendDialogOpen] = useState<boolean>(false);
  const [snackbar, setSnackbar] = useState<{ open: boolean; message: string; severity: 'success' | 'error' }>({
    open: false,
    message: '',
    severity: 'success',
  });

  const fetchTenants = async () => {
    setLoading(true);
    try {
      const res = await api.get('/landlord/tenants');
      setTenants(res.data.tenants || []);
    } catch (error) {
      console.error('Error fetching tenants:', error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchTenants();
  }, []);

  const handleSuspend = async () => {
    if (!selectedTenant) return;
    try {
      const endpoint = selectedTenant.is_active
        ? `/landlord/tenants/${selectedTenant.id}/suspend`
        : `/landlord/tenants/${selectedTenant.id}/activate`;

      await api.post(endpoint);
      setSnackbar({
        open: true,
        message: `Tenant ${selectedTenant.is_active ? 'suspended' : 'activated'} successfully`,
        severity: 'success',
      });
      fetchTenants();
    } catch (error) {
      setSnackbar({ open: true, message: 'Failed to update tenant status', severity: 'error' });
    }
    setSuspendDialogOpen(false);
    setSelectedTenant(null);
  };

  const handleDelete = async () => {
    if (!selectedTenant) return;
    try {
      await api.delete(`/landlord/tenants/${selectedTenant.id}`);
      setSnackbar({ open: true, message: 'Tenant deleted successfully', severity: 'success' });
      fetchTenants();
    } catch (error) {
      setSnackbar({ open: true, message: 'Failed to delete tenant', severity: 'error' });
    }
    setDeleteDialogOpen(false);
    setSelectedTenant(null);
  };

  const filteredTenants = tenants.filter((tenant) =>
    tenant.name.toLowerCase().includes(search.toLowerCase()) ||
    tenant.domain.toLowerCase().includes(search.toLowerCase())
  );

  if (loading) return <CircularProgress />;

  return (
    <Box>
      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 3, flexWrap: 'wrap', gap: 2 }}>
        <Typography variant="h5">🏢 All Tenants</Typography>
        <Box sx={{ display: 'flex', gap: 2 }}>
          <TextField
            size="small"
            placeholder="Search tenants..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            slotProps={{
              input: {
                startAdornment: (
                  <InputAdornment position="start">
                    <Search />
                  </InputAdornment>
                ),
              },
            }}
          />
          <Button variant="contained" startIcon={<Refresh />} onClick={fetchTenants}>
            Refresh
          </Button>
        </Box>
      </Box>

      <Typography variant="body2" color="textSecondary" sx={{ mb: 2 }}>
        Total: {filteredTenants.length} tenants
      </Typography>

      <TableContainer component={Paper}>
        <Table>
          <TableHead>
            <TableRow>
              <TableCell>Name</TableCell>
              <TableCell>Domain</TableCell>
              <TableCell>Status</TableCell>
              <TableCell>Users</TableCell>
              <TableCell>Patients</TableCell>
              <TableCell>Tests</TableCell>
              <TableCell>Actions</TableCell>
            </TableRow>
          </TableHead>
          <TableBody>
            {filteredTenants.map((tenant) => (
              <TableRow key={tenant.id} hover>
                <TableCell>{tenant.name}</TableCell>
                <TableCell>{tenant.domain}</TableCell>
                <TableCell>
                  <Chip
                    label={tenant.is_active ? 'Active' : 'Suspended'}
                    color={tenant.is_active ? 'success' : 'error'}
                    size="small"
                    icon={tenant.is_active ? <CheckCircle /> : <Cancel />}
                  />
                </TableCell>
                <TableCell>{tenant.stats?.users || 0}</TableCell>
                <TableCell>{tenant.stats?.patients || 0}</TableCell>
                <TableCell>{tenant.stats?.lab_tests || 0}</TableCell>
                <TableCell>
                  <IconButton
                    size="small"
                    color={tenant.is_active ? 'warning' : 'success'}
                    onClick={() => {
                      setSelectedTenant(tenant);
                      setSuspendDialogOpen(true);
                    }}
                  >
                    {tenant.is_active ? <Cancel /> : <CheckCircle />}
                  </IconButton>
                  <IconButton
                    size="small"
                    color="error"
                    onClick={() => {
                      setSelectedTenant(tenant);
                      setDeleteDialogOpen(true);
                    }}
                  >
                    <Delete fontSize="small" />
                  </IconButton>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </TableContainer>

      {/* Suspend/Activate Dialog */}
      <Dialog open={suspendDialogOpen} onClose={() => setSuspendDialogOpen(false)}>
        <DialogTitle>{selectedTenant?.is_active ? 'Suspend' : 'Activate'} Tenant</DialogTitle>
        <DialogContent>
          <Typography>
            Are you sure you want to {selectedTenant?.is_active ? 'suspend' : 'activate'}
            tenant &quot;{selectedTenant?.name}&quot;?
          </Typography>
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setSuspendDialogOpen(false)}>Cancel</Button>
          <Button
            onClick={handleSuspend}
            variant="contained"
            color={selectedTenant?.is_active ? 'warning' : 'success'}
          >
            {selectedTenant?.is_active ? 'Suspend' : 'Activate'}
          </Button>
        </DialogActions>
      </Dialog>

      {/* Delete Confirmation Dialog */}
      <Dialog open={deleteDialogOpen} onClose={() => setDeleteDialogOpen(false)}>
        <DialogTitle>Confirm Delete</DialogTitle>
        <DialogContent>
          <Typography color="error">
            ⚠️ Warning: This will permanently delete the tenant and all its data!
          </Typography>
          <Typography sx={{ mt: 2 }}>
            Are you sure you want to delete tenant &quot;{selectedTenant?.name}&quot;? This action cannot be undone.
          </Typography>
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setDeleteDialogOpen(false)}>Cancel</Button>
          <Button onClick={handleDelete} variant="contained" color="error">
            Delete Permanently
          </Button>
        </DialogActions>
      </Dialog>

      {/* Snackbar */}
      <Snackbar
        open={snackbar.open}
        autoHideDuration={3000}
        onClose={() => setSnackbar({ ...snackbar, open: false })}
        anchorOrigin={{ vertical: 'bottom', horizontal: 'center' }}
      >
        <Alert severity={snackbar.severity}>{snackbar.message}</Alert>
      </Snackbar>
    </Box>
  );
};

export default AllTenants;