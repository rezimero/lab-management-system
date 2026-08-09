import {
  useEffect,
  useState,
} from "react";

import {
  Alert,
  Button,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  Stack,
  TextField,
  Typography,
} from "@mui/material";

type Action = "reject" | "cancel";

type Props = {
  open: boolean;
  action: Action;
  loading?: boolean;
  error?: string | null;
  onClose: () => void;
  onSubmit: (
    reason: string,
  ) => Promise<unknown> | unknown;
};

export default function ReferralReasonDialog({
  open,
  action,
  loading = false,
  error,
  onClose,
  onSubmit,
}: Props) {
  const [
    reason,
    setReason,
  ] = useState("");

  const [
    localError,
    setLocalError,
  ] = useState<string | null>(
    null,
  );

  useEffect(() => {
    if (open) {
      setReason("");
      setLocalError(null);
    }
  }, [open, action]);

  const submit = async () => {
    const cleanReason =
      reason.trim();

    if (cleanReason.length < 5) {
      setLocalError(
        "Reason must contain at least 5 characters.",
      );
      return;
    }

    setLocalError(null);

    try {
      await onSubmit(
        cleanReason,
      );

      onClose();
    } catch {
      // Parent exposes sanitized API feedback.
    }
  };

  const rejecting =
    action === "reject";

  return (
    <Dialog
      open={open}
      onClose={
        loading
          ? undefined
          : onClose
      }
      fullWidth
      maxWidth="sm"
      sx={{
        "& .MuiDialog-paper": {
          m: {
            xs: 0,
            sm: 2,
          },
          width: {
            xs: "100%",
            sm: "calc(100% - 32px)",
          },
          height: {
            xs: "100%",
            sm: "auto",
          },
          maxHeight: {
            xs: "100%",
            sm: "calc(100% - 32px)",
          },
          borderRadius: {
            xs: 0,
            sm: 2.5,
          },
        },
      }}
    >
      <DialogTitle
        sx={{
          position: "sticky",
          top: 0,
          zIndex: 2,
          bgcolor: "background.paper",
          borderBottom: "1px solid",
          borderColor: "divider",
        }}
      >
        {rejecting
          ? "Reject Referral"
          : "Cancel Referral"}
      </DialogTitle>

      <DialogContent
        sx={{
          px: {
            xs: 2,
            sm: 3,
          },
        }}
      >
        <Stack
          spacing={1.5}
          sx={{ mt: 1 }}
        >
          <Typography
            sx={{
              color: "#526175",
              fontSize: "0.82rem",
              lineHeight: 1.5,
            }}
          >
            {rejecting
              ? "Use rejection when the external laboratory cannot accept or perform the referral. The referral will close and the order item can be referred again or processed locally."
              : "Use cancellation when your laboratory withdraws the referral before an external result is captured. The referral will close and the order item can be referred again or processed locally."}
          </Typography>

          {localError || error ? (
            <Alert severity="error">
              {localError ?? error}
            </Alert>
          ) : null}

          <TextField
            label={
              rejecting
                ? "Rejection reason"
                : "Cancellation reason"
            }
            value={reason}
            onChange={(event) =>
              setReason(
                event.target.value,
              )
            }
            disabled={loading}
            multiline
            minRows={4}
            fullWidth
            required
          />
        </Stack>
      </DialogContent>

      <DialogActions
        sx={{
          position: "sticky",
          bottom: 0,
          zIndex: 2,
          bgcolor: "background.paper",
          borderTop: "1px solid",
          borderColor: "divider",
          px: {
            xs: 2,
            sm: 3,
          },
          py: 1.25,
        }}
      >
        <Button
          onClick={onClose}
          disabled={loading}
        >
          Keep Referral
        </Button>

        <Button
          color={
            rejecting
              ? "warning"
              : "error"
          }
          variant="contained"
          onClick={() => {
            void submit();
          }}
          disabled={
            loading ||
            reason.trim().length < 5
          }
        >
          {loading
            ? "Saving..."
            : rejecting
              ? "Reject Referral"
              : "Cancel Referral"}
        </Button>
      </DialogActions>
    </Dialog>
  );
}
