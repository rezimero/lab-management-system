import React, { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import {
  Box,
  Paper,
  Typography,
  Grid,
  Chip,
  Divider,
  Button,
  CircularProgress,
  Alert,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  IconButton,
  alpha,
  useTheme,
} from '@mui/material';
import {
  ArrowBack as ArrowBackIcon,
  Print as PrintIcon,
  PictureAsPdf as PdfIcon,
  CheckCircle as CheckCircleIcon,
  Pending as PendingIcon,
  Cancel as CancelIcon,
  HourglassEmpty as InProgressIcon,
  Science as ScienceIcon,
  Person as PersonIcon,
} from '@mui/icons-material';
import Layout from '../components/Layout/Layout';
import api from '../api/client';

interface TestResult {
  id: number;
  patient_id: number;
  lab_test_id: number;
  user_id: number;
  test_date: string;
  results_data: any;
  notes: string | null;
  status: 'pending' | 'in_progress' | 'completed' | 'cancelled';
  completed_at: string | null;
  created_at: string;
  updated_at: string;
  patient?: {
    id: number;
    first_name: string;
    last_name: string;
    email: string | null;
    phone: string | null;
    date_of_birth: string;
    gender: string;
    medical_record_number: string;
    address: string | null;
  };
  lab_test?: {
    id: number;
    name: string;
    description: string | null;
    category: string;
    price: string;
    turnaround_time_hours: number;
    parameters: any[];
  };
  user?: {
    id: number;
    name: string;
    email: string;
  };
}

const TestResultView: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const theme = useTheme();
  const [result, setResult] = useState<TestResult | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    fetchResult();
  }, [id]);

  const fetchResult = async () => {
    setLoading(true);
    setError(null);
    try {
      const response = await api.get(`/test-results/${id}`);
      setResult(response.data);
    } catch (error: any) {
      console.error('Error fetching test result:', error);
      setError(error.response?.data?.message || 'Failed to load test result');
    } finally {
      setLoading(false);
    }
  };

  const handlePrint = () => {
    window.print();
  };

  const getStatusChip = (status: string) => {
    const config: Record<string, { label: string; color: 'success' | 'warning' | 'info' | 'error'; icon: React.ReactElement }> = {
      completed: { label: 'Completed', color: 'success', icon: <CheckCircleIcon /> },
      pending: { label: 'Pending', color: 'warning', icon: <PendingIcon /> },
      in_progress: { label: 'In Progress', color: 'info', icon: <InProgressIcon /> },
      cancelled: { label: 'Cancelled', color: 'error', icon: <CancelIcon /> },
    };
    const { label, color, icon } = config[status] || config.pending;
    return <Chip icon={icon} label={label} color={color} size="medium" />;
  };

  const getStatusColor = (status: string) => {
    const colors: Record<string, string> = {
      completed: '#06d6a0',
      pending: '#f8961e',
      in_progress: '#4361ee',
      cancelled: '#ef476f',
    };
    return colors[status] || '#f8961e';
  };

  const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    });
  };

  const formatDateTime = (date: string) => {
    return new Date(date).toLocaleString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  const getResultsArray = () => {
    if (!result?.results_data) return [];
    if (Array.isArray(result.results_data)) return result.results_data;
    if (typeof result.results_data === 'object') return Object.values(result.results_data);
    return [];
  };

  const resultsArray = getResultsArray();

  if (loading) {
    return (
      <Layout title="Test Result">
        <Box sx={{ display: 'flex', justifyContent: 'center', py: 8 }}>
          <CircularProgress />
        </Box>
      </Layout>
    );
  }

  if (error || !result) {
    return (
      <Layout title="Test Result">
        <Alert severity="error" sx={{ mt: 2 }}>{error || 'Test result not found'}</Alert>
      </Layout>
    );
  }

  const statusColor = getStatusColor(result.status);

  return (
    <Layout title="Test Result">
      <style>
        {`
          @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }
            .print-paper { 
              box-shadow: none !important; 
              border: none !important;
              padding: 20px !important;
            }
            body { background: white !important; }
          }
          .print-only { display: none; }
        `}
      </style>

      <Box sx={{ mb: 3, display: 'flex', justifyContent: 'space-between', alignItems: 'center' }} className="no-print">
        <Box sx={{ display: 'flex', alignItems: 'center', gap: 2 }}>
          <IconButton onClick={() => navigate('/test-results')}>
            <ArrowBackIcon />
          </IconButton>
          <Typography variant="h5" sx={{ fontWeight: 700 }}>
            Test Result Report
          </Typography>
        </Box>
        <Box sx={{ display: 'flex', gap: 2 }}>
          <Button
            variant="outlined"
            startIcon={<PrintIcon />}
            onClick={handlePrint}
            sx={{ borderRadius: 2 }}
          >
            Print
          </Button>
          <Button
            variant="contained"
            startIcon={<PdfIcon />}
            onClick={handlePrint}
            sx={{
              borderRadius: 2,
              background: `linear-gradient(135deg, ${theme.palette.primary.main}, ${theme.palette.primary.dark})`,
            }}
          >
            Download PDF
          </Button>
        </Box>
      </Box>

      <Paper
        id="print-area"
        className="print-paper"
        sx={{
          p: 4,
          borderRadius: 3,
          backgroundColor: '#ffffff',
          border: `1px solid ${alpha(theme.palette.divider, 0.08)}`,
        }}
      >
        {/* Header */}
        <Box sx={{ mb: 4, textAlign: 'center', borderBottom: `2px solid ${alpha(theme.palette.primary.main, 0.2)}`, pb: 2 }}>
          <Typography variant="h4" sx={{ fontWeight: 800, color: theme.palette.primary.main }}>
            🧪 Lab Manager
          </Typography>
          <Typography variant="body2" color="text.secondary">
            Laboratory Test Result Report
          </Typography>
          <Typography variant="caption" color="text.secondary">
            Report ID: #{String(result.id).padStart(6, '0')} • Generated: {formatDateTime(new Date().toISOString())}
          </Typography>
        </Box>

        {/* Status Banner */}
        <Box
          sx={{
            bgcolor: alpha(statusColor, 0.08),
            borderLeft: `4px solid ${statusColor}`,
            p: 2,
            mb: 3,
            borderRadius: 1,
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'center',
          }}
        >
          <Box>
            <Typography variant="body2" color="text.secondary">Status</Typography>
            {getStatusChip(result.status)}
          </Box>
          <Box sx={{ textAlign: 'right' }}>
            <Typography variant="body2" color="text.secondary">Test Date</Typography>
            <Typography variant="body1" sx={{ fontWeight: 600 }}>
              {formatDate(result.test_date)}
            </Typography>
          </Box>
        </Box>

        {/* Patient & Test Info */}
        <Grid container spacing={3} sx={{ mb: 3 }}>
          <Grid size={{ xs: 12, md: 6 }}>
            <Paper variant="outlined" sx={{ p: 2, bgcolor: alpha(theme.palette.background.default, 0.4) }}>
              <Typography variant="subtitle2" color="primary" sx={{ fontWeight: 700, mb: 1 }}>
                <PersonIcon sx={{ mr: 1, verticalAlign: 'middle' }} />
                Patient Information
              </Typography>
              <Divider sx={{ mb: 1.5 }} />
              <Grid container spacing={1}>
                <Grid size={{ xs: 6 }}>
                  <Typography variant="caption" color="text.secondary">Name</Typography>
                  <Typography variant="body2" sx={{ fontWeight: 500 }}>
                    {result.patient?.first_name} {result.patient?.last_name}
                  </Typography>
                </Grid>
                <Grid size={{ xs: 6 }}>
                  <Typography variant="caption" color="text.secondary">MRN</Typography>
                  <Typography variant="body2" sx={{ fontWeight: 500 }}>
                    {result.patient?.medical_record_number || 'N/A'}
                  </Typography>
                </Grid>
                <Grid size={{ xs: 6 }}>
                  <Typography variant="caption" color="text.secondary">DOB</Typography>
                  <Typography variant="body2">
                    {result.patient?.date_of_birth ? formatDate(result.patient.date_of_birth) : 'N/A'}
                  </Typography>
                </Grid>
                <Grid size={{ xs: 6 }}>
                  <Typography variant="caption" color="text.secondary">Gender</Typography>
                  <Typography variant="body2">
                    {result.patient?.gender || 'N/A'}
                  </Typography>
                </Grid>
                <Grid size={{ xs: 6 }}>
                  <Typography variant="caption" color="text.secondary">Email</Typography>
                  <Typography variant="body2" sx={{ wordBreak: 'break-all' }}>
                    {result.patient?.email || 'N/A'}
                  </Typography>
                </Grid>
                <Grid size={{ xs: 6 }}>
                  <Typography variant="caption" color="text.secondary">Phone</Typography>
                  <Typography variant="body2">
                    {result.patient?.phone || 'N/A'}
                  </Typography>
                </Grid>
              </Grid>
            </Paper>
          </Grid>

          <Grid size={{ xs: 12, md: 6 }}>
            <Paper variant="outlined" sx={{ p: 2, bgcolor: alpha(theme.palette.background.default, 0.4) }}>
              <Typography variant="subtitle2" color="primary" sx={{ fontWeight: 700, mb: 1 }}>
                <ScienceIcon sx={{ mr: 1, verticalAlign: 'middle' }} />
                Test Information
              </Typography>
              <Divider sx={{ mb: 1.5 }} />
              <Grid container spacing={1}>
                <Grid size={{ xs: 12 }}>
                  <Typography variant="caption" color="text.secondary">Test Name</Typography>
                  <Typography variant="body2" sx={{ fontWeight: 500 }}>
                    {result.lab_test?.name || 'N/A'}
                  </Typography>
                </Grid>
                <Grid size={{ xs: 6 }}>
                  <Typography variant="caption" color="text.secondary">Category</Typography>
                  <Typography variant="body2">
                    {result.lab_test?.category || 'N/A'}
                  </Typography>
                </Grid>
                <Grid size={{ xs: 6 }}>
                  <Typography variant="caption" color="text.secondary">Price</Typography>
                  <Typography variant="body2">
                    ${result.lab_test?.price || '0.00'}
                  </Typography>
                </Grid>
                <Grid size={{ xs: 12 }}>
                  <Typography variant="caption" color="text.secondary">Description</Typography>
                  <Typography variant="body2">
                    {result.lab_test?.description || 'No description available'}
                  </Typography>
                </Grid>
              </Grid>
            </Paper>
          </Grid>
        </Grid>

        {/* Test Results Table */}
        {resultsArray.length > 0 && (
          <Box sx={{ mb: 3 }}>
            <Typography variant="subtitle1" sx={{ fontWeight: 700, mb: 2 }}>
              📊 Test Results
            </Typography>
            <TableContainer component={Paper} variant="outlined">
              <Table>
                <TableHead sx={{ bgcolor: alpha(theme.palette.primary.main, 0.06) }}>
                  <TableRow>
                    <TableCell sx={{ fontWeight: 700 }}>Parameter</TableCell>
                    <TableCell sx={{ fontWeight: 700 }}>Value</TableCell>
                    <TableCell sx={{ fontWeight: 700 }}>Unit</TableCell>
                    <TableCell sx={{ fontWeight: 700 }}>Reference Range</TableCell>
                    <TableCell sx={{ fontWeight: 700 }}>Status</TableCell>
                  </TableRow>
                </TableHead>
                <TableBody>
                  {resultsArray.map((item: any, index: number) => {
                    const isAbnormal = item.reference_range && item.value && 
                      !item.value.includes('Normal') && !item.value.includes('normal');
                    return (
                      <TableRow key={index}>
                        <TableCell sx={{ fontWeight: 500 }}>{item.name || `Parameter ${index + 1}`}</TableCell>
                        <TableCell sx={{ 
                          fontWeight: isAbnormal ? 700 : 400,
                          color: isAbnormal ? theme.palette.error.main : 'inherit'
                        }}>
                          {item.value || 'N/A'}
                        </TableCell>
                        <TableCell>{item.unit || 'N/A'}</TableCell>
                        <TableCell>{item.reference_range || 'N/A'}</TableCell>
                        <TableCell>
                          <Chip
                            label={isAbnormal ? 'Abnormal' : 'Normal'}
                            color={isAbnormal ? 'error' : 'success'}
                            size="small"
                          />
                        </TableCell>
                      </TableRow>
                    );
                  })}
                </TableBody>
              </Table>
            </TableContainer>
          </Box>
        )}

        {/* Notes */}
        {result.notes && (
          <Box sx={{ mb: 3 }}>
            <Typography variant="subtitle2" sx={{ fontWeight: 700, mb: 1 }}>
              📝 Notes
            </Typography>
            <Paper variant="outlined" sx={{ p: 2, bgcolor: alpha(theme.palette.background.default, 0.3) }}>
              <Typography variant="body2" sx={{ whiteSpace: 'pre-wrap' }}>
                {result.notes}
              </Typography>
            </Paper>
          </Box>
        )}

        {/* Footer */}
        <Box
          sx={{
            mt: 4,
            pt: 2,
            borderTop: `1px solid ${alpha(theme.palette.divider, 0.08)}`,
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'center',
          }}
        >
          <Box>
            <Typography variant="caption" color="text.secondary" sx={{ display: 'block' }}>
              Performed by: {result.user?.name || 'Unknown'}
            </Typography>
            <Typography variant="caption" color="text.secondary" sx={{ display: 'block' }}>
              Completed: {result.completed_at ? formatDateTime(result.completed_at) : 'Not completed'}
            </Typography>
          </Box>
          <Box sx={{ textAlign: 'right' }}>
            <Typography variant="caption" color="text.secondary" sx={{ display: 'block' }}>
              Reported: {formatDateTime(result.created_at)}
            </Typography>
            <Chip
              label="Laboratory Report"
              size="small"
              color="info"
              variant="outlined"
            />
          </Box>
        </Box>

        {/* Print Footer */}
        <Box sx={{ mt: 2, textAlign: 'center', borderTop: `1px solid ${alpha(theme.palette.divider, 0.1)}`, pt: 2 }} className="no-print">
          <Typography variant="caption" color="text.secondary">
            This is a computer-generated report. No signature required.
          </Typography>
        </Box>
      </Paper>
    </Layout>
  );
};

export default TestResultView;