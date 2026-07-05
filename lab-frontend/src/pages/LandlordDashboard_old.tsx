import React, { useEffect, useState } from 'react';
import { Routes, Route, Link, useNavigate } from 'react-router-dom';
import {
  Box,
  Typography,
  Card,
  CardContent,
  Grid,
  Button,
  AppBar,
  Toolbar,
  Container,
  Paper,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Chip,
  CircularProgress,
} from '@mui/material';
import {
  Dashboard as DashboardIcon,
  People as PeopleIcon,
  Business as BusinessIcon,
  BarChart as BarChartIcon,
  Logout as LogoutIcon,
  AdminPanelSettings as AdminIcon,
} from '@mui/icons-material';
import { useAuth } from '../contexts/AuthContext';
import api from '../api/client';

interface User {
  id: number;
  name: string;
  email: string;
  role: string;
  tenant_name?: string;
  tenant_domain?: string;
  is_active: boolean;
}

interface Stats {
  total_tenants: number;
  total_users: number;
  total_patients: number;
  total_lab_tests: number;
  total_test_results: number;
}

const DashboardStats: React.FC = () => {
  const [stats, setStats] = useState<Stats | null>(null);
  const [loading, setLoading] = useState<boolean>(true);

  useEffect(() => {
    const fetchStats = async () => {
      try {
        const res = await api.get('/landlord/dashboard/stats');
        setStats(res.data);
      } catch (error) {
        console.error('Error fetching stats:', error);
      } finally {
        setLoading(false);
      }
    };
    fetchStats();
  }, []);

  if (loading) return <CircularProgress />;

  return (
    <Grid container spacing={3}>
      <Grid size={{ xs: 12, sm: 6, md: 3 }}>
        <Card>
          <CardContent>
            <Typography color="textSecondary" gutterBottom>
              Total Tenants
            </Typography>
            <Typography variant="h4">{stats?.total_tenants || 0}</Typography>
          </CardContent>
        </Card>
      </Grid>
      <Grid size={{ xs: 12, sm: 6, md: 3 }}>
        <Card>
          <CardContent>
            <Typography color="textSecondary" gutterBottom>
              Total Users
            </Typography>
            <Typography variant="h4">{stats?.total_users || 0}</Typography>
          </CardContent>
        </Card>
      </Grid>
      <Grid size={{ xs: 12, sm: 6, md: 3 }}>
        <Card>
          <CardContent>
            <Typography color="textSecondary" gutterBottom>
              Total Patients
            </Typography>
            <Typography variant="h4">{stats?.total_patients || 0}</Typography>
          </CardContent>
        </Card>
      </Grid>
      <Grid size={{ xs: 12, sm: 6, md: 3 }}>
        <Card>
          <CardContent>
            <Typography color="textSecondary" gutterBottom>
              Total Lab Tests
            </Typography>
            <Typography variant="h4">{stats?.total_lab_tests || 0}</Typography>
          </CardContent>
        </Card>
      </Grid>
    </Grid>
  );
};

const AllUsers: React.FC = () => {
  const [users, setUsers] = useState<User[]>([]);
  const [loading, setLoading] = useState<boolean>(true);

  useEffect(() => {
    const fetchUsers = async () => {
      try {
        const res = await api.get('/landlord/users');
        setUsers(res.data.users.data);
      } catch (error) {
        console.error('Error fetching users:', error);
      } finally {
        setLoading(false);
      }
    };
    fetchUsers();
  }, []);

  if (loading) return <CircularProgress />;

  return (
    <TableContainer component={Paper}>
      <Table>
        <TableHead>
          <TableRow>
            <TableCell>Name</TableCell>
            <TableCell>Email</TableCell>
            <TableCell>Role</TableCell>
            <TableCell>Tenant</TableCell>
            <TableCell>Status</TableCell>
          </TableRow>
        </TableHead>
        <TableBody>
          {users.map((user) => (
            <TableRow key={user.id}>
              <TableCell>{user.name}</TableCell>
              <TableCell>{user.email}</TableCell>
              <TableCell>
                <Chip 
                  label={user.role} 
                  color={user.role === 'superadmin' ? 'secondary' : 'primary'}
                  size="small"
                />
              </TableCell>
              <TableCell>{user.tenant_name || 'N/A'}</TableCell>
              <TableCell>
                <Chip 
                  label={user.is_active ? 'Active' : 'Inactive'} 
                  color={user.is_active ? 'success' : 'error'}
                  size="small"
                />
              </TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>
    </TableContainer>
  );
};

const LandlordDashboard: React.FC = () => {
  const { logout, user } = useAuth();
  const navigate = useNavigate();

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  return (
    <Box sx={{ flexGrow: 1 }}>
      <AppBar position="static" sx={{ bgcolor: '#1a1a2e' }}>
        <Toolbar>
          <AdminIcon sx={{ mr: 2 }} />
          <Typography variant="h6" sx={{ flexGrow: 1 }}>
            🏢 Landlord Dashboard (God Mode)
          </Typography>
          <Chip 
            label="Superadmin" 
            color="secondary" 
            sx={{ mr: 2 }}
          />
          <Typography variant="body2" sx={{ mr: 2 }}>
            {user?.email}
          </Typography>
          <Button 
            color="inherit" 
            onClick={handleLogout}
            startIcon={<LogoutIcon />}
          >
            Logout
          </Button>
        </Toolbar>
      </AppBar>

      <Container sx={{ mt: 4 }}>
        <Box sx={{ display: 'flex', gap: 2, mb: 4, flexWrap: 'wrap' }}>
          <Button 
            component={Link} 
            to="/landlord" 
            variant="contained" 
            startIcon={<DashboardIcon />}
          >
            Overview
          </Button>
          <Button 
            component={Link} 
            to="/landlord/users" 
            variant="outlined" 
            startIcon={<PeopleIcon />}
          >
            All Users
          </Button>
          <Button 
            component={Link} 
            to="/landlord/tenants" 
            variant="outlined" 
            startIcon={<BusinessIcon />}
          >
            All Tenants
          </Button>
          <Button 
            component={Link} 
            to="/landlord/stats" 
            variant="outlined" 
            startIcon={<BarChartIcon />}
          >
            Statistics
          </Button>
        </Box>

        <Routes>
          <Route index element={<DashboardStats />} />
          <Route path="users" element={<AllUsers />} />
          <Route path="stats" element={<DashboardStats />} />
          <Route 
            path="tenants" 
            element={
              <Typography variant="body1">
                Tenant management coming soon...
              </Typography>
            } 
          />
        </Routes>
      </Container>
    </Box>
  );
};

export default LandlordDashboard;