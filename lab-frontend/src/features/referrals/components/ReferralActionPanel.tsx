import {
  Button,
  Stack,
} from "@mui/material";

type Props = {
  status?: string;
  loading?: boolean;
  standardResultStatus?: string | null;
  standardResultUuid?: string | null;
  canDispatchWorkflow?: boolean;
  canCaptureResults?: boolean;
  onDispatch?: () => void;
  onAcknowledge?: () => void;
  onReject?: () => void;
  onCaptureResult?: () => void;
  onCancel?: () => void;
};

export default function ReferralActionPanel({
  status,
  loading = false,
  standardResultStatus,
  standardResultUuid,
  canDispatchWorkflow = false,
  canCaptureResults = false,
  onDispatch,
  onAcknowledge,
  onReject,
  onCaptureResult,
  onCancel,
}: Props) {
  const canCaptureInitialResult =
    canCaptureResults &&
    status === "acknowledged";

  const canCaptureCorrection =
    canCaptureResults &&
    status === "result_received" &&
    (
      standardResultStatus === "draft" ||
      standardResultStatus === "rejected"
    );

  const canReject =
    canDispatchWorkflow &&
    (
      status === "dispatched" ||
      status === "acknowledged"
    );

  const canCancel =
    canDispatchWorkflow &&
    [
      "draft",
      "dispatched",
      "acknowledged",
    ].includes(status ?? "");

  return (
    <Stack
      direction={{
        xs: "column",
        sm: "row",
      }}
      spacing={1}
    >
      {canDispatchWorkflow &&
      status === "draft" ? (
        <Button
          variant="contained"
          onClick={onDispatch}
          disabled={loading}
        >
          Dispatch
        </Button>
      ) : null}

      {canDispatchWorkflow &&
      status === "dispatched" ? (
        <Button
          variant="contained"
          onClick={onAcknowledge}
          disabled={loading}
        >
          Acknowledge
        </Button>
      ) : null}

      {canCaptureInitialResult ? (
        <Button
          variant="contained"
          onClick={onCaptureResult}
          disabled={loading}
        >
          Capture External Result
        </Button>
      ) : null}

      {canCaptureCorrection ? (
        <Button
          variant="outlined"
          onClick={onCaptureResult}
          disabled={loading}
        >
          Add Corrected Result
        </Button>
      ) : null}

      {standardResultUuid ? (
        <Button
          variant="outlined"
          onClick={() => {
            window.location.assign(
              `/test-results/${standardResultUuid}`,
            );
          }}
          disabled={loading}
        >
          Open Test Result
        </Button>
      ) : null}

      {canReject ? (
        <Button
          color="warning"
          variant="outlined"
          onClick={onReject}
          disabled={loading}
        >
          Reject
        </Button>
      ) : null}

      {canCancel ? (
        <Button
          color="error"
          variant="outlined"
          onClick={onCancel}
          disabled={loading}
        >
          Cancel
        </Button>
      ) : null}
    </Stack>
  );
}
