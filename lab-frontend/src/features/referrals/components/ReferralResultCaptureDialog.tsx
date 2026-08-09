import {
  useEffect,
  useMemo,
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
  MenuItem,
  Paper,
  Stack,
  TextField,
  Typography,
} from "@mui/material";

import {
  fetchLaboratoryOrder,
} from "../../laboratory-orders/api/laboratoryOrderApi";

import {
  RESULT_FLAGS,
  type ResultFormRow,
} from "../../test-results/types/testResultForm.types";

import {
  normalizeRows,
} from "../../test-results/utils/resultFormMapper";

import type {
  CaptureReferralResultPayload,
  Referral,
  ReferralResultFlag,
} from "../types/referral.types";

type Props = {
  open: boolean;
  referral: Referral;
  loading?: boolean;
  error?: string | null;
  onClose: () => void;
  onSubmit: (
    payload: CaptureReferralResultPayload,
  ) => Promise<unknown> | unknown;
};

const nullableText = (
  value: string,
): string | undefined => {
  const trimmed = value.trim();
  return trimmed === ""
    ? undefined
    : trimmed;
};

const toLocalDateTimeInput = (
  value: Date,
): string => {
  const pad = (number: number) =>
    String(number).padStart(2, "0");

  return [
    value.getFullYear(),
    "-",
    pad(value.getMonth() + 1),
    "-",
    pad(value.getDate()),
    "T",
    pad(value.getHours()),
    ":",
    pad(value.getMinutes()),
  ].join("");
};

