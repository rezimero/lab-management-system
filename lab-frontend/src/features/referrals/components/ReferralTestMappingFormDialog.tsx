import {
  useEffect,
  useMemo,
  useState,
} from "react";

import {
  Alert,
  Button,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  FormControlLabel,
  MenuItem,
  Stack,
  Switch,
  TextField,
  Typography,
} from "@mui/material";

import type {
  ReferralLabTestOption,
  ReferralTestMapping,
  ReferralTestMappingPayload,
} from "../types/referral.types";

type Props = {
  open: boolean;
  mapping?: ReferralTestMapping | null;
  labTests: ReferralLabTestOption[];
  loading?: boolean;
  error?: string | null;
  onClose: () => void;
  onSubmit: (
    payload: ReferralTestMappingPayload,
  ) => Promise<unknown> | unknown;
};

export default function ReferralTestMappingFormDialog({
  open,
  mapping = null,
  labTests,
  loading = false,
  error,
  onClose,
  onSubmit,
}: Props) {
  const [labTestUuid, setLabTestUuid] = useState("");
  const [externalTestCode, setExternalTestCode] = useState("");
  const [externalTestName, setExternalTestName] = useState("");
  const [cost, setCost] = useState("0.00");
  const [currency, setCurrency] = useState("NGN");
  const [turnaroundHours, setTurnaroundHours] = useState("");
  const [specimenType, setSpecimenType] = useState("");
  const [container, setContainer] = useState("");
  const [minimumVolume, setMinimumVolume] = useState("");
  const [instructions, setInstructions] = useState("");
  const [isActive, setIsActive] = useState(true);
  const [localError, setLocalError] = useState<string | null>(null);

  useEffect(() => {
    if (!open) {
      return;
    }

    setLabTestUuid(mapping?.lab_test?.uuid ?? "");
    setExternalTestCode(mapping?.external_test_code ?? "");
    setExternalTestName(mapping?.external_test_name ?? "");
    setCost(String(mapping?.cost ?? "0.00"));
    setCurrency(mapping?.currency ?? "NGN");
    setTurnaroundHours(
      mapping?.turnaround_hours
        ? String(mapping.turnaround_hours)
        : "",
    );
    setSpecimenType(
      mapping?.specimen_requirements?.specimen_type ?? "",
    );
    setContainer(
      mapping?.specimen_requirements?.container ?? "",
    );
    setMinimumVolume(
      mapping?.specimen_requirements?.minimum_volume ?? "",
    );
    setInstructions(
      mapping?.specimen_requirements?.instructions ?? "",
    );
    setIsActive(mapping?.is_active !== false);
    setLocalError(null);
  }, [open, mapping]);

  const selectedLabTest = useMemo(
    () =>
      labTests.find(
        (test) => test.uuid === labTestUuid,
      ),
    [labTests, labTestUuid],
  );

  const submit = async () => {
    if (!labTestUuid) {
      setLocalError("Select a local laboratory test.");
      return;
    }

    if (!externalTestCode.trim()) {
      setLocalError("External test code is required.");
      return;
    }

    if (!externalTestName.trim()) {
      setLocalError("External test name is required.");
      return;
    }

    const numericCost = Number(cost);

    if (!Number.isFinite(numericCost) || numericCost < 0) {
      setLocalError("Cost must be zero or a positive number.");
      return;
    }

    const turnaroundValue =
      turnaroundHours.trim() === ""
        ? null
        : Number(turnaroundHours);

    if (
      turnaroundValue !== null &&
      (
        !Number.isInteger(turnaroundValue) ||
        turnaroundValue < 1 ||
        turnaroundValue > 87600
      )
    ) {
      setLocalError(
        "Turnaround hours must be a whole number between 1 and 87600.",
      );
      return;
    }

    const cleanRequirements = {
      specimen_type: specimenType.trim() || null,
      container: container.trim() || null,
      minimum_volume: minimumVolume.trim() || null,
      instructions: instructions.trim() || null,
    };

    const hasRequirements = Object.values(cleanRequirements).some(Boolean);

    setLocalError(null);

    try {
      await onSubmit({
        lab_test_uuid: labTestUuid,
        external_test_code: externalTestCode.trim(),
        external_test_name: externalTestName.trim(),
        cost: numericCost.toFixed(2),
        currency: currency.trim().toUpperCase() || "NGN",
        turnaround_hours: turnaroundValue,
        specimen_requirements: hasRequirements
          ? cleanRequirements
          : null,
        is_active: isActive,
      });

      onClose();
    } catch {
      // The parent surfaces sanitized API feedback.
    }
  };

  return (
    <Dialog
      open={open}
      onClose={loading ? undefined : onClose}
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
        {mapping ? "Edit Test Mapping" : "Add Test Mapping"}
      </DialogTitle>

      <DialogContent>
        <Stack spacing={1.75} sx={{ mt: 1 }}>
          <Typography
            sx={{
              color: "#526175",
              fontSize: "0.82rem",
              lineHeight: 1.5,
            }}
          >
            Map one active internal laboratory test to the corresponding
            test offered by the selected external laboratory.
          </Typography>

          {localError || error ? (
            <Alert severity="error">
              {localError ?? error}
            </Alert>
          ) : null}

          <TextField
            select
            label="Internal laboratory test"
            value={labTestUuid}
            onChange={(event) =>
              setLabTestUuid(event.target.value)
            }
            disabled={loading}
            fullWidth
            required
          >
            {labTests.length === 0 ? (
              <MenuItem value="" disabled>
                No active laboratory tests with UUIDs are available
              </MenuItem>
            ) : (
              labTests.map((test) => (
                <MenuItem key={test.uuid} value={test.uuid}>
                  {test.name}
                  {test.category ? ` · ${test.category}` : ""}
                </MenuItem>
              ))
            )}
          </TextField>

          {selectedLabTest ? (
            <Typography
              sx={{
                color: "#667085",
                fontSize: "0.74rem",
                mt: -0.75,
              }}
            >
              Local test UUID: {selectedLabTest.uuid}
            </Typography>
          ) : null}

          <TextField
            label="External test code"
            value={externalTestCode}
            onChange={(event) =>
              setExternalTestCode(event.target.value)
            }
            disabled={loading}
            fullWidth
            required
          />

          <TextField
            label="External test name"
            value={externalTestName}
            onChange={(event) =>
              setExternalTestName(event.target.value)
            }
            disabled={loading}
            fullWidth
            required
          />

          <Stack
            direction={{ xs: "column", sm: "row" }}
            spacing={1.25}
          >
            <TextField
              label="Cost"
              type="number"
              value={cost}
              onChange={(event) => setCost(event.target.value)}
              disabled={loading}
              fullWidth
              required
            />

            <TextField
              label="Currency"
              value={currency}
              onChange={(event) =>
                setCurrency(event.target.value.toUpperCase())
              }
              disabled={loading}
              fullWidth
              required
            />

            <TextField
              label="Turnaround (hours)"
              type="number"
              value={turnaroundHours}
              onChange={(event) =>
                setTurnaroundHours(event.target.value)
              }
              disabled={loading}
              fullWidth
            />
          </Stack>

          <Typography
            sx={{
              pt: 0.25,
              fontWeight: 720,
              color: "#344054",
              fontSize: "0.78rem",
            }}
          >
            Specimen requirements
          </Typography>

          <Stack
            direction={{ xs: "column", sm: "row" }}
            spacing={1.25}
          >
            <TextField
              label="Specimen type"
              value={specimenType}
              onChange={(event) =>
                setSpecimenType(event.target.value)
              }
              disabled={loading}
              fullWidth
            />

            <TextField
              label="Container"
              value={container}
              onChange={(event) =>
                setContainer(event.target.value)
              }
              disabled={loading}
              fullWidth
            />

            <TextField
              label="Minimum volume"
              value={minimumVolume}
              onChange={(event) =>
                setMinimumVolume(event.target.value)
              }
              disabled={loading}
              fullWidth
            />
          </Stack>

          <TextField
            label="Specimen instructions"
            value={instructions}
            onChange={(event) =>
              setInstructions(event.target.value)
            }
            disabled={loading}
            fullWidth
            multiline
            minRows={2}
          />

          <FormControlLabel
            control={
              <Switch
                checked={isActive}
                onChange={(event) =>
                  setIsActive(event.target.checked)
                }
                disabled={loading}
              />
            }
            label="Active mapping"
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
        <Button onClick={onClose} disabled={loading}>
          Cancel
        </Button>

        <Button
          variant="contained"
          onClick={() => {
            void submit();
          }}
          disabled={loading}
        >
          {loading
            ? "Saving..."
            : mapping
              ? "Save Changes"
              : "Add Mapping"}
        </Button>
      </DialogActions>
    </Dialog>
  );
}
