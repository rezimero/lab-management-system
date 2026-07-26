import {
  Alert,
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
  orderUuid: string;
  submitting: boolean;
  error: string | null;
  onOrderUuidChange: (
    value: string
  ) => void;
  onClose: () => void;
  onGenerate: () => void;
}

const GenerateReportDialog = ({
  open,
  orderUuid,
  submitting,
  error,
  onOrderUuidChange,
  onClose,
  onGenerate,
}: Props) => (
  <Dialog
    open={open}
    onClose={onClose}
    fullWidth
    maxWidth="sm"
  >
    <DialogTitle>
      Generate laboratory report
    </DialogTitle>

    <DialogContent>
      <DialogContentText
        sx={{ mb: 2 }}
      >
        Enter the UUID of a completed
        laboratory order. Every active
        item must have an approved result.
      </DialogContentText>

      {error && (
        <Alert
          severity="error"
          sx={{ mb: 2 }}
        >
          {error}
        </Alert>
      )}

      <TextField
        autoFocus
        fullWidth
        required
        label="Laboratory order UUID"
        value={orderUuid}
        onChange={(event) => {
          onOrderUuidChange(
            event.target.value
          );
        }}
      />
    </DialogContent>

    <DialogActions>
      <Button
        onClick={onClose}
        disabled={submitting}
      >
        Cancel
      </Button>

      <Button
        variant="contained"
        onClick={onGenerate}
        disabled={
          submitting ||
          orderUuid.trim() === ''
        }
      >
        {submitting
          ? 'Generating...'
          : 'Generate report'}
      </Button>
    </DialogActions>
  </Dialog>
);

export default GenerateReportDialog;
