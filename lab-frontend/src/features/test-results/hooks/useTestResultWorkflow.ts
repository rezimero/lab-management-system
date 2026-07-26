import {
  useState,
} from 'react';
import type {
  TestResultResource,
} from '../../../types';
import {
  approveTestResult,
  rejectTestResult,
  submitTestResult,
} from '../api/testResultViewApi';
import {
  roleAllowsTestResult,
} from '../permissions/testResultPermissions';
import {
  apiErrorMessage,
} from '../utils/apiErrorMessage';
interface Options {
  result: TestResultResource | null;
  role?: string | null;
  onUpdated: (
    result: TestResultResource
  ) => void;
}
type ActionName =
  | 'submit'
  | 'approve'
  | 'reject';
export const useTestResultWorkflow = ({
  result,
  role,
  onUpdated,
}: Options) => {
  const [
    busyAction,
    setBusyAction,
  ] = useState<ActionName | null>(null);
  const [
    error,
    setError,
  ] = useState<string | null>(null);
  const [
    rejectOpen,
    setRejectOpen,
  ] = useState(false);
  const [
    rejectionReason,
    setRejectionReason,
  ] = useState('');
  const mayEdit = roleAllowsTestResult(
    role,
    'edit_test_results'
  );
  const mayApprove = roleAllowsTestResult(
    role,
    'approve_test_results'
  );
  const canEdit = Boolean(
    result &&
    mayEdit &&
    (
      result.status === 'draft' ||
      result.status === 'rejected'
    )
  );
  const canSubmit = Boolean(
    result &&
    mayEdit &&
    result.status === 'draft'
  );
  const canApprove = Boolean(
    result &&
    mayApprove &&
    result.status === 'submitted'
  );
  const canReject = canApprove;
  const run = async (
    action: ActionName,
    request: () => Promise<TestResultResource>
  ) => {
    setBusyAction(action);
    setError(null);
    try {
      const updated = await request();
      onUpdated(updated);
      return true;
    } catch (requestError: unknown) {
      setError(
        apiErrorMessage(
          requestError,
          `Failed to ${action} the test result.`
        )
      );
      return false;
    } finally {
      setBusyAction(null);
    }
  };
  const submit = async () => {
    if (!result || !canSubmit) {
      return;
    }
    await run(
      'submit',
      () => submitTestResult(result.uuid)
    );
  };
  const approve = async () => {
    if (!result || !canApprove) {
      return;
    }
    await run(
      'approve',
      () => approveTestResult(result.uuid)
    );
  };
  const reject = async () => {
    if (!result || !canReject) {
      return;
    }
    const reason = rejectionReason.trim();
    if (!reason) {
      setError(
        'A rejection reason is required.'
      );
      return;
    }
    if (reason.length > 5000) {
      setError(
        'The rejection reason cannot exceed 5000 characters.'
      );
      return;
    }
    const succeeded = await run(
      'reject',
      () => rejectTestResult(
        result.uuid,
        reason
      )
    );
    if (succeeded) {
      setRejectOpen(false);
      setRejectionReason('');
    }
  };
  return {
    busyAction,
    error,
    rejectOpen,
    rejectionReason,
    canEdit,
    canSubmit,
    canApprove,
    canReject,
    setError,
    setRejectionReason,
    openReject: () => {
      setError(null);
      setRejectOpen(true);
    },
    closeReject: () => {
      if (!busyAction) {
        setRejectOpen(false);
      }
    },
    submit,
    approve,
    reject,
  };
};
