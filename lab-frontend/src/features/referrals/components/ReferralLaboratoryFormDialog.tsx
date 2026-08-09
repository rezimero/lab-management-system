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
} from "@mui/material";

import type {
  ReferralLaboratoryPayload,
} from "../types/referral.types";

type Props = {
  open: boolean;
  onClose: () => void;
  onSubmit: (
    payload:
      ReferralLaboratoryPayload,
  ) => Promise<unknown> | unknown;
  initial?: Partial<
    ReferralLaboratoryPayload
  >;
  loading?: boolean;
  error?: string | null;
};

export default function ReferralLaboratoryFormDialog({
  open,
  onClose,
  onSubmit,
  initial = {},
  loading = false,
  error,
}: Props) {
  const [
    name,
    setName,
  ] = useState("");

  const [
    code,
    setCode,
  ] = useState("");

  const [
    contactPerson,
    setContactPerson,
  ] = useState("");

  const [
    email,
    setEmail,
  ] = useState("");

  const [
    phone,
    setPhone,
  ] = useState("");

  const [
    localError,
    setLocalError,
  ] = useState<string | null>(
    null,
  );

  useEffect(() => {
    if (!open) {
      return;
    }

    setName(
      initial.name ?? "",
    );

    setCode(
      initial.code ?? "",
    );

    setContactPerson(
      initial.contact_person ??
        "",
    );

    setEmail(
      initial.email ?? "",
    );

    setPhone(
      initial.phone ?? "",
    );

    setLocalError(null);
  }, [
    open,
    initial.name,
    initial.code,
    initial.contact_person,
    initial.email,
    initial.phone,
  ]);

  const submit = async () => {
    if (!name.trim()) {
      setLocalError(
        "Laboratory name is required.",
      );

      return;
    }

    setLocalError(null);

    try {
      await onSubmit({
        name: name.trim(),
        code:
          code.trim() ||
          undefined,
        contact_person:
          contactPerson.trim() ||
          undefined,
        email:
          email.trim() ||
          undefined,
        phone:
          phone.trim() ||
          undefined,
      });

      onClose();
    } catch {
      // Parent exposes sanitized
      // API feedback.
    }
  };

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
        {initial.name
          ? "Edit Referral Laboratory"
          : "Add Referral Laboratory"}
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
          {localError || error ? (
            <Alert severity="error">
              {localError ?? error}
            </Alert>
          ) : null}

          <TextField
            label="Laboratory Name"
            value={name}
            onChange={(event) =>
              setName(
                event.target.value,
              )
            }
            disabled={loading}
            required
            fullWidth
          />

          <TextField
            label="Code"
            value={code}
            onChange={(event) =>
              setCode(
                event.target.value,
              )
            }
            disabled={loading}
            fullWidth
          />

          <TextField
            label="Contact Person"
            value={contactPerson}
            onChange={(event) =>
              setContactPerson(
                event.target.value,
              )
            }
            disabled={loading}
            fullWidth
          />

          <TextField
            label="Email"
            type="email"
            value={email}
            onChange={(event) =>
              setEmail(
                event.target.value,
              )
            }
            disabled={loading}
            fullWidth
          />

          <TextField
            label="Phone"
            value={phone}
            onChange={(event) =>
              setPhone(
                event.target.value,
              )
            }
            disabled={loading}
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
            !name.trim()
          }
        >
          {loading
            ? "Saving..."
            : "Save"}
        </Button>
      </DialogActions>
    </Dialog>
  );
}
