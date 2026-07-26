import type { FormEvent } from 'react';

import {
  useNavigate,
  useParams,
  useSearchParams,
} from 'react-router-dom';

import {
  Divider,
  Paper,
  TextField,
} from '@mui/material';

import Layout from '../components/Layout/Layout';

import ResultContextSummary from '../features/test-results/components/ResultContextSummary';
import ResultParametersEditor from '../features/test-results/components/ResultParametersEditor';
import ResultWorkflowNotice from '../features/test-results/components/ResultWorkflowNotice';
import TestResultFormActions from '../features/test-results/components/TestResultFormActions';
import TestResultFormHeader from '../features/test-results/components/TestResultFormHeader';
import TestResultFormLoading from '../features/test-results/components/TestResultFormLoading';

import {
  useTestResultForm,
} from '../features/test-results/hooks/useTestResultForm';

import {
  isEditableResultStatus,
} from '../features/test-results/services/testResultFormLoader';

const TestResultForm = () => {
  const navigate = useNavigate();

  const {
    id: resultUuid,
  } = useParams<{
    id: string;
  }>();

  const [searchParams] = useSearchParams();

  const form = useTestResultForm({
    resultUuid,
    orderUuid:
      searchParams.get('order'),
    orderItemUuid:
      searchParams.get('item'),
  });

  const pageTitle = form.isEdit
    ? 'Edit Test Result'
    : 'Enter Test Result';

  const goToList = () => {
    navigate('/test-results');
  };

  const handleSubmit = async (
    event: FormEvent<HTMLFormElement>
  ) => {
    event.preventDefault();

    const savedUuid =
      await form.save();

    if (savedUuid) {
      navigate(
        `/test-results/${savedUuid}`
      );
    }
  };

  const openExistingResult = () => {
    const existing =
      form.existingResult;

    if (!existing) {
      return;
    }

    navigate(
      isEditableResultStatus(
        existing.status
      )
        ? `/test-results/${existing.uuid}/edit`
        : `/test-results/${existing.uuid}`
    );
  };

  if (form.fetching) {
    return (
      <Layout title={pageTitle}>
        <TestResultFormLoading />
      </Layout>
    );
  }

  return (
    <Layout title={pageTitle}>
      <TestResultFormHeader
        title={pageTitle}
        isEdit={form.isEdit}
        onBack={goToList}
      />

      <ResultWorkflowNotice
        isEdit={form.isEdit}
        error={form.error}
        blockingMessage={
          form.blockingMessage
        }
        rejectionReason={
          form.rejectionReason
        }
        existingResult={
          form.existingResult
        }
        onClearError={() => {
          form.setError(null);
        }}
        onOpenExisting={
          openExistingResult
        }
        onViewCurrent={() => {
          if (resultUuid) {
            navigate(
              `/test-results/${resultUuid}`
            );
          }
        }}
      />

      <Paper
        sx={{
          p: {
            xs: 2,
            md: 3,
          },
          borderRadius: 3,
        }}
      >
        <ResultContextSummary
          context={form.context}
          status={form.status}
        />

        <Divider sx={{ mb: 3 }} />

        <form onSubmit={handleSubmit}>
          <ResultParametersEditor
            rows={form.rows}
            disabled={
              Boolean(
                form.blockingMessage
              )
            }
            onAdd={form.addRow}
            onRemove={form.removeRow}
            onChange={form.updateRow}
          />

          <TextField
            fullWidth
            multiline
            rows={4}
            label="Overall notes"
            value={form.notes}
            disabled={
              Boolean(
                form.blockingMessage
              )
            }
            onChange={(event) => {
              form.setNotes(
                event.target.value
              );
            }}
            sx={{ mt: 1 }}
          />

          <TestResultFormActions
            isEdit={form.isEdit}
            saving={form.saving}
            canSave={form.canSave}
            blocked={
              Boolean(
                form.blockingMessage
              )
            }
            onCancel={goToList}
          />
        </form>
      </Paper>
    </Layout>
  );
};

export default TestResultForm;
