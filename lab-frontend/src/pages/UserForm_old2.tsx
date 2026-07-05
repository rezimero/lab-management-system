import React, { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import {
  Box,
  Paper,
  TextField,
  Button,
  Grid,
  Typography,
  MenuItem,
  FormControlLabel,
  Switch,
  Alert,
  Avatar,
  alpha,
  useTheme,
  Chip,
  InputAdornment,
  CircularProgress,
} from '@mui/material';
import {
  Save as SaveIcon,
  Cancel as CancelIcon,
  PersonAdd as PersonAddIcon,
  Edit as EditIcon,
  Person as PersonIcon,
} from '@mui/icons-material';
import Layout from '../components/Layout/Layout';
import api from '../api/client';

interface UserFormData {
  id?: number;
  name: string;
  email: string;
  password?: string;
  role: string;
  is_active: boolean;
}

const UserForm: React.FC = () => {
  const navigate = useNavigate();
  const { id } = useParams<{ id: string }>();
  const theme = useTheme();
  const isEdit = !!id;
  const [loading, setLoading] = useState(false);
  const [loadingRoles, setLoadingRoles] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [formData, setFormData] = useState<UserFormData>({
    name: '',
    email: '',
    password: '',
    role: 'lab_technician', // Changed from 'staff' to a valid role
    is_active: true,
  });
  const [availableRoles, setAvailableRoles] = useState<Record<string, string>>({});

  useEffect(() => {
    fetchRoles();
    if (isEdit) {
      fetchUser();
    }
  }, [id]);

  const fetchRoles = async () => {
    setLoadingRoles(true);
    try {
      const response = await api.get('/roles');
      setAvailableRoles(response.data.roles);
      
      // If there are roles available and the current role is not in the list, set the first role as default
      const roleKeys = Object.keys(response.data.roles);
      if (roleKeys.length > 0 && !roleKeys.includes(formData.role)) {
        setFormData(prev => ({ ...prev, role: roleKeys[0] }));
      }
    } catch (error) {
      console.error('Error fetching roles:', error);
    } finally {
      setLoadingRoles(false);
    }
  };

  const fetchUser = async () => {
    setLoading(true);
    try {
      const response = await api.get(`/users/${id}`);
      setFormData(response.data);
    } catch (error) {
      console.error('Error fetching user:', error);
      setError('Failed to load user data');
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError(null);

    try {
      if (isEdit) {
        const data: any = { ...formData };
        if (!data.password) {
          delete data.password;
        }
        await api.put(`/users/${id}`, data);
      } else {
        await api.post('/users', formData);
      }
      navigate('/users');
    } catch (error: any) {
      console.error('Error saving user:', error);
      setError(error.response?.data?.message || 'Failed to save user');
      setLoading(false);
    }
  };

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    const { name, value, type, checked } = e.target as HTMLInputElement;
    setFormData({
      ...formData,
      [name]: type === 'checkbox' ? checked : value,
    });
  };

  const getRoleChip = (role: string) => {
    const colors: Record<string, 'primary' | 'secondary' | 'success' | 'warning' | 'info' | 'error'> = {
      superadmin: 'error',
      admin: 'primary',
      lab_technician: 'success',
      receptionist: 'warning',
      doctor: 'info',
    };
    const roleMap: Record<string, string> = {
      superadmin: 'Super Admin',
      admin: 'Admin',
      lab_technician: 'Lab Tech',
      receptionist: 'Receptionist',
      doctor: 'Doctor',
    };
    return (
      <Chip
        label={roleMap[role] || role.replace('_', ' ').toUpperCase()}
        color={colors[role] || 'default'}
        size="small"
        sx={{ fontWeight: 600 }}
      />
    );
  };

  if (loadingRoles) {
    return (
      <Layout title={isEdit ? 'Edit User' : 'Add New User'}>
        <Box sx={{ display: 'flex', justifyContent: 'center', alignItems: 'center', minHeight: '60vh' }}>
          <CircularProgress />
        </Box>
      </Layout>
    );
  }

  return (
    <Layout title={isEdit ? 'Edit User' : 'Add New User'}>
      <Box sx={{ maxWidth: 800, mx: 'auto' }}>
        <Paper
          elevation={0}
          sx={{
            p: 4,
            borderRadius: 4,
            border: `1px solid ${alpha(theme.palette.divider, 0.08)}`,
            backdropFilter: 'blur(10px)',
            backgroundColor: alpha(theme.palette.background.paper, 0.6),
          }}
        >
          <Box sx={{ mb: 4, display: 'flex', alignItems: 'center', gap: 2 }}>
            <Avatar
              sx={{
                width: 56,
                height: 56,
                bgcolor: theme.palette.primary.main,
                background: `linear-gradient(135deg, ${theme.palette.primary.main}, ${theme.palette.primary.dark})`,
                boxShadow: `0 8px 24px ${alpha(theme.palette.primary.main, 0.3)}`,
              }}
            >
              {isEdit ? <EditIcon /> : <PersonAddIcon />}
            </Avatar>
            <Box>
              <Typography variant="h5" sx={{ fontWeight: 700 }}>
                {isEdit ? 'Edit User' : 'Add New User'}
              </Typography>
              <Typography variant="body2" color="text.secondary">
                {isEdit ? 'Update user information' : 'Create a new system user'}
              </Typography>
            </Box>
          </Box>

          {error && (
            <Alert severity="error" sx={{ mb: 3, borderRadius: 2 }}>
              {error}
            </Alert>
          )}

          <form onSubmit={handleSubmit}>
            <Grid container spacing={3}>
              <Grid size={{ xs: 12, md: 6 }}>
                <TextField
                  fullWidth
                  label="Full Name"
                  name="name"
                  value={formData.name}
                  onChange={handleChange}
                  required
                  variant="outlined"
                  slotProps={{
                    input: {
                      sx: { borderRadius: 2 },
                      startAdornment: (
                        <InputAdornment position="start">
                          <PersonIcon sx={{ color: 'text.secondary' }} />
                        </InputAdornment>
                      ),
                    },
                  }}
                />
              </Grid>
              <Grid size={{ xs: 12, md: 6 }}>
                <TextField
                  fullWidth
                  label="Email"
                  name="email"
                  type="email"
                  value={formData.email}
                  onChange={handleChange}
                  required
                  variant="outlined"
                  slotProps={{
                    input: {
                      sx: { borderRadius: 2 },
                    },
                  }}
                />
              </Grid>
              <Grid size={{ xs: 12, md: 6 }}>
                <TextField
                  fullWidth
                  label={isEdit ? "New Password (leave blank to keep current)" : "Password"}
                  name="password"
                  type="password"
                  value={formData.password || ''}
                  onChange={handleChange}
                  required={!isEdit}
                  variant="outlined"
                  slotProps={{
                    input: {
                      sx: { borderRadius: 2 },
                    },
                  }}
                />
              </Grid>
              <Grid size={{ xs: 12, md: 6 }}>
                <TextField
                  fullWidth
                  select
                  label="Role"
                  name="role"
                  value={formData.role}
                  onChange={handleChange}
                  required
                  variant="outlined"
                  slotProps={{
                    input: {
                      sx: { borderRadius: 2 },
                    },
                  }}
                >
                  {Object.entries(availableRoles).map(([key, value]) => (
                    <MenuItem key={key} value={key}>
                      <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                        {getRoleChip(key)}
                        <Typography variant="body2">{value}</Typography>
                      </Box>
                    </MenuItem>
                  ))}
                </TextField>
              </Grid>
              <Grid size={{ xs: 12 }}>
                <FormControlLabel
                  control={
                    <Switch
                      name="is_active"
                      checked={formData.is_active}
                      onChange={handleChange}
                      sx={{
                        '& .MuiSwitch-switchBase.Mui-checked': {
                          color: theme.palette.success.main,
                        },
                        '& .MuiSwitch-switchBase.Mui-checked + .MuiSwitch-track': {
                          backgroundColor: theme.palette.success.main,
                        },
                      }}
                    />
                  }
                  label="Active User"
                />
              </Grid>
              <Grid size={{ xs: 12 }}>
                <Box sx={{ display: 'flex', gap: 2, justifyContent: 'flex-end' }}>
                  <Button
                    variant="outlined"
                    startIcon={<CancelIcon />}
                    onClick={() => navigate('/users')}
                    sx={{ borderRadius: 2, textTransform: 'none' }}
                  >
                    Cancel
                  </Button>
                  <Button
                    type="submit"
                    variant="contained"
                    startIcon={<SaveIcon />}
                    disabled={loading}
                    sx={{
                      borderRadius: 2,
                      background: `linear-gradient(135deg, ${theme.palette.primary.main}, ${theme.palette.primary.dark})`,
                      boxShadow: `0 8px 24px ${alpha(theme.palette.primary.main, 0.35)}`,
                      '&:hover': {
                        boxShadow: `0 12px 32px ${alpha(theme.palette.primary.main, 0.45)}`,
                      },
                      textTransform: 'none',
                      fontWeight: 600,
                    }}
                  >
                    {loading ? 'Saving...' : isEdit ? 'Update User' : 'Create User'}
                  </Button>
                </Box>
              </Grid>
            </Grid>
          </form>
        </Paper>
      </Box>
    </Layout>
  );
};

export default UserForm;