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
} from '@mui/material';
import {
  Add as AddIcon,
  Search as SearchIcon,
  Edit as EditIcon,
  Delete as DeleteIcon,
  Science as ScienceIcon,
  Schedule as ScheduleIcon,
  Category as CategoryIcon,
} from '@mui/icons-material';
import Layout from '../components/Layout/Layout';
import api from '../api/client';
import { LabTest } from '../types';
import { motion } from 'framer-motion';

const LabTests: React.FC = () => {
  const navigate = useNavigate();
  const theme = useTheme();
  const [tests, setTests] = useState<LabTest[]>([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(0);
  const [rowsPerPage, setRowsPerPage] = useState(10);
  const [total, setTotal] = useState(0);
  const [search, setSearch] = useState('');
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
  const [selectedTest, setSelectedTest] = useState<LabTest | null>(null);

  useEffect(() => {
    fetchTests();
  }, [page, rowsPerPage, search]);

  const fetchTests = async () => {
    setLoading(true);
    try {
      const response = await api.get('/lab-tests', {
        params: { page: page + 1, per_page: rowsPerPage, search },
      });
      setTests(response.data.data);
      setTotal(response.data.total);
    } catch (error) {
      console.error('Error fetching lab tests:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async () => {
    if (!selectedTest) return;
    try {
      await api.delete(`/lab-tests/${selectedTest.id}`);
      setDeleteDialogOpen(false);
      fetchTests();
    } catch (error) {
      console.error('Error deleting lab test:', error);
    }
  };

  const getInitials = (name: string) => {
    return name
      .split(' ')
      .map((word) => word[0])
      .join('')
      .toUpperCase()
      .slice(0, 2);
  };

  return (
    <Layout title="Lab Tests">
      <Box sx={{ mb: 4 }}>
        <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: 2 }}>
          <Box>
            <Typography variant="h5" sx={{ fontWeight: 700, color: 'text.primary' }}>
              Lab Tests
            </Typography>
            <Typography variant="body2" color="text.secondary">
              Manage all laboratory tests and their parameters
            </Typography>
          </Box>
          <Button
            variant="contained"
            startIcon={<AddIcon />}
            onClick={() => navigate('/lab-tests/new')}
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
            Add Lab Test
          </Button>
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
            placeholder="Search lab tests..."
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
            <strong>{total}</strong> tests available
          </Typography>
        </Box>

        <TableContainer>
          <Table>
            <TableHead>
              <TableRow sx={{ backgroundColor: alpha(theme.palette.background.default, 0.4) }}>
                <TableCell sx={{ fontWeight: 700, color: 'text.secondary' }}>Test Name</TableCell>
                <TableCell sx={{ fontWeight: 700, color: 'text.secondary' }}>Category</TableCell>
                <TableCell sx={{ fontWeight: 700, color: 'text.secondary' }}>Price</TableCell>
                <TableCell sx={{ fontWeight: 700, color: 'text.secondary' }}>Turnaround</TableCell>
                <TableCell sx={{ fontWeight: 700, color: 'text.secondary' }}>Status</TableCell>
                <TableCell align="right" sx={{ fontWeight: 700, color: 'text.secondary' }}>Actions</TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {loading ? (
                <TableRow>
                  <TableCell colSpan={6} align="center" sx={{ py: 8 }}>
                    <Typography color="text.secondary">Loading lab tests...</Typography>
                  </TableCell>
                </TableRow>
              ) : tests.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={6} align="center" sx={{ py: 8 }}>
                    <Typography color="text.secondary">No lab tests found</Typography>
                  </TableCell>
                </TableRow>
              ) : (
                tests.map((test) => (
                  <TableRow
                    key={test.id}
                    hover
                    sx={{
                      '&:hover': {
                        backgroundColor: alpha(theme.palette.primary.main, 0.02),
                      },
                      transition: 'background-color 0.2s ease',
                    }}
                  >
                    <TableCell>
                      <Box sx={{ display: 'flex', alignItems: 'center', gap: 2 }}>
                        <Avatar
                          sx={{
                            width: 40,
                            height: 40,
                            bgcolor: theme.palette.secondary.main,
                            background: `linear-gradient(135deg, ${theme.palette.secondary.main}, ${theme.palette.secondary.dark})`,
                            fontWeight: 700,
                            fontSize: '0.875rem',
                            boxShadow: `0 4px 12px ${alpha(theme.palette.secondary.main, 0.25)}`,
                          }}
                        >
                          {getInitials(test.name)}
                        </Avatar>
                        <Box>
                          <Typography variant="body2" sx={{ fontWeight: 600 }}>
                            {test.name}
                          </Typography>
                          <Typography variant="caption" color="text.secondary" sx={{ display: 'flex', alignItems: 'center', gap: 0.5 }}>
                            <ScienceIcon sx={{ fontSize: 12 }} />
                            {test.parameters?.length || 0} parameters
                          </Typography>
                        </Box>
                      </Box>
                    </TableCell>
                    <TableCell>
                      <Chip
                        icon={<CategoryIcon sx={{ fontSize: 16 }} />}
                        label={test.category}
                        size="small"
                        variant="outlined"
                        sx={{
                          borderRadius: 2,
                          borderColor: alpha(theme.palette.divider, 0.2),
                        }}
                      />
                    </TableCell>
                    <TableCell>
                      <Box sx={{ display: 'flex', alignItems: 'center' }}>
                        {/* 🌟 CLEARED THE DOLLAR ICON OUT FROM THIS NODE */}
                        <Typography variant="body2" sx={{ fontWeight: 600, color: theme.palette.success.main }}>
                          ₦{test.price.toLocaleString()}
                        </Typography>
                      </Box>
                    </TableCell>
                    <TableCell>
                      <Box sx={{ display: 'flex', alignItems: 'center', gap: 0.5 }}>
                        <ScheduleIcon sx={{ fontSize: 16, color: 'text.secondary' }} />
                        <Typography variant="body2">{test.turnaround_time_hours}h</Typography>
                      </Box>
                    </TableCell>
                    <TableCell>
                      <Chip
                        label={test.is_active ? 'Active' : 'Inactive'}
                        color={test.is_active ? 'success' : 'default'}
                        size="small"
                        sx={{
                          fontWeight: 600,
                          ...(test.is_active && {
                            background: `linear-gradient(135deg, ${alpha(theme.palette.success.main, 0.15)}, ${alpha(theme.palette.success.light, 0.05)})`,
                            border: `1px solid ${alpha(theme.palette.success.main, 0.3)}`,
                          }),
                        }}
                      />
                    </TableCell>
                    <TableCell align="right">
                      <IconButton
                        size="small"
                        onClick={() => navigate(`/lab-tests/${test.id}/edit`)}
                        sx={{
                          color: theme.palette.primary.main,
                          '&:hover': {
                            backgroundColor: alpha(theme.palette.primary.main, 0.08),
                          },
                        }}
                      >
                        <EditIcon fontSize="small" />
                      </IconButton>
                      <IconButton
                        size="small"
                        onClick={() => {
                          setSelectedTest(test);
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
                    </TableCell>
                  </TableRow>
                ))
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
            Are you sure you want to delete lab test "<strong>{selectedTest?.name}</strong>"? This action cannot be undone.
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
            Delete Test
          </Button>
        </DialogActions>
      </Dialog>
    </Layout>
  );
};

export default LabTests;