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
  FormControl,
  InputLabel,
  Select,
  Alert,
  IconButton,
  CircularProgress,
  Divider,
} from '@mui/material';
import { Save as SaveIcon, Cancel as CancelIcon, Delete as DeleteIcon, Add as AddIcon } from '@mui/icons-material';
import Layout from '../components/Layout/Layout';
import api from '../api/client';
import { TestResult, Patient, LabTest } from '../types';

interface ResultData {
  name: string;
  value: string;
  unit: string;
  reference_range: string;
}

const TestResultForm: React.FC = () => {
  const navigate = useNavigate();
  const { id } = useParams<{ id: string }>();
  const isEdit = !!id;
  const [loading, setLoading] = useState(false);
  const [fetching, setFetching] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [patients, setPatients] = useState<Patient[]>([]);
  const [labTests, setLabTests] = useState<LabTest[]>([]);
  const [formData, setFormData] = useState<any>({
    patient_id: '',
    lab_test_id: '',
    test_date: new Date().toISOString().split('T')[0],
    status: 'pending',
    results_data: [],
    notes: '',
  });

  useEffect(() => {
    fetchData();
    if (isEdit) {
      fetchResult();
    }
  }, [id]);

  const fetchData = async () => {
    setFetching(true);
    try {
      const [patientsRes, testsRes] = await Promise.all([
        api.get('/patients', { params: { per_page: 100 } }),
        api.get('/lab-tests', { params: { per_page: 100 } }),
      ]);
      
      // Handle different response structures
      let patientsData = [];
      if (patientsRes.data.data) {
        patientsData = patientsRes.data.data;
      } else if (patientsRes.data.patients?.data) {
        patientsData = patientsRes.data.patients.data;
      } else {
        patientsData = patientsRes.data || [];
      }
      
      let testsData = [];
      if (testsRes.data.data) {
        testsData = testsRes.data.data;
      } else if (testsRes.data.lab_tests?.data) {
        testsData = testsRes.data.lab_tests.data;
      } else {
        testsData = testsRes.data || [];
      }
      
      setPatients(patientsData);
      setLabTests(testsData);
    } catch (error) {
      console.error('Error fetching data:', error);
      setError('Failed to load data');
    } finally {
      setFetching(false);
    }
  };

  const fetchResult = async () => {
    setLoading(true);
    try {
      const response = await api.get(`/test-results/${id}`);
      const data = response.data;
      
      // Ensure results_data is an array
      let resultsData = [];
      if (data.results_data) {
        if (typeof data.results_data === 'string') {
          try {
            resultsData = JSON.parse(data.results_data);
          } catch (e) {
            resultsData = [];
          }
        } else if (Array.isArray(data.results_data)) {
          resultsData = data.results_data;
        } else if (typeof data.results_data === 'object') {
          // If it's an object, convert to array
          resultsData = Object.values(data.results_data);
        }
      }
      
      setFormData({
        ...data,
        results_data: resultsData,
      });
    } catch (error) {
      console.error('Error fetching test result:', error);
      setError('Failed to load test result data');
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError(null);

    try {
      // Convert results_data to the format the backend expects
      const payload = {
        ...formData,
        // If results_data is an array, convert to object with numeric keys
        results_data: Array.isArray(formData.results_data) 
          ? formData.results_data 
          : [],
        // Ensure patient_id and lab_test_id are numbers
        patient_id: parseInt(formData.patient_id),
        lab_test_id: parseInt(formData.lab_test_id),
      };

      console.log('Sending payload:', payload);

      if (isEdit) {
        await api.put(`/test-results/${id}`, payload);
      } else {
        await api.post('/test-results', payload);
      }
      navigate('/test-results');
    } catch (error: any) {
      console.error('Error saving test result:', error);
      console.error('Error response:', error.response?.data);
      setError(error.response?.data?.message || 'Failed to save test result');
      setLoading(false);
    }
  };

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target;
    setFormData({ ...formData, [name]: value });
  };

  const handleSelectChange = (name: string, value: any) => {
    setFormData({ ...formData, [name]: value });
  };

  const handleResultChange = (index: number, field: string, value: string) => {
    const newResults = [...(formData.results_data || [])];
    newResults[index] = { ...newResults[index], [field]: value };
    setFormData({ ...formData, results_data: newResults });
  };

  const addResult = () => {
    setFormData({
      ...formData,
      results_data: [...(formData.results_data || []), { name: '', value: '', unit: '', reference_range: '' }],
    });
  };

  const removeResult = (index: number) => {
    const newResults = [...(formData.results_data || [])];
    newResults.splice(index, 1);
    setFormData({ ...formData, results_data: newResults });
  };

  if (fetching) {
    return (
      <Layout title={isEdit ? 'Edit Test Result' : 'Add New Test Result'}>
        <Box sx={{ display: 'flex', justifyContent: 'center', py: 8 }}>
          <CircularProgress />
        </Box>
      </Layout>
    );
  }

  return (
    <Layout title={isEdit ? 'Edit Test Result' : 'Add New Test Result'}>
      <Paper sx={{ p: 4 }}>
        {error && (
          <Alert severity="error" sx={{ mb: 2 }} onClose={() => setError(null)}>
            {error}
          </Alert>
        )}

        <form onSubmit={handleSubmit}>
          <Grid container spacing={3}>
            <Grid size={{ xs: 12, md: 6 }}>
              <FormControl fullWidth required>
                <InputLabel>Patient</InputLabel>
                <Select
                  value={formData.patient_id || ''}
                  onChange={(e) => handleSelectChange('patient_id', e.target.value)}
                  label="Patient"
                >
                  {patients.map((patient) => (
                    <MenuItem key={patient.id} value={patient.id}>
                      {patient.first_name} {patient.last_name} ({patient.medical_record_number})
                    </MenuItem>
                  ))}
                </Select>
              </FormControl>
            </Grid>
            <Grid size={{ xs: 12, md: 6 }}>
              <FormControl fullWidth required>
                <InputLabel>Lab Test</InputLabel>
                <Select
                  value={formData.lab_test_id || ''}
                  onChange={(e) => handleSelectChange('lab_test_id', e.target.value)}
                  label="Lab Test"
                >
                  {labTests.map((test) => (
                    <MenuItem key={test.id} value={test.id}>
                      {test.name}
                    </MenuItem>
                  ))}
                </Select>
              </FormControl>
            </Grid>
            <Grid size={{ xs: 12, md: 6 }}>
              <TextField
                fullWidth
                label="Test Date"
                type="date"
                name="test_date"
                value={formData.test_date || ''}
                onChange={handleChange}
                required
                slotProps={{
                  inputLabel: { shrink: true },
                }}
              />
            </Grid>
            <Grid size={{ xs: 12, md: 6 }}>
              <FormControl fullWidth required>
                <InputLabel>Status</InputLabel>
                <Select
                  value={formData.status || 'pending'}
                  onChange={(e) => handleSelectChange('status', e.target.value)}
                  label="Status"
                >
                  <MenuItem value="pending">Pending</MenuItem>
                  <MenuItem value="in_progress">In Progress</MenuItem>
                  <MenuItem value="completed">Completed</MenuItem>
                  <MenuItem value="cancelled">Cancelled</MenuItem>
                </Select>
              </FormControl>
            </Grid>

            <Grid size={{ xs: 12 }}>
              <Divider sx={{ my: 2 }} />
              <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2 }}>
                <Typography variant="h6">Results Data</Typography>
                <Button variant="outlined" startIcon={<AddIcon />} onClick={addResult} size="small">
                  Add Result
                </Button>
              </Box>
              
              {(formData.results_data || []).length === 0 ? (
                <Alert severity="info" sx={{ mb: 2 }}>
                  No results added yet. Click "Add Result" to add parameters.
                </Alert>
              ) : (
                (formData.results_data || []).map((result: ResultData, index: number) => (
                  <Box key={index} sx={{ display: 'flex', gap: 2, alignItems: 'center', mb: 2, flexWrap: 'wrap' }}>
                    <TextField
                      label="Parameter Name"
                      value={result.name || ''}
                      onChange={(e) => handleResultChange(index, 'name', e.target.value)}
                      size="small"
                      required
                      sx={{ flex: 1, minWidth: 120 }}
                    />
                    <TextField
                      label="Value"
                      value={result.value || ''}
                      onChange={(e) => handleResultChange(index, 'value', e.target.value)}
                      size="small"
                      required
                      sx={{ flex: 1, minWidth: 100 }}
                    />
                    <TextField
                      label="Unit"
                      value={result.unit || ''}
                      onChange={(e) => handleResultChange(index, 'unit', e.target.value)}
                      size="small"
                      sx={{ flex: 0.7, minWidth: 80 }}
                    />
                    <TextField
                      label="Reference Range"
                      value={result.reference_range || ''}
                      onChange={(e) => handleResultChange(index, 'reference_range', e.target.value)}
                      size="small"
                      sx={{ flex: 1, minWidth: 100 }}
                    />
                    <IconButton color="error" onClick={() => removeResult(index)} size="small">
                      <DeleteIcon />
                    </IconButton>
                  </Box>
                ))
              )}
            </Grid>

            <Grid size={{ xs: 12 }}>
              <TextField
                fullWidth
                label="Notes"
                name="notes"
                value={formData.notes || ''}
                onChange={handleChange}
                multiline
                rows={3}
              />
            </Grid>

            <Grid size={{ xs: 12 }}>
              <Box sx={{ display: 'flex', gap: 2, justifyContent: 'flex-end', mt: 2 }}>
                <Button variant="outlined" startIcon={<CancelIcon />} onClick={() => navigate('/test-results')}>
                  Cancel
                </Button>
                <Button type="submit" variant="contained" startIcon={<SaveIcon />} disabled={loading}>
                  {loading ? 'Saving...' : isEdit ? 'Update Result' : 'Create Result'}
                </Button>
              </Box>
            </Grid>
          </Grid>
        </form>
      </Paper>
    </Layout>
  );
};

export default TestResultForm;