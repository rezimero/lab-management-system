import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  Box,
  Paper,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  TablePagination,
  Button,
  IconButton,
  Chip,
  TextField,
  InputAdornment,
  Dialog,
  DialogActions,
  DialogContent,
  DialogContentText,
  DialogTitle,
  Typography,
  Avatar,
  alpha,
  useTheme,
  Alert,
} from '@mui/material';
import {
  Add as AddIcon,
  Search as SearchIcon,
  Edit as EditIcon,
  Delete as DeleteIcon,
  Person as PersonIcon,
  AdminPanelSettings as AdminIcon,
  Science as TechnicianIcon,
  MedicalServices as DoctorIcon,
  SupportAgent as ReceptionistIcon,
  Verified as SuperAdminIcon,
} from '@mui/icons-material';
import Layout from '../components/Layout/Layout';
import api from '../api/client';
import { User } from '../types';
import { useAuth } from '../contexts/AuthContext';

const Users: React.FC = () => {
  const navigate = useNavigate();
  const theme = useTheme();
  const { user: currentUser, tenant, isSuperadmin } = useAuth();
  const [users, setUsers] = useState<User[]>([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(0);
  const [rowsPerPage, setRowsPerPage] = useState(10);
  const [total, setTotal] = useState(0);
  const [search, setSearch] = useState('');
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
  const [selectedUser, setSelectedUser] = useState<User | null>(null);
  const [error, setError] = useState<string | null>(null);

  const isAdmin = currentUser?.role === 'admin' || isSuperadmin;

  useEffect(() => {
    fetchUsers();
  }, [page, rowsPerPage, search]);

  const fetchUsers = async () => {
    setLoading(true);
    setError(null);
    try {
      console.log('Fetching users...');
      console.log('Current user:', currentUser);
      console.log('Tenant:', tenant);
      console.log('Is Superadmin:', isSuperadmin);
      
      // Get token from localStorage
      const token = localStorage.getItem('token');
      console.log('Token present:', !!token);

      if (!token) {
        setError('No authentication token found. Please login again.');
        setLoading(false);
        return;
      }

      const response = await api.get('/users', {
        params: { page: page + 1, per_page: rowsPerPage, search },
      });
      
      console.log('Users response:', response.data);
      
      // Handle different response structures
      let usersData = [];
      let totalCount = 0;
      
      if (response.data.users && response.data.users.data) {
        usersData = response.data.users.data;
        totalCount = response.data.users.total || 0;
      } else if (Array.isArray(response.data.users)) {
        usersData = response.data.users;
        totalCount = usersData.length;
      } else if (Array.isArray(response.data)) {
        usersData = response.data;
        totalCount = usersData.length;
      }
      
      setUsers(usersData);
      setTotal(totalCount);
    } catch (error: any) {
      console.error('Error fetching users:', error);
      console.error('Error response:', error.response?.data);
      setError(error.response?.data?.message || 'Failed to fetch users');
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async () => {
    if (!selectedUser) return;
    try {
      await api.delete(`/users/${selectedUser.id}`);
      setDeleteDialogOpen(false);
      fetchUsers();
    } catch (error: any) {
      console.error('Error deleting user:', error);
      alert(error.response?.data?.message || 'Failed to delete user');
    }
  };

  const getRoleIcon = (role: string) => {
    const config = {
      superadmin: { icon: <SuperAdminIcon />, color: '#ef476f' },
      admin: { icon: <AdminIcon />, color: '#4361ee' },
      lab_technician: { icon: <TechnicianIcon />, color: '#2ec4b6' },
      receptionist: { icon: <ReceptionistIcon />, color: '#f8961e' },
      doctor: { icon: <DoctorIcon />, color: '#7209b7' },
    };
    return config[role as keyof typeof config] || config.admin;
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
        label={roleMap[role] || role}
        color={colors[role] || 'default'}
        size="small"
        sx={{
          fontWeight: 600,
          '& .MuiChip-label': { px: 1.5 },
        }}
      />
    );
  };

  const getInitials = (name: string) => {
    return name
      .split(' ')
      .map((n) => n[0])
      .join('')
      .toUpperCase()
      .slice(0, 2);
  };

  const canEditUser = (user: User) => {
    if (currentUser?.id === user.id && !isSuperadmin) return false;
    if (user.role === 'superadmin' && !isSuperadmin) return false;
    return isAdmin;
  };

  const canDeleteUser = (user: User) => {
    if (currentUser?.id === user.id) return false;
    if (user.role === 'superadmin' && !isSuperadmin) return false;
    return isAdmin;
  };

  const canSeeActions = (user: User) => {
    if (isSuperadmin) return true;
    if (isAdmin && user.role !== 'superadmin') return true;
    return false;
  };

  if (error) {
    return (
      <Layout title="User Management">
        <Box sx={{ p: 3 }}>
          <Alert 
            severity="error" 
            action={
              <Button color="inherit" size="small" onClick={fetchUsers}>
                Retry
              </Button>
            }
          >
            {error}
          </Alert>
        </Box>
      </Layout>
    );
  }

  return (
    <Layout title="User Management">
      <Box sx={{ mb: 4 }}>
        <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: 2 }}>
          <Box>
            <Typography variant="h5" sx={{ fontWeight: 700, color: 'text.primary' }}>
              User Management
            </Typography>
            <Typography variant="body2" color="text.secondary">
              {isSuperadmin 
                ? 'Manage all system users across all tenants' 
                : `Manage team members in ${tenant?.name || 'your lab'}`}
            </Typography>
          </Box>
          {isAdmin && (
            <Button
              variant="contained"
              startIcon={<AddIcon />}
              onClick={() => navigate('/users/new')}
              sx={{
                background: `linear-gradient(135deg, ${theme.palette.primary.main}, ${theme.palette.primary.dark})`,
                px: 4,
                py: 1.2,
                borderRadius: 3,
                boxShadow: `0 8px 24px ${alpha(theme.palette.primary.main, 0.35)}`,
                '&:hover': {
                  boxShadow: `0 12px 32px ${alpha(theme.palette.primary.main, 0.45)}`,
                  transform: 'translateY(-2px)',
                },
                transition: 'all 0.3s ease',
                textTransform: 'none',
                fontWeight: 600,
              }}
            >
              Add User
            </Button>
          )}
        </Box>
      </Box>

      <Paper
        elevation={0}
        sx={{
          p: 3,
          borderRadius: 4,
          border: `1px solid ${alpha(theme.palette.divider, 0.08)}`,
          backdropFilter: 'blur(10px)',
          backgroundColor: alpha(theme.palette.background.paper, 0.6),
        }}
      >
        <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 3, flexWrap: 'wrap', gap: 2 }}>
          <TextField
            placeholder="Search users..."
            size="small"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            slotProps={{
              input: {
                startAdornment: (
                  <InputAdornment position="start">
                    <SearchIcon sx={{ color: 'text.secondary' }} />
                  </InputAdornment>
                ),
                sx: {
                  borderRadius: 3,
                  backgroundColor: alpha(theme.palette.background.default, 0.6),
                  '&:hover': {
                    backgroundColor: alpha(theme.palette.background.default, 0.8),
                  },
                },
              },
            }}
            sx={{ width: 300 }}
          />
          <Typography variant="body2" color="text.secondary">
            <strong>{total}</strong> users total
          </Typography>
        </Box>

        <TableContainer>
          <Table>
            <TableHead>
              <TableRow sx={{ backgroundColor: alpha(theme.palette.background.default, 0.4) }}>
                <TableCell sx={{ fontWeight: 700, color: 'text.secondary' }}>User</TableCell>
                <TableCell sx={{ fontWeight: 700, color: 'text.secondary' }}>Email</TableCell>
                <TableCell sx={{ fontWeight: 700, color: 'text.secondary' }}>Role</TableCell>
                <TableCell sx={{ fontWeight: 700, color: 'text.secondary' }}>Status</TableCell>
                <TableCell align="right" sx={{ fontWeight: 700, color: 'text.secondary' }}>Actions</TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {loading ? (
                <TableRow>
                  <TableCell colSpan={5} align="center" sx={{ py: 8 }}>
                    <Typography color="text.secondary">Loading users...</Typography>
                  </TableCell>
                </TableRow>
              ) : users.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={5} align="center" sx={{ py: 8 }}>
                    <Typography color="text.secondary">No users found in this tenant</Typography>
                  </TableCell>
                </TableRow>
              ) : (
                users.map((user) => {
                  const roleConfig = getRoleIcon(user.role);
                  const isSelf = currentUser?.id === user.id;
                  const isSuperadminUser = user.role === 'superadmin';
                  
                  return (
                    <TableRow
                      key={user.id}
                      hover
                      sx={{
                        '&:hover': {
                          backgroundColor: alpha(theme.palette.primary.main, 0.02),
                        },
                        transition: 'background-color 0.2s ease',
                        ...(isSuperadminUser && {
                          backgroundColor: alpha(theme.palette.error.main, 0.03),
                        }),
                      }}
                    >
                      <TableCell>
                        <Box sx={{ display: 'flex', alignItems: 'center', gap: 2 }}>
                          <Avatar
                            sx={{
                              width: 40,
                              height: 40,
                              bgcolor: roleConfig.color,
                              fontWeight: 700,
                              fontSize: '0.875rem',
                              boxShadow: `0 4px 12px ${alpha(roleConfig.color, 0.25)}`,
                            }}
                          >
                            {getInitials(user.name)}
                          </Avatar>
                          <Box>
                            <Typography variant="body2" sx={{ fontWeight: 600 }}>
                              {user.name}
                              {isSelf && (
                                <Chip
                                  label="You"
                                  size="small"
                                  sx={{ ml: 1, height: 20, fontSize: '0.625rem' }}
                                  color="info"
                                />
                              )}
                              {isSuperadminUser && isSuperadmin && (
                                <Chip
                                  label="Super Admin"
                                  size="small"
                                  sx={{ 
                                    ml: 1, 
                                    height: 20, 
                                    fontSize: '0.625rem', 
                                    backgroundColor: alpha(theme.palette.error.main, 0.1), 
                                    color: theme.palette.error.main 
                                  }}
                                />
                              )}
                            </Typography>
                            <Typography variant="caption" color="text.secondary">
                              {user.is_active ? 'Active' : 'Inactive'}
                            </Typography>
                          </Box>
                        </Box>
                      </TableCell>
                      <TableCell>{user.email}</TableCell>
                      <TableCell>{getRoleChip(user.role)}</TableCell>
                      <TableCell>
                        <Chip
                          label={user.is_active ? 'Active' : 'Inactive'}
                          color={user.is_active ? 'success' : 'default'}
                          size="small"
                          sx={{
                            fontWeight: 600,
                            ...(user.is_active && {
                              background: `linear-gradient(135deg, ${alpha(theme.palette.success.main, 0.15)}, ${alpha(theme.palette.success.light, 0.05)})`,
                              border: `1px solid ${alpha(theme.palette.success.main, 0.3)}`,
                            }),
                          }}
                        />
                      </TableCell>
                      <TableCell align="right">
                        {canEditUser(user) && (
                          <IconButton
                            size="small"
                            onClick={() => navigate(`/users/${user.id}/edit`)}
                            sx={{
                              color: theme.palette.primary.main,
                              '&:hover': {
                                backgroundColor: alpha(theme.palette.primary.main, 0.08),
                              },
                            }}
                          >
                            <EditIcon fontSize="small" />
                          </IconButton>
                        )}
                        {canDeleteUser(user) && (
                          <IconButton
                            size="small"
                            onClick={() => {
                              setSelectedUser(user);
                              setDeleteDialogOpen(true);
                            }}
                            sx={{
                              color: theme.palette.error.main,
                              '&:hover': {
                                backgroundColor: alpha(theme.palette.error.main, 0.08),
                              },
                            }}
                          >
                            <DeleteIcon fontSize="small" />
                          </IconButton>
                        )}
                        {!canEditUser(user) && !canDeleteUser(user) && (
                          <Typography variant="caption" color="text.secondary">
                            {isSuperadminUser && !isSuperadmin ? 'Protected' : 'Read only'}
                          </Typography>
                        )}
                      </TableCell>
                    </TableRow>
                  );
                })
              )}
            </TableBody>
          </Table>
        </TableContainer>
        <TablePagination
          rowsPerPageOptions={[5, 10, 25]}
          component="div"
          count={total}
          rowsPerPage={rowsPerPage}
          page={page}
          onPageChange={(_, newPage) => setPage(newPage)}
          onRowsPerPageChange={(e) => setRowsPerPage(parseInt(e.target.value, 10))}
          sx={{
            borderTop: `1px solid ${alpha(theme.palette.divider, 0.06)}`,
          }}
        />
      </Paper>

      <Dialog
        open={deleteDialogOpen}
        onClose={() => setDeleteDialogOpen(false)}
        maxWidth="sm"
        slotProps={{
          paper: {
            sx: {
              borderRadius: 4,
              p: 1,
            },
          },
        }}
      >
        <DialogTitle sx={{ fontWeight: 700 }}>Confirm Deletion</DialogTitle>
        <DialogContent>
          <DialogContentText>
            Are you sure you want to delete user "<strong>{selectedUser?.name}</strong>"? This action cannot be undone.
          </DialogContentText>
        </DialogContent>
        <DialogActions sx={{ p: 2.5 }}>
          <Button
            onClick={() => setDeleteDialogOpen(false)}
            variant="outlined"
            sx={{ borderRadius: 2, textTransform: 'none' }}
          >
            Cancel
          </Button>
          <Button
            onClick={handleDelete}
            color="error"
            variant="contained"
            sx={{
              borderRadius: 2,
              background: `linear-gradient(135deg, ${theme.palette.error.main}, ${theme.palette.error.dark})`,
              textTransform: 'none',
            }}
          >
            Delete User
          </Button>
        </DialogActions>
      </Dialog>
    </Layout>
  );
};

export default Users;