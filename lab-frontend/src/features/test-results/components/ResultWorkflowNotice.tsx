import {
  Alert,
  Button,
  Typography,
} from '@mui/material';

import type {
  TestResultResource,
} from '../../../types';

interface Props {
  isEdit: boolean;
  error: string | null;
  blockingMessage: string | null;
  rejectionReason: string | null;
  existingResult: TestResultResource | null;
  onClearError: () => void;
  onOpenExisting: () => void;
  onViewCurrent: () => void;
}

const ResultWorkflowNotice = ({
  isEdit,
  error,
  blockingMessage,
  rejectionReason,
  existingResult,
  onClearError,
  onOpenExisting,
  onViewCurrent,
}: Props) => (
  <>
    {error && (
      <Alert
        severity="error"
        sx={{
          mb: 2,
        }}
        onClose={onClearError}
      >
        {error}
      </Alert>
    )}

    {blockingMessage && (
      <Alert
        severity="warning"
        sx={{
          mb: 2,
        }}
        action={
          existingResult ? (
            <Button
              color="inherit"
              size="small"
              onClick={onOpenExisting}
            >
              Open existing result
            </Button>
          ) : isEdit ? (
            <Button
              color="inherit"
              size="small"
              onClick={onViewCurrent}
            >
              View result
            </Button>
          ) : undefined
        }
      >
        {blockingMessage}
      </Alert>
    )}

    {isEdit && rejectionReason && (
      <Alert
        severity="error"
        sx={{
          mb: 2,
        }}
      >
        <Typography
          variant="subtitle2"
          sx={{
            fontWeight: 700,
          }}
        >
          Previous rejection reason
        </Typography>

        {rejectionReason}
      </Alert>
    )}

    {!isEdit &&
      !blockingMessage &&
      !error && (
        <Alert
          severity="info"
          sx={{
            mb: 2,
          }}
        >
          Saving creates a draft result.
          The backend also requires a
          received specimen for this
          order item.
        </Alert>
      )}
  </>
);

export default ResultWorkflowNotice;
