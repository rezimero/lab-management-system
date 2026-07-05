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
  Stepper,
  Step,
  StepLabel,
  Avatar,
  alpha,
  useTheme,
} from '@mui/material';
import {
  Save as SaveIcon,
  Cancel as CancelIcon,
  Person as PersonIcon,
  ContactMail as ContactIcon,
  MedicalServices as MedicalIcon,
  Edit as EditIcon,
  PersonAdd as PersonAddIcon,
} from '@mui/icons-material';
import Layout from '../components/Layout/Layout';
import api from '../api/client';
import { Patient } from '../types';

const PatientForm: React.FC = () => {
  const navigate = useNavigate();
  const { id } = useParams<{ id: string }>();
  const theme = useTheme();
  const isEdit = !!id;
  const [activeStep, setActiveStep] = useState(0);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [formData, setFormData] = useState<Partial<Patient>>({
    first_name: '',
    last_name: '',
    date_of_birth: '',
    gender: 'male',
    email: '',
    phone: '',
    address: '',
    notes: '',
    is_active: true,
  });

  useEffect(() => {
    if (isEdit) {
      fetchPatient();
    }
  }, [id]);

  const fetchPatient = async () => {
    setLoading(true);
    try {
      const response = await api.get(`/patients/${id}`);
      setFormData(response.data);
    } catch (error) {
      console.error('Error fetching patient:', error);
      setError('Failed to load patient data');
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
        await api.put(`/patients/${id}`, formData);
      } else {
        await api.post('/patients', formData);
      }
      navigate('/patients');
    } catch (error: any) {
      console.error('Error saving patient:', error);
      setError(error.response?.data?.message || 'Failed to save patient');
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

  const steps = [
    { label: 'Personal Information', icon: <PersonIcon /> },
    { label: 'Contact Details', icon: <ContactIcon /> },
    { label: 'Medical Information', icon: <MedicalIcon /> },
  ];

  return (
    <Layout title={isEdit ? 'Edit Patient' : 'Add New Patient'}>
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
                {isEdit ? 'Edit Patient' : 'Add New Patient'}
              </Typography>
              <Typography variant="body2" color="text.secondary">
                {isEdit ? 'Update patient information' : 'Register a new patient in the system'}
              </Typography>
            </Box>
          </Box>

          {error && (
            <Alert severity="error" sx={{ mb: 3, borderRadius: 2 }}>
              {error}
            </Alert>
          )}

          <Stepper
            activeStep={activeStep}
            orientation="horizontal"
            sx={{ mb: 4, '& .MuiStepConnector-line': { borderColor: alpha(theme.palette.divider, 0.2) } }}
          >
            {steps.map((step, index) => (
              <Step key={index} onClick={() => setActiveStep(index)} sx={{ cursor: 'pointer' }}>
                <StepLabel
                  slots={{
                    stepIcon: () => (
                      <Box
                        sx={{
                          width: 32,
                          height: 32,
                          borderRadius: '50%',
                          display: 'flex',
                          alignItems: 'center',
                          justifyContent: 'center',
                          backgroundColor: activeStep >= index ? theme.palette.primary.main : alpha(theme.palette.divider, 0.2),
                          color: activeStep >= index ? 'white' : 'text.secondary',
                          transition: 'all 0.3s ease',
                        }}
                      >
                        {step.icon}
                      </Box>
                    ),
                  }}
                >
                  {step.label}
                </StepLabel>
              </Step>
            ))}
          </Stepper>

          <form onSubmit={handleSubmit}>
            <Grid container spacing={3}>
              {activeStep === 0 && (
                <>
                  <Grid size={{ xs: 12, md: 6 }}>
                    <TextField
                      fullWidth
                      label="First Name"
                      name="first_name"
                      value={formData.first_name}
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
                      label="Last Name"
                      name="last_name"
                      value={formData.last_name}
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
                      label="Date of Birth"
                      name="date_of_birth"
                      type="date"
                      value={formData.date_of_birth}
                      onChange={handleChange}
                      required
                      slotProps={{
                        inputLabel: { shrink: true },
                        input: { sx: { borderRadius: 2 } },
                      }}
                    />
                  </Grid>
                  <Grid size={{ xs: 12, md: 6 }}>
                    <TextField
                      fullWidth
                      select
                      label="Gender"
                      name="gender"
                      value={formData.gender}
                      onChange={handleChange}
                      required
                      variant="outlined"
                      slotProps={{
                        input: { sx: { borderRadius: 2 } },
                      }}
                    >
                      <MenuItem value="male">Male</MenuItem>
                      <MenuItem value="female">Female</MenuItem>
                      <MenuItem value="other">Other</MenuItem>
                    </TextField>
                  </Grid>
                </>
              )}

              {activeStep === 1 && (
                <>
                  <Grid size={{ xs: 12, md: 6 }}>
                    <TextField
                      fullWidth
                      label="Phone"
                      name="phone"
                      value={formData.phone}
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
                      label="Email"
                      name="email"
                      type="email"
                      value={formData.email}
                      onChange={handleChange}
                      variant="outlined"
                      slotProps={{
                        input: {
                          sx: { borderRadius: 2 },
                        },
                      }}
                    />
                  </Grid>
                  <Grid size={{ xs: 12 }}>
                    <TextField
                      fullWidth
                      label="Address"
                      name="address"
                      value={formData.address}
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
                </>
              )}

              {activeStep === 2 && (
                <>
                  <Grid size={{ xs: 12 }}>
                    <TextField
                      fullWidth
                      label="Notes"
                      name="notes"
                      value={formData.notes}
                      onChange={handleChange}
                      multiline
                      rows={3}
                      variant="outlined"
                      slotProps={{
                        input: {
                          sx: { borderRadius: 2 },
                        },
                      }}
                    />
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
                      label="Active Patient"
                    />
                  </Grid>
                </>
              )}

              <Grid size={{ xs: 12 }}>
                <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mt: 2 }}>
                  <Box sx={{ display: 'flex', gap: 2 }}>
                    <Button
                      onClick={() => setActiveStep(Math.max(0, activeStep - 1))}
                      disabled={activeStep === 0}
                      variant="outlined"
                      sx={{ borderRadius: 2, textTransform: 'none' }}
                    >
                      Previous
                    </Button>
                    {activeStep < steps.length - 1 && (
                      <Button
                        onClick={() => setActiveStep(activeStep + 1)}
                        variant="contained"
                        sx={{ borderRadius: 2, textTransform: 'none' }}
                      >
                        Next
                      </Button>
                    )}
                  </Box>
                  <Box sx={{ display: 'flex', gap: 2 }}>
                    <Button
                      variant="outlined"
                      startIcon={<CancelIcon />}
                      onClick={() => navigate('/patients')}
                      sx={{ borderRadius: 2, textTransform: 'none' }}
                    >
                      Cancel
                    </Button>
                    {activeStep === steps.length - 1 && (
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
                        {loading ? 'Saving...' : isEdit ? 'Update Patient' : 'Create Patient'}
                      </Button>
                    )}
                  </Box>
                </Box>
              </Grid>
            </Grid>
          </form>
        </Paper>
      </Box>
    </Layout>
  );
};

export default PatientForm;