import {
  useNavigate,
  useParams,
} from 'react-router-dom';

import {
  Alert,
} from '@mui/material';

import Layout from '../components/Layout/Layout';

import {
  useAuth,
} from '../contexts/AuthContext';

import RejectResultDialog from '../features/test-results/components/RejectResultDialog';
import ResultWorkflowActions from '../features/test-results/components/ResultWorkflowActions';
import TestResultDetailPaper from '../features/test-results/components/TestResultDetailPaper';
import TestResultPrintStyles from '../features/test-results/components/TestResultPrintStyles';
import TestResultViewHeader from '../features/test-results/components/TestResultViewHeader';
import TestResultViewLoading from '../features/test-results/components/TestResultViewLoading';

import {
  useTestResultResource,
} from '../features/test-results/hooks/useTestResultResource';

import {
  useTestResultWorkflow,
} from '../features/test-results/hooks/useTestResultWorkflow';

const TestResultView = () => {
  const {
    id: uuid,
  } = useParams<{
    id: string;
  }>();

  const navigate = useNavigate();
  const { user } = useAuth();

  const resource =
    useTestResultResource(uuid);

  const workflow =
    useTestResultWorkflow({
      result: resource.result,
      role: user?.role,
      onUpdated: resource.setResult,
    });

  if (resource.loading) {
    return (
      <Layout title="Test Result">
        <TestResultViewLoading />
      </Layout>
    );
  }

  if (
    resource.error ||
    !resource.result
  ) {
    return (
      <Layout title="Test Result">
        <Alert
          severity="error"
          sx={{
            mt: 2,
          }}
        >
          {resource.error ??
            'Test result not found'}
        </Alert>
      </Layout>
    );
  }

  const result = resource.result;

  return (
    <Layout title="Test Result">
      <TestResultPrintStyles />

      <TestResultViewHeader
        onBack={() => {
          navigate('/test-results');
        }}
        onPrint={() => {
          window.print();
        }}
      />

      <ResultWorkflowActions
        error={workflow.error}
        busyAction={
          workflow.busyAction
        }
        canEdit={workflow.canEdit}
        canSubmit={workflow.canSubmit}
        canApprove={
          workflow.canApprove
        }
        canReject={workflow.canReject}
        onEdit={() => {
          navigate(
            `/test-results/${result.uuid}/edit`
          );
        }}
        onSubmit={() => {
          void workflow.submit();
        }}
        onApprove={() => {
          void workflow.approve();
        }}
        onReject={workflow.openReject}
        onClearError={() => {
          workflow.setError(null);
        }}
      />

      <TestResultDetailPaper
        result={result}
      />

      <RejectResultDialog
        open={workflow.rejectOpen}
        reason={
          workflow.rejectionReason
        }
        rejecting={
          workflow.busyAction ===
          'reject'
        }
        onReasonChange={
          workflow.setRejectionReason
        }
        onClose={workflow.closeReject}
        onConfirm={() => {
          void workflow.reject();
        }}
      />
    </Layout>
  );
};

export default TestResultView;
