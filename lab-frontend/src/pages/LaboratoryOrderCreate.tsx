import { Alert, Box, Button, Grid, Paper, Stack } from '@mui/material';
import type { FormEvent } from 'react';
import { useNavigate } from 'react-router-dom';
import Layout from '../components/Layout/Layout';
import { useAuth } from '../contexts/AuthContext';
import OrderCreationDetails from '../features/laboratory-orders/components/OrderCreationDetails';
import OrderCreationHeader from '../features/laboratory-orders/components/OrderCreationHeader';
import OrderPatientSelector from '../features/laboratory-orders/components/OrderPatientSelector';
import OrderTestSelector from '../features/laboratory-orders/components/OrderTestSelector';
import SelectedOrderTests from '../features/laboratory-orders/components/SelectedOrderTests';
import { useOrderCatalogues } from '../features/laboratory-orders/hooks/useOrderCatalogues';
import { useOrderCreation } from '../features/laboratory-orders/hooks/useOrderCreation';
import { roleAllowsOrderAction } from '../features/laboratory-orders/permissions/orderPermissions';

const LaboratoryOrderCreate = () => {
  const navigate = useNavigate();
  const { user } = useAuth();
  const allowed = roleAllowsOrderAction(user?.role, 'create_order');
  const catalogues = useOrderCatalogues(allowed);
  const creation = useOrderCreation((uuid) => {
    navigate(`/laboratory-orders/${uuid}`);
  });

  const submit = (event: FormEvent) => {
    event.preventDefault();
    void creation.submit();
  };

  return (
    <Layout title="Create laboratory order">
      <OrderCreationHeader onBack={() => navigate('/laboratory-orders')} />
      {!allowed ? (
        <Alert severity="error">
          You do not have permission to create laboratory orders.
        </Alert>
      ) : (
        <Box component="form" onSubmit={submit}>
          {(catalogues.error || creation.error) && (
            <Alert severity="error" sx={{ mb: 2 }}>
              {catalogues.error ?? creation.error}
            </Alert>
          )}
          <Grid container spacing={2.5}>
            <Grid size={{ xs: 12, lg: 8 }}>
              <Paper variant="outlined" sx={{ p: 2.5 }}>
                <Stack spacing={2.5}>
                  <OrderPatientSelector
                    options={catalogues.patients}
                    value={creation.state.patient}
                    loading={catalogues.patientsLoading}
                    search={catalogues.patientSearch}
                    onSearch={catalogues.setPatientSearch}
                    onChange={creation.setPatient}
                  />
                  <OrderTestSelector
                    options={catalogues.tests}
                    value={creation.state.tests}
                    loading={catalogues.testsLoading}
                    search={catalogues.testSearch}
                    onSearch={catalogues.setTestSearch}
                    onChange={creation.setTests}
                  />
                  <OrderCreationDetails
                    priority={creation.state.priority}
                    referringClinician={creation.state.referringClinician}
                    clinicalNotes={creation.state.clinicalNotes}
                    onPriorityChange={creation.setPriority}
                    onReferringClinicianChange={creation.setReferringClinician}
                    onClinicalNotesChange={creation.setClinicalNotes}
                  />
                </Stack>
              </Paper>
            </Grid>
            <Grid size={{ xs: 12, lg: 4 }}>
              <SelectedOrderTests
                tests={creation.state.tests}
                onRemove={creation.removeTest}
              />
            </Grid>
          </Grid>
          <Stack direction="row" spacing={1.5} sx={{ mt: 2.5, justifyContent: 'flex-end' }}>
            <Button onClick={() => navigate('/laboratory-orders')}>
              Cancel
            </Button>
            <Button type="submit" variant="contained" disabled={creation.submitting}>
              {creation.submitting ? 'Creating…' : 'Create order'}
            </Button>
          </Stack>
        </Box>
      )}
    </Layout>
  );
};

export default LaboratoryOrderCreate;
