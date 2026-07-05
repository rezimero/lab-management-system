import React, { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import {
  Box,
  Paper,
  TextField,
  Button,
  Grid,
  Typography,
  FormControlLabel,
  Switch,
  Alert,
  IconButton,
  Avatar,
  alpha,
  useTheme,
  Chip,
  InputAdornment,
} from '@mui/material';
import {
  Save as SaveIcon,
  Cancel as CancelIcon,
  Delete as DeleteIcon,
  Add as AddIcon,
  Science as ScienceIcon,
  Edit as EditIcon,
  Category as CategoryIcon,
} from '@mui/icons-material';
import Layout from '../components/Layout/Layout';
import api from '../api/client';
import { LabTest } from '../types';

const LabTestForm: React.FC = () => {
  const navigate = useNavigate();
  const { id } = useParams<{ id: string }>();
  const theme = useTheme();
  const isEdit = !!id;
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [formData, setFormData] = useState<Partial<LabTest>>({
    name: '',
    description: '',
    category: '',
    price: '',
    turnaround_time_hours: 24,
    parameters: [],
    is_active: true,
  });

  useEffect(() => {
    if (isEdit) {
      fetchTest();
    }
  }, [id]);

  const fetchTest = async () => {
    setLoading(true);
    try {
      const response = await api.get(`/lab-tests/${id}`);
      setFormData(response.data);
    } catch (error) {
      console.error('Error fetching lab test:', error);
      setError('Failed to load lab test data');
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
        await api.put(`/lab-tests/${id}`, formData);
      } else {
        await api.post('/lab-tests', formData);
      }
      navigate('/lab-tests');
    } catch (error: any) {
      console.error('Error saving lab test:', error);
      setError(error.response?.data?.message || 'Failed to save lab test');
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

  const handleParameterChange = (index: number, field: string, value: string) => {
    const newParams = [...(formData.parameters || [])];
    newParams[index] = { ...newParams[index], [field]: value };
    setFormData({ ...formData, parameters: newParams });
  };

  const addParameter = () => {
    setFormData({
      ...formData,
      parameters: [...(formData.parameters || []), { name: '', unit: '', reference_range: '' }],
    });
  };

  const removeParameter = (index: number) => {
    const newParams = [...(formData.parameters || [])];
    newParams.splice(index, 1);
    setFormData({ ...formData, parameters: newParams });
  };

  return (
    <Layout title={isEdit ? 'Edit Lab Test' : 'Add New Lab Test'}>
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
                bgcolor: theme.palette.secondary.main,
                background: `linear-gradient(135deg, ${theme.palette.secondary.main}, ${theme.palette.secondary.dark})`,
                boxShadow: `0 8px 24px ${alpha(theme.palette.secondary.main, 0.3)}`,
              }}
            >
              {isEdit ? <EditIcon /> : <ScienceIcon />}
            </Avatar>
            <Box>
              <Typography variant="h5" sx={{ fontWeight: 700 }}>
                {isEdit ? 'Edit Lab Test' : 'Add New Lab Test'}
              </Typography>
              <Typography variant="body2" color="text.secondary">
                {isEdit ? 'Update test information' : 'Create a new laboratory test'}
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
                  label="Test Name"
                  name="name"
                  value={formData.name}
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
                  label="Category"
                  name="category"
                  value={formData.category}
                  onChange={handleChange}
                  required
                  variant="outlined"
                  slotProps={{
                    input: {
                      sx: { borderRadius: 2 },
                      startAdornment: (
                        <InputAdornment position="start">
                          <CategoryIcon sx={{ color: 'text.secondary' }} />
                        </InputAdornment>
                      ),
                    },
                  }}
                />
              </Grid>
              <Grid size={{ xs: 12 }}>
                <TextField
                  fullWidth
                  label="Description"
                  name="description"
                  value={formData.description}
                  onChange={handleChange}
                  multiline
                  rows={2}
                  variant="outlined"
                  slotProps={{
                    input: {
                      sx: { borderRadius: 2 },
                    },
                  }}
                />
              </Grid>
              <Grid size={{ xs: 12, md: 4 }}>
                <TextField
                  fullWidth
                  label="Price (₦)"
                  name="price"
                  type="number"
                  value={formData.price}
                  onChange={handleChange}
                  variant="outlined"
                  slotProps={{
                    input: {
                      sx: { borderRadius: 2 },
                    },
                  }}
                />
              </Grid>
              <Grid size={{ xs: 12, md: 4 }}>
                <TextField
                  fullWidth
                  label="Turnaround Time (hours)"
                  name="turnaround_time_hours"
                  type="number"
                  value={formData.turnaround_time_hours}
                  onChange={handleChange}
                  variant="outlined"
                  slotProps={{
                    input: {
                      sx: { borderRadius: 2 },
                    },
                  }}
                />
              </Grid>
              <Grid size={{ xs: 12, md: 4 }}>
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
                  label="Active Test"
                />
              </Grid>

              <Grid size={{ xs: 12 }}>
                <Typography variant="h6" gutterBottom sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                  <ScienceIcon sx={{ color: 'text.secondary' }} />
                  Parameters
                </Typography>
                <Button
                  variant="outlined"
                  startIcon={<AddIcon />}
                  onClick={addParameter}
                  size="small"
                  sx={{ mb: 2, borderRadius: 2, textTransform: 'none' }}
                >
                  Add Parameter
                </Button>
                {(formData.parameters || []).map((param, index) => (
                  <Box key={index} sx={{ display: 'flex', gap: 2, alignItems: 'center', mb: 2, flexWrap: 'wrap' }}>
                    <TextField
                      label="Parameter Name"
                      value={param.name}
                      onChange={(e) => handleParameterChange(index, 'name', e.target.value)}
                      size="small"
                      sx={{ flex: 1, minWidth: 150 }}
                      slotProps={{
                        input: {
                          sx: { borderRadius: 2 },
                        },
                      }}
                    />
                    <TextField
                      label="Unit"
                      value={param.unit}
                      onChange={(e) => handleParameterChange(index, 'unit', e.target.value)}
                      size="small"
                      sx={{ flex: 1, minWidth: 100 }}
                      slotProps={{
                        input: {
                          sx: { borderRadius: 2 },
                        },
                      }}
                    />
                    <TextField
                      label="Reference Range"
                      value={param.reference_range}
                      onChange={(e) => handleParameterChange(index, 'reference_range', e.target.value)}
                      size="small"
                      sx={{ flex: 1, minWidth: 120 }}
                      slotProps={{
                        input: {
                          sx: { borderRadius: 2 },
                        },
                      }}
                    />
                    <IconButton
                      color="error"
                      onClick={() => removeParameter(index)}
                      size="small"
                      sx={{
                        '&:hover': {
                          backgroundColor: alpha(theme.palette.error.main, 0.08),
                        },
                      }}
                    >
                      <DeleteIcon />
                    </IconButton>
                  </Box>
                ))}
              </Grid>

              <Grid size={{ xs: 12 }}>
                <Box sx={{ display: 'flex', gap: 2, justifyContent: 'flex-end' }}>
                  <Button
                    variant="outlined"
                    startIcon={<CancelIcon />}
                    onClick={() => navigate('/lab-tests')}
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
                    {loading ? 'Saving...' : isEdit ? 'Update Test' : 'Create Test'}
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

export default LabTestForm;