export default function ReferralResultCaptureDialog({
  open,
  referral,
  loading = false,
  error,
  onClose,
  onSubmit,
}: Props) {
  const [
    rows,
    setRows,
  ] = useState<ResultFormRow[]>([]);

  const [
    externalReportNumber,
    setExternalReportNumber,
  ] = useState("");

  const [
    reportedAt,
    setReportedAt,
  ] = useState("");

  const [
    finalCost,
    setFinalCost,
  ] = useState("");

  const [
    notes,
    setNotes,
  ] = useState("");

  const [
    loadingParameters,
    setLoadingParameters,
  ] = useState(false);

  const [
    localError,
    setLocalError,
  ] = useState<string | null>(null);

  const latestExternalResult =
    useMemo(
      () =>
        referral.external_results?.[
          Math.max(
            0,
            (referral.external_results?.length ?? 1) - 1,
          )
        ] ?? null,
      [referral.external_results],
    );

  useEffect(() => {
    if (!open) {
      return;
    }

    let active = true;

    const load = async () => {
      setLoadingParameters(true);
      setLocalError(null);
      setExternalReportNumber(
        latestExternalResult?.external_report_number ?? "",
      );
      setReportedAt(
        toLocalDateTimeInput(new Date()),
      );
      setFinalCost(
        String(
          referral.final_cost ??
            referral.quoted_cost ??
            "",
        ),
      );
      setNotes(
        latestExternalResult?.notes ?? "",
      );

      try {
        if (
          latestExternalResult?.results_data &&
          latestExternalResult.results_data.length > 0
        ) {
          if (active) {
            setRows(
              normalizeRows(
                latestExternalResult.results_data,
              ),
            );
          }
          return;
        }

        const orderUuid =
          referral.order_item?.order?.uuid;
        const itemUuid =
          referral.order_item?.uuid;

        if (!orderUuid || !itemUuid) {
          throw new Error(
            "The referral is missing its laboratory order context.",
          );
        }

        const order =
          await fetchLaboratoryOrder(
            orderUuid,
          );

        const item =
          order.items.find(
            (candidate) =>
              candidate.uuid ===
              itemUuid,
          );

        if (!item) {
          throw new Error(
            "The referred laboratory order item could not be found.",
          );
        }

        if (active) {
          setRows(
            normalizeRows(
              item.parameters,
            ),
          );
        }
      } catch (
        requestError: unknown
      ) {
        if (!active) {
          return;
        }

        setRows([]);

        setLocalError(
          requestError instanceof Error
            ? requestError.message
            : "Failed to load ordered test parameters.",
        );
      } finally {
        if (active) {
          setLoadingParameters(false);
        }
      }
    };

    void load();

    return () => {
      active = false;
    };
  }, [
    open,
    referral,
    latestExternalResult,
  ]);

  const updateRow = (
    index: number,
    field: keyof ResultFormRow,
    value: string,
  ) => {
    setRows((current) =>
      current.map(
        (row, rowIndex) =>
          rowIndex === index
            ? {
                ...row,
                [field]: value,
              }
            : row,
      ),
    );
  };

  const submit = async () => {
    if (rows.length === 0) {
      setLocalError(
        "At least one result parameter is required.",
      );
      return;
    }

    if (
      rows.some(
        (row) =>
          row.name.trim() === "",
      )
    ) {
      setLocalError(
        "Every external result parameter requires a name.",
      );
      return;
    }

    if (
      finalCost.trim() !== ""
    ) {
      const numericCost =
        Number(finalCost);

      if (
        !Number.isFinite(numericCost) ||
        numericCost < 0
      ) {
        setLocalError(
          "Final cost must be zero or a positive number.",
        );
        return;
      }
    }

    setLocalError(null);

    const payload:
      CaptureReferralResultPayload = {
        results_data: rows.map(
          (row) => ({
            name: row.name.trim(),
            value:
              nullableText(
                row.value,
              ) ?? null,
            unit:
              nullableText(
                row.unit,
              ) ?? null,
            reference_range:
              nullableText(
                row.reference_range,
              ) ?? null,
            flag:
              row.flag === ""
                ? null
                : row.flag as ReferralResultFlag,
            notes:
              nullableText(
                row.notes,
              ) ?? null,
          }),
        ),
      };

    const reportNumber =
      nullableText(
        externalReportNumber,
      );

    if (reportNumber) {
      payload.external_report_number =
        reportNumber;
    }

    if (reportedAt) {
      payload.reported_at =
        new Date(
          reportedAt,
        ).toISOString();
    }

    const resultNotes =
      nullableText(notes);

    if (resultNotes) {
      payload.notes =
        resultNotes;
    }

    if (
      finalCost.trim() !== ""
    ) {
      payload.final_cost =
        Number(
          finalCost,
        ).toFixed(2);
    }

    try {
      await onSubmit(payload);
      onClose();
    } catch {
      // Parent exposes the sanitized API error.
    }
  };

  const isCorrection =
    referral.status ===
      "result_received";

  return (
    <Dialog
      open={open}
      onClose={
        loading
          ? undefined
          : onClose
      }
      fullWidth
      maxWidth="lg"
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
        {isCorrection
          ? "Add Corrected External Result"
          : "Capture External Result"}
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
          spacing={2}
          sx={{ mt: 1 }}
        >
          <Typography
            sx={{
              color: "#526175",
              fontSize: "0.82rem",
              lineHeight: 1.5,
            }}
          >
            Record the result received from{" "}
            {referral.laboratory?.name ??
              referral.laboratory_snapshot?.name ??
              "the external laboratory"}.
            Saving creates or refreshes the linked standard
            draft Test Result for the normal submit and approval workflow.
          </Typography>

          {localError || error ? (
            <Alert severity="error">
              {localError ?? error}
            </Alert>
          ) : null}

          {isCorrection ? (
            <Alert severity="warning">
              This creates a new immutable external-result version
              and updates the linked standard result back to Draft.
            </Alert>
          ) : null}

          <Box
            sx={{
              display: "grid",
              gridTemplateColumns: {
                xs: "1fr",
                md: "repeat(3, minmax(0, 1fr))",
              },
              gap: 1.25,
            }}
          >
            <TextField
              label="External report number"
              value={externalReportNumber}
              onChange={(event) =>
                setExternalReportNumber(
                  event.target.value,
                )
              }
              disabled={loading}
              fullWidth
            />

            <TextField
              label="Reported at"
              type="datetime-local"
              value={reportedAt}
              onChange={(event) =>
                setReportedAt(
                  event.target.value,
                )
              }
              disabled={loading}
              fullWidth
            />

            <TextField
              label="Final cost"
              type="number"
              value={finalCost}
              onChange={(event) =>
                setFinalCost(
                  event.target.value,
                )
              }
              disabled={loading}
              helperText={`Currency: ${referral.currency ?? referral.test?.currency ?? "NGN"}`}
              fullWidth
            />
          </Box>

          <Typography
            sx={{
              fontWeight: 730,
              color: "#202939",
              fontSize: "0.86rem",
            }}
          >
            Result parameters
          </Typography>

          {loadingParameters ? (
            <Typography
              sx={{
                color: "#526175",
                fontSize: "0.8rem",
              }}
            >
              Loading ordered test parameters...
            </Typography>
          ) : null}

          {!loadingParameters &&
          rows.length > 0 ? (
            <Paper
              variant="outlined"
              sx={{
                borderRadius: 2,
                overflow: "hidden",
              }}
            >
              <Box
                sx={{
                  display: "grid",
                  gridTemplateColumns: {
                    xs: "1fr",
                    lg: "1.2fr 1fr .7fr 1fr .8fr 1.2fr",
                  },
                  gap: 1,
                  px: 1.25,
                  py: 0.85,
                  bgcolor: "#f7f9fc",
                  borderBottom:
                    "1px solid",
                  borderColor:
                    "divider",
                }}
              >
                {[
                  "Parameter",
                  "Value",
                  "Unit",
                  "Reference Range",
                  "Flag",
                  "Notes",
                ].map((label) => (
                  <Typography
                    key={label}
                    sx={{
                      fontWeight: 720,
                      color: "#344054",
                      fontSize: "0.73rem",
                    }}
                  >
                    {label}
                  </Typography>
                ))}
              </Box>

              <Stack
                spacing={0}
              >
                {rows.map(
                  (
                    row,
                    index,
                  ) => (
                    <Box
                      key={`${row.name}-${index}`}
                      sx={{
                        display: "grid",
                        gridTemplateColumns: {
                          xs: "1fr",
                          lg: "1.2fr 1fr .7fr 1fr .8fr 1.2fr",
                        },
                        gap: 1,
                        p: 1.1,
                        borderBottom:
                          index <
                          rows.length - 1
                            ? "1px solid"
                            : "none",
                        borderColor:
                          "divider",
                      }}
                    >
                      <TextField
                        size="small"
                        value={row.name}
                        disabled
                        fullWidth
                      />

                      <TextField
                        size="small"
                        label="Value"
                        value={row.value}
                        onChange={(event) =>
                          updateRow(
                            index,
                            "value",
                            event.target.value,
                          )
                        }
                        disabled={loading}
                        fullWidth
                      />

                      <TextField
                        size="small"
                        label="Unit"
                        value={row.unit}
                        onChange={(event) =>
                          updateRow(
                            index,
                            "unit",
                            event.target.value,
                          )
                        }
                        disabled={loading}
                        fullWidth
                      />

                      <TextField
                        size="small"
                        label="Reference"
                        value={
                          row.reference_range
                        }
                        onChange={(event) =>
                          updateRow(
                            index,
                            "reference_range",
                            event.target.value,
                          )
                        }
                        disabled={loading}
                        fullWidth
                      />

                      <TextField
                        select
                        size="small"
                        label="Flag"
                        value={row.flag}
                        onChange={(event) =>
                          updateRow(
                            index,
                            "flag",
                            event.target.value,
                          )
                        }
                        disabled={loading}
                        fullWidth
                      >
                        <MenuItem value="">
                          None
                        </MenuItem>

                        {RESULT_FLAGS.map(
                          (flag) => (
                            <MenuItem
                              key={flag}
                              value={flag}
                            >
                              {flag}
                            </MenuItem>
                          ),
                        )}
                      </TextField>

                      <TextField
                        size="small"
                        label="Notes"
                        value={row.notes}
                        onChange={(event) =>
                          updateRow(
                            index,
                            "notes",
                            event.target.value,
                          )
                        }
                        disabled={loading}
                        fullWidth
                      />
                    </Box>
                  ),
                )}
              </Stack>
            </Paper>
          ) : null}

          <TextField
            label="External result notes"
            value={notes}
            onChange={(event) =>
              setNotes(
                event.target.value,
              )
            }
            disabled={loading}
            multiline
            minRows={3}
            fullWidth
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
          Cancel
        </Button>

        <Button
          variant="contained"
          onClick={() => {
            void submit();
          }}
          disabled={
            loading ||
            loadingParameters ||
            rows.length === 0
          }
        >
          {loading
            ? "Saving..."
            : isCorrection
              ? "Save Corrected Result"
              : "Save External Result"}
        </Button>
      </DialogActions>
    </Dialog>
  );
}
