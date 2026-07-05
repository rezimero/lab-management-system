import React, { useEffect, useState } from 'react';
import {
  Grid,
  Paper,
  Typography,
  Box,
  Card,
  CardContent,
  LinearProgress,
  Avatar,
  alpha,
  useTheme,
  Chip,
} from '@mui/material';
import {
  People,
  Science,
  Pending,
  CheckCircle,
  PersonAdd,
  AddCircle,
  Assessment,
} from '@mui/icons-material';
import Layout from '../components/Layout/Layout';
import api from '../api/client';
import { useAuth } from '../contexts/AuthContext';
import { motion } from 'framer-motion';

interface Stats {
  total_patients: number;
  active_patients: number;
  inactive_patients: number;
}

interface TestStats {
  total_tests: number;
  pending: number;
  in_progress: number;
  completed: number;
  cancelled: number;
}

const Dashboard: React.FC = () => {
  const { user, tenant } = useAuth();
  const theme = useTheme();
  const [patientStats, setPatientStats] = useState<Stats | null>(null);
  const [testStats, setTestStats] = useState<TestStats | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchStats();
  }, []);

  const fetchStats = async () => {
    setLoading(true);
    try {
      const [patientRes, testRes] = await Promise.all([
        api.get('/patients/stats'),
        api.get('/test-results/stats'),
      ]);
      setPatientStats(patientRes.data);
      setTestStats(testRes.data);
    } catch (error) {
      console.error('Error fetching stats:', error);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <Layout title="Dashboard">
        <Box sx={{ width: '100%', mt: 4 }}>
          <LinearProgress />
        </Box>
      </Layout>
    );
  }

  const statCards = [
    {
      title: 'Total Patients',
      value: patientStats?.total_patients || 0,
      icon: <People sx={{ fontSize: 32 }} />,
      color: '#4361ee',
      subtitle: `${patientStats?.active_patients || 0} active`,
      change: '+12%',
    },
    {
      title: 'Total Tests',
      value: testStats?.total_tests || 0,
      icon: <Science sx={{ fontSize: 32 }} />,
      color: '#2ec4b6',
      subtitle: `${testStats?.completed || 0} completed`,
      change: '+8%',
    },
    {
      title: 'Pending Tests',
      value: testStats?.pending || 0,
      icon: <Pending sx={{ fontSize: 32 }} />,
      color: '#f8961e',
      subtitle: `${testStats?.in_progress || 0} in progress`,
      change: '-3%',
    },
    {
      title: 'Completed Tests',
      value: testStats?.completed || 0,
      icon: <CheckCircle sx={{ fontSize: 32 }} />,
      color: '#06d6a0',
      subtitle: `${testStats?.cancelled || 0} cancelled`,
      change: '+15%',
    },
  ];

  const quickActions = [
    { title: 'New Patient', icon: <PersonAdd />, path: '/patients/new', color: '#4361ee' },
    { title: 'New Test', icon: <AddCircle />, path: '/lab-tests/new', color: '#2ec4b6' },
    { title: 'View Reports', icon: <Assessment />, path: '/test-results', color: '#f8961e' },
    { title: 'All Patients', icon: <People />, path: '/patients', color: '#7209b7' },
  ];

  return (
    <Layout title="Dashboard">
      <Box sx={{ mb: 4 }}>
        <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: 2 }}>
          <Box>
            <Typography variant="h4" sx={{ fontWeight: 700, mb: 0.5 }}>
              Welcome back, {user?.name}! 👋
            </Typography>
            <Typography variant="body1" color="text.secondary">
              {tenant?.name} · {tenant?.domain}
            </Typography>
          </Box>
          <Chip
            label={`${new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}`}
            variant="outlined"
            sx={{ borderRadius: 2, borderColor: alpha(theme.palette.divider, 0.2) }}
          />
        </Box>
      </Box>

      <Grid container spacing={3}>
        {statCards.map((stat, index) => (
          <Grid size={{ xs: 12, sm: 6, md: 3 }} key={index}>
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: index * 0.1 }}
            >
              <Card
                sx={{
                  height: '100%',
                  borderRadius: 4,
                  position: 'relative',
                  overflow: 'hidden',
                  background: `linear-gradient(135deg, ${alpha(stat.color, 0.05)}, ${alpha(theme.palette.background.paper, 0.9)})`,
                  border: `1px solid ${alpha(stat.color, 0.1)}`,
                  transition: 'all 0.3s ease',
                  '&:hover': {
                    transform: 'translateY(-4px)',
                    boxShadow: `0 12px 40px ${alpha(stat.color, 0.15)}`,
                  },
                }}
              >
                <CardContent>
                  <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
                    <Box>
                      <Typography variant="body2" color="text.secondary" sx={{ fontWeight: 500 }}>
                        {stat.title}
                      </Typography>
                      <Typography variant="h3" sx={{ fontWeight: 700, mt: 1, color: stat.color }}>
                        {stat.value}
                      </Typography>
                      <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mt: 1 }}>
                        <Typography variant="caption" color="text.secondary">
                          {stat.subtitle}
                        </Typography>
                        <Chip
                          label={stat.change}
                          size="small"
                          sx={{
                            height: 20,
                            fontSize: '0.625rem',
                            backgroundColor: alpha(stat.change.startsWith('+') ? '#06d6a0' : '#ef476f', 0.1),
                            color: stat.change.startsWith('+') ? '#06d6a0' : '#ef476f',
                            fontWeight: 600,
                          }}
                        />
                      </Box>
                    </Box>
                    <Avatar
                      sx={{
                        width: 48,
                        height: 48,
                        bgcolor: alpha(stat.color, 0.1),
                        color: stat.color,
                      }}
                    >
                      {stat.icon}
                    </Avatar>
                  </Box>
                </CardContent>
              </Card>
            </motion.div>
          </Grid>
        ))}
      </Grid>

      <Grid container spacing={3} sx={{ mt: 2 }}>
        <Grid size={{ xs: 12, md: 6 }}>
          <Paper
            elevation={0}
            sx={{
              p: 3,
              borderRadius: 4,
              border: `1px solid ${alpha(theme.palette.divider, 0.08)}`,
            }}
          >
            <Typography variant="h6" sx={{ fontWeight: 600, mb: 2 }}>
              Quick Actions
            </Typography>
            <Grid container spacing={2}>
              {quickActions.map((action) => (
                <Grid size={{ xs: 6 }} key={action.title}>
                  <Card
                    sx={{
                      p: 2,
                      textAlign: 'center',
                      cursor: 'pointer',
                      borderRadius: 3,
                      transition: 'all 0.3s ease',
                      border: `1px solid ${alpha(action.color, 0.1)}`,
                      '&:hover': {
                        transform: 'translateY(-2px)',
                        boxShadow: `0 8px 24px ${alpha(action.color, 0.15)}`,
                        borderColor: action.color,
                      },
                    }}
                    onClick={() => window.location.href = action.path}
                  >
                    <Avatar
                      sx={{
                        width: 48,
                        height: 48,
                        mx: 'auto',
                        mb: 1,
                        bgcolor: alpha(action.color, 0.1),
                        color: action.color,
                      }}
                    >
                      {action.icon}
                    </Avatar>
                    <Typography variant="body2" sx={{ fontWeight: 500 }}>
                      {action.title}
                    </Typography>
                  </Card>
                </Grid>
              ))}
            </Grid>
          </Paper>
        </Grid>

        <Grid size={{ xs: 12, md: 6 }}>
          <Paper
            elevation={0}
            sx={{
              p: 3,
              borderRadius: 4,
              border: `1px solid ${alpha(theme.palette.divider, 0.08)}`,
            }}
          >
            <Typography variant="h6" sx={{ fontWeight: 600, mb: 2 }}>
              Test Results Summary
            </Typography>
            <Box sx={{ mt: 2 }}>
              {testStats && (
                <>
                  <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 1 }}>
                    <Typography variant="body2">Completed</Typography>
                    <Typography variant="body2" color="success.main" sx={{ fontWeight: 600 }}>
                      {testStats.completed}
                    </Typography>
                  </Box>
                  <LinearProgress
                    variant="determinate"
                    value={(testStats.completed / testStats.total_tests) * 100 || 0}
                    sx={{ mb: 2, height: 8, borderRadius: 4 }}
                    color="success"
                  />
                  <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 1 }}>
                    <Typography variant="body2">Pending</Typography>
                    <Typography variant="body2" color="warning.main" sx={{ fontWeight: 600 }}>
                      {testStats.pending}
                    </Typography>
                  </Box>
                  <LinearProgress
                    variant="determinate"
                    value={(testStats.pending / testStats.total_tests) * 100 || 0}
                    sx={{ mb: 2, height: 8, borderRadius: 4 }}
                    color="warning"
                  />
                  <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 1 }}>
                    <Typography variant="body2">In Progress</Typography>
                    <Typography variant="body2" color="info.main" sx={{ fontWeight: 600 }}>
                      {testStats.in_progress}
                    </Typography>
                  </Box>
                  <LinearProgress
                    variant="determinate"
                    value={(testStats.in_progress / testStats.total_tests) * 100 || 0}
                    sx={{ mb: 2, height: 8, borderRadius: 4 }}
                    color="info"
                  />
                  <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 1 }}>
                    <Typography variant="body2">Cancelled</Typography>
                    <Typography variant="body2" color="error.main" sx={{ fontWeight: 600 }}>
                      {testStats.cancelled}
                    </Typography>
                  </Box>
                  <LinearProgress
                    variant="determinate"
                    value={(testStats.cancelled / testStats.total_tests) * 100 || 0}
                    sx={{ height: 8, borderRadius: 4 }}
                    color="error"
                  />
                </>
              )}
            </Box>
          </Paper>
        </Grid>
      </Grid>
    </Layout>
  );
};

export default Dashboard;