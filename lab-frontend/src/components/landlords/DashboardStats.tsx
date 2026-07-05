import React, { useEffect, useState } from 'react';
import {
  Grid,
  Card,
  CardContent,
  Typography,
  CircularProgress,
  Box,
  Chip,
} from '@mui/material';
import {
  Business as BusinessIcon,
  People as PeopleIcon,
  MedicalServices as MedicalIcon,
  Science as ScienceIcon,
  Assignment as AssignmentIcon,
} from '@mui/icons-material';
import api from '../../api/client';

interface TenantStats {
  users: number;
  patients: number;
  lab_tests: number;
  test_results: number;
}

interface Tenant {
  id: number;
  name: string;
  domain: string;
  is_active: boolean;
  stats?: TenantStats;
}

interface DashboardStatsType {
  total_tenants: number;
  total_users: number;
  total_patients: number;
  total_lab_tests: number;
  total_test_results: number;
  tenants: Tenant[];
}

const DashboardStats: React.FC = () => {
  const [stats, setStats] = useState<DashboardStatsType | null>(null);
  const [loading, setLoading] = useState<boolean>(true);

  useEffect(() => {
    const fetchStats = async () => {
      try {
        const res = await api.get('/landlord/dashboard/stats');
        console.log('Stats response:', res.data);
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

  const statCards = [
    { title: 'Total Tenants', value: stats?.total_tenants || 0, icon: <BusinessIcon sx={{ fontSize: 40 }} />, color: '#4361ee' },
    { title: 'Total Users', value: stats?.total_users || 0, icon: <PeopleIcon sx={{ fontSize: 40 }} />, color: '#2ec4b6' },
    { title: 'Total Patients', value: stats?.total_patients || 0, icon: <MedicalIcon sx={{ fontSize: 40 }} />, color: '#06d6a0' },
    { title: 'Total Lab Tests', value: stats?.total_lab_tests || 0, icon: <ScienceIcon sx={{ fontSize: 40 }} />, color: '#f8961e' },
    { title: 'Total Test Results', value: stats?.total_test_results || 0, icon: <AssignmentIcon sx={{ fontSize: 40 }} />, color: '#ef476f' },
  ];

  return (
    <Box>
      <Typography variant="h5" gutterBottom sx={{ mb: 3 }}>
        📊 Platform Overview
      </Typography>
      <Grid container spacing={3}>
        {statCards.map((stat) => (
          <Grid size={{ xs: 12, sm: 6, md: 2.4 }} key={stat.title}>
            <Card sx={{ height: '100%' }}>
              <CardContent>
                <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                  <Box>
                    <Typography color="textSecondary" gutterBottom variant="body2">
                      {stat.title}
                    </Typography>
                    <Typography variant="h4" sx={{ fontWeight: 'bold' }}>
                      {stat.value}
                    </Typography>
                  </Box>
                  <Box sx={{ color: stat.color }}>{stat.icon}</Box>
                </Box>
              </CardContent>
            </Card>
          </Grid>
        ))}
      </Grid>

      {/* Tenant Breakdown */}
      {stats?.tenants && stats.tenants.length > 0 && (
        <>
          <Typography variant="h6" sx={{ mt: 4, mb: 2 }}>
            Tenant Breakdown
          </Typography>
          <Grid container spacing={2}>
            {stats.tenants.map((tenant: Tenant) => (
              <Grid size={{ xs: 12, sm: 6, md: 4 }} key={tenant.id}>
                <Card>
                  <CardContent>
                    <Typography variant="h6">{tenant.name}</Typography>
                    <Typography variant="body2" color="textSecondary">
                      Domain: {tenant.domain}
                    </Typography>
                    <Chip
                      label={tenant.is_active ? 'Active' : 'Suspended'}
                      color={tenant.is_active ? 'success' : 'error'}
                      size="small"
                      sx={{ mt: 1 }}
                    />
                    <Box sx={{ mt: 2, display: 'flex', gap: 2, flexWrap: 'wrap' }}>
                      <Typography variant="body2">👤 {tenant.stats?.users || 0} users</Typography>
                      <Typography variant="body2">🩺 {tenant.stats?.patients || 0} patients</Typography>
                      <Typography variant="body2">🧪 {tenant.stats?.lab_tests || 0} tests</Typography>
                    </Box>
                  </CardContent>
                </Card>
              </Grid>
            ))}
          </Grid>
        </>
      )}
    </Box>
  );
};

export default DashboardStats;