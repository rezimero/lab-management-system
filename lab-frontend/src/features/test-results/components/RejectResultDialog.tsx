import {
  Button,
  Dialog,
  DialogActions,
  DialogContent,
  DialogContentText,
  DialogTitle,
  TextField,
} from '@mui/material';

interface Props {
  open: boolean;
  reason: string;
  rejecting: boolean;
  onReasonChange: (
    value: string
  ) => void;
  onClose: () => void;
  onConfirm: () => void;
}

const RejectResultDialog = ({
  open,
  reason,
  rejecting,
  onReasonChange,
  onClose,
  onConfirm,
}: Props) => (
  <Dialog
    open={open}
    onClose={onClose}
    fullWidth
    maxWidth="sm"
  >
    <DialogTitle>
      Reject test result
    </DialogTitle>

    <DialogContent>
      <DialogContentText
        sx={{
          mb: 2,
        }}
      >
        Explain what must be corrected
        before this result can be
        submitted again.
      </DialogContentText>

      <TextField
        autoFocus
        fullWidth
        required
        multiline
        minRows={4}
        label="Rejection reason"
        value={reason}
        slotProps={{
          htmlInput: {
            maxLength: 5000,
          },
        }}
        helperText={
          `${reason.length}/5000 characters`
        }
        onChange={(event) => {
          onReasonChange(
            event.target.value
          );
        }}
      />
    </DialogContent>

    <DialogActions>
      <Button
        onClick={onClose}
        disabled={rejecting}
      >
        Cancel
      </Button>

      <Button
        color="error"
        variant="contained"
        onClick={onConfirm}
        disabled={
          rejecting ||
          reason.trim() === ''
        }
      >
        {rejecting
          ? 'Rejecting...'
          : 'Reject result'}
      </Button>
    </DialogActions>
  </Dialog>
);

export default RejectResultDialog;
