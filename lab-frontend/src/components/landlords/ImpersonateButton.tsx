import React, { useState } from 'react';
import {
  Button,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  List,
  ListItem,
  ListItemText,
  CircularProgress,
  Alert,
  Snackbar,
  Box,
} from '@mui/material';
import { VpnKey, Login as LoginIcon } from '@mui/icons-material';
import api from '../../api/client';

interface Tenant {
  id: number;
  name: string;
  domain: string;
}

const ImpersonateButton: React.FC = () => {
  const [open, setOpen] = useState<boolean>(false);
  const [tenants, setTenants] = useState<Tenant[]>([]);
  const [loading, setLoading] = useState<boolean>(false);
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
      setSnackbar({ open: true, message: 'Failed to fetch tenants', severity: 'error' });
    } finally {
      setLoading(false);
    }
  };

  const handleOpen = async () => {
    await fetchTenants();
    setOpen(true);
  };

  const handleImpersonate = async (tenantId: number, tenantName: string) => {
    try {
      const res = await api.post(`/landlord/impersonate/${tenantId}`);
      const { token, user, tenant } = res.data;

      // Store impersonation data
      localStorage.setItem('token', token);
      localStorage.setItem('user', JSON.stringify(user));
      localStorage.setItem('tenant', JSON.stringify(tenant));
      localStorage.setItem('tenant_domain', tenant.domain);
      localStorage.setItem('is_superadmin', 'false');
      localStorage.setItem('is_impersonating', 'true');
      localStorage.setItem('role', user.role);

      setSnackbar({
        open: true,
        message: `Now impersonating ${tenantName}`,
        severity: 'success',
      });

      setTimeout(() => {
        window.location.href = '/dashboard';
      }, 1500);
    } catch (error) {
      console.error('Error impersonating:', error);
      setSnackbar({ open: true, message: 'Failed to impersonate tenant', severity: 'error' });
    }
    setOpen(false);
  };

  return (
    <Box>
      <Button
        variant="contained"
        color="secondary"
        startIcon={<VpnKey />}
        onClick={handleOpen}
      >
        Impersonate Tenant
      </Button>

      <Dialog open={open} onClose={() => setOpen(false)} maxWidth="sm" fullWidth>
        <DialogTitle>
          <LoginIcon sx={{ mr: 1, verticalAlign: 'middle' }} />
          Enter a Tenant
        </DialogTitle>
        <DialogContent>
          {loading ? (
            <Box sx={{ display: 'flex', justifyContent: 'center', p: 3 }}>
              <CircularProgress />
            </Box>
          ) : (
            <List>
              {tenants.map((tenant) => (
                <ListItem
                  key={tenant.id}
                  onClick={() => handleImpersonate(tenant.id, tenant.name)}
                  sx={{
                    '&:hover': { backgroundColor: 'rgba(67, 97, 238, 0.08)' },
                    borderRadius: 1,
                    my: 0.5,
                    cursor: 'pointer',
                  }}
                >
                  <ListItemText
                    primary={tenant.name}
                    secondary={tenant.domain}
                  />
                  <Button size="small" variant="outlined">
                    Enter
                  </Button>
                </ListItem>
              ))}
            </List>
          )}
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setOpen(false)}>Cancel</Button>
        </DialogActions>
      </Dialog>

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

export default ImpersonateButton;