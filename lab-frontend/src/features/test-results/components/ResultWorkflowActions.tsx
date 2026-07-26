import {
  Alert,
  Box,
  Button,
} from '@mui/material';

import {
  CheckCircle as ApproveIcon,
  Edit as EditIcon,
  Send as SubmitIcon,
  Cancel as RejectIcon,
} from '@mui/icons-material';

interface Props {
  error: string | null;
  busyAction: string | null;
  canEdit: boolean;
  canSubmit: boolean;
  canApprove: boolean;
  canReject: boolean;
  onEdit: () => void;
  onSubmit: () => void;
  onApprove: () => void;
  onReject: () => void;
  onClearError: () => void;
}

const ResultWorkflowActions = ({
  error,
  busyAction,
  canEdit,
  canSubmit,
  canApprove,
  canReject,
  onEdit,
  onSubmit,
  onApprove,
  onReject,
  onClearError,
}: Props) => {
  const hasActions =
    canEdit ||
    canSubmit ||
    canApprove ||
    canReject;

  return (
    <Box
      className="no-print"
      sx={{
        mb: hasActions || error
          ? 3
          : 0,
      }}
    >
      {error && (
        <Alert
          severity="error"
          sx={{
            mb: hasActions ? 2 : 0,
          }}
          onClose={onClearError}
        >
          {error}
        </Alert>
      )}

      {hasActions && (
        <Box
          sx={{
            display: 'flex',
            flexWrap: 'wrap',
            justifyContent: 'flex-end',
            gap: 1.5,
          }}
        >
          {canEdit && (
            <Button
              variant="outlined"
              startIcon={<EditIcon />}
              disabled={Boolean(busyAction)}
              onClick={onEdit}
            >
              Edit result
            </Button>
          )}

          {canSubmit && (
            <Button
              variant="contained"
              startIcon={<SubmitIcon />}
              disabled={Boolean(busyAction)}
              onClick={onSubmit}
            >
              {busyAction === 'submit'
                ? 'Submitting...'
                : 'Submit for review'}
            </Button>
          )}

          {canReject && (
            <Button
              color="error"
              variant="outlined"
              startIcon={<RejectIcon />}
              disabled={Boolean(busyAction)}
              onClick={onReject}
            >
              Reject result
            </Button>
          )}

          {canApprove && (
            <Button
              color="success"
              variant="contained"
              startIcon={<ApproveIcon />}
              disabled={Boolean(busyAction)}
              onClick={onApprove}
            >
              {busyAction === 'approve'
                ? 'Approving...'
                : 'Approve result'}
            </Button>
          )}
        </Box>
      )}
    </Box>
  );
};

export default ResultWorkflowActions;
