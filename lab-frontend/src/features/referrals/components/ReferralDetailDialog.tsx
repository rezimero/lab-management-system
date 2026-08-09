import {
  useEffect,
  useState,
} from "react";

import {
  Alert,
  Box,
  Button,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  Divider,
} from "@mui/material";

import type {
  CaptureReferralResultPayload,
  Referral,
} from "../types/referral.types";

import ReferralActionPanel from "./ReferralActionPanel";
import ReferralDetailPanel from "./ReferralDetailPanel";
import ReferralReasonDialog from "./ReferralReasonDialog";
import ReferralResultCaptureDialog from "./ReferralResultCaptureDialog";
import ReferralTimeline from "./ReferralTimeline";

type ReasonAction =
  | "reject"
  | "cancel";

type Props = {
  referral: Referral | null;
  open: boolean;
  loading?: boolean;
  error?: string | null;
  canDispatchWorkflow?: boolean;
  canCaptureResults?: boolean;
  onClose: () => void;
  onDispatch: () => void;
  onAcknowledge: () => void;
  onReject: (
    reason: string,
  ) => Promise<unknown> | unknown;
  onCaptureResult: (
    payload: CaptureReferralResultPayload,
  ) => Promise<unknown> | unknown;
  onCancel: (
    reason: string,
  ) => Promise<unknown> | unknown;
};

export default function ReferralDetailDialog({
  referral,
  open,
  loading = false,
  error,
  canDispatchWorkflow = false,
  canCaptureResults = false,
  onClose,
  onDispatch,
  onAcknowledge,
  onReject,
  onCaptureResult,
  onCancel,
}: Props) {
  const [
    resultDialogOpen,
    setResultDialogOpen,
  ] = useState(false);

  const [
    reasonAction,
    setReasonAction,
  ] = useState<
    ReasonAction | null
  >(null);

  useEffect(() => {
    if (!open) {
      setResultDialogOpen(false);
      setReasonAction(null);
    }
  }, [open]);

  return (
    <>
      <Dialog
        open={open}
        onClose={
          loading
            ? undefined
            : onClose
        }
        fullWidth
        maxWidth="md"
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
          {referral
            ?.referral_number
            ? `Referral ${referral.referral_number}`
            : "Referral Details"}
        </DialogTitle>

        <DialogContent
          sx={{
            px: {
              xs: 2,
              sm: 3,
            },
            py: 2,
          }}
        >
          <Box
            sx={{
              display: "grid",
              gap: 2.5,
              pt: 1,
            }}
          >
            {error ? (
              <Alert severity="error">
                {error}
              </Alert>
            ) : null}

            {referral ? (
              <>
                <ReferralActionPanel
                  status={
                    referral.status
                  }
                  loading={
                    loading
                  }
                  standardResultStatus={
                    referral.standard_result
                      ?.status ?? null
                  }
                  standardResultUuid={
                    referral.standard_result
                      ?.uuid ?? null
                  }
                  canDispatchWorkflow={
                    canDispatchWorkflow
                  }
                  canCaptureResults={
                    canCaptureResults
                  }
                  onDispatch={
                    onDispatch
                  }
                  onAcknowledge={
                    onAcknowledge
                  }
                  onReject={() =>
                    setReasonAction(
                      "reject",
                    )
                  }
                  onCaptureResult={() =>
                    setResultDialogOpen(
                      true,
                    )
                  }
                  onCancel={() =>
                    setReasonAction(
                      "cancel",
                    )
                  }
                />

                <Divider />

                <ReferralDetailPanel
                  referral={
                    referral
                  }
                />

                <ReferralTimeline
                  referral={
                    referral
                  }
                />
              </>
            ) : null}
          </Box>
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
            Close
          </Button>
        </DialogActions>
      </Dialog>

      {referral ? (
        <ReferralResultCaptureDialog
          open={resultDialogOpen}
          referral={referral}
          loading={loading}
          error={error}
          onClose={() =>
            setResultDialogOpen(
              false,
            )
          }
          onSubmit={
            onCaptureResult
          }
        />
      ) : null}

      {reasonAction ? (
        <ReferralReasonDialog
          open={Boolean(
            reasonAction,
          )}
          action={
            reasonAction
          }
          loading={loading}
          error={error}
          onClose={() =>
            setReasonAction(null)
          }
          onSubmit={
            reasonAction ===
            "reject"
              ? onReject
              : onCancel
          }
        />
      ) : null}
    </>
  );
}
