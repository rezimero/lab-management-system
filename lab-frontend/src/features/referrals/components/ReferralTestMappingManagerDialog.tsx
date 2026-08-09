import {
  useCallback,
  useEffect,
  useMemo,
  useState,
} from "react";

import {
  Alert,
  Box,
  Button,
  Chip,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  IconButton,
  MenuItem,
  Paper,
  Stack,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  TextField,
  Tooltip,
  Typography,
} from "@mui/material";

import AddRoundedIcon from "@mui/icons-material/AddRounded";
import EditOutlinedIcon from "@mui/icons-material/EditOutlined";
import RefreshRoundedIcon from "@mui/icons-material/RefreshRounded";

import {
  referralApi,
} from "../api/referralApi";

import type {
  ReferralLaboratory,
  ReferralLabTestOption,
  ReferralTestMapping,
  ReferralTestMappingPayload,
} from "../types/referral.types";

import ReferralTestMappingFormDialog from "./ReferralTestMappingFormDialog";

type Props = {
  open: boolean;
  laboratories: ReferralLaboratory[];
  onClose: () => void;
};

function apiErrorMessage(
  error: unknown,
  fallback: string,
) {
  if (
    typeof error === "object" &&
    error !== null &&
    "response" in error
  ) {
    const response = (
      error as {
        response?: {
          data?: {
            message?: unknown;
            errors?: Record<string, unknown>;
          };
        };
      }
    ).response;

    const message = response?.data?.message;

    if (
      typeof message === "string" &&
      message.trim()
    ) {
      return message;
    }

    const errors = response?.data?.errors;

    if (
      errors &&
      typeof errors === "object"
    ) {
      for (const value of Object.values(errors)) {
        if (Array.isArray(value) && typeof value[0] === "string") {
          return value[0];
        }

        if (typeof value === "string") {
          return value;
        }
      }
    }
  }

  if (error instanceof Error && error.message.trim()) {
    return error.message;
  }

  return fallback;
}

export default function ReferralTestMappingManagerDialog({
  open,
  laboratories,
  onClose,
}: Props) {
  const [laboratoryUuid, setLaboratoryUuid] = useState("");
  const [mappings, setMappings] = useState<ReferralTestMapping[]>([]);
  const [labTests, setLabTests] = useState<ReferralLabTestOption[]>([]);
  const [loading, setLoading] = useState(false);
  const [labTestsLoading, setLabTestsLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [formError, setFormError] = useState<string | null>(null);
  const [formOpen, setFormOpen] = useState(false);
  const [editingMapping, setEditingMapping] =
    useState<ReferralTestMapping | null>(null);

  const selectedLaboratory = useMemo(
    () =>
      laboratories.find(
        (laboratory) =>
          laboratory.uuid === laboratoryUuid,
      ) ?? null,
    [laboratories, laboratoryUuid],
  );

  const loadMappings = useCallback(
    async (selectedUuid: string) => {
      if (!selectedUuid) {
        setMappings([]);
        return;
      }

      setLoading(true);
      setError(null);

      try {
        const values =
          await referralApi.getLaboratoryTestMappings(
            selectedUuid,
            {
              page: 1,
              per_page: 100,
            },
          );

        setMappings(values);
      } catch (requestError: unknown) {
        setMappings([]);
        setError(
          apiErrorMessage(
            requestError,
            "Failed to load referral test mappings.",
          ),
        );
      } finally {
        setLoading(false);
      }
    },
    [],
  );

  const loadLabTests = useCallback(async () => {
    setLabTestsLoading(true);
    setFormError(null);

    try {
      const values = await referralApi.getLabTests();
      setLabTests(values);
    } catch (requestError: unknown) {
      setLabTests([]);
      setFormError(
        apiErrorMessage(
          requestError,
          "Failed to load internal laboratory tests.",
        ),
      );
    } finally {
      setLabTestsLoading(false);
    }
  }, []);

  useEffect(() => {
    if (!open) {
      return;
    }

    const initialUuid =
      laboratories.find(
        (laboratory) => laboratory.is_active,
      )?.uuid ??
      laboratories[0]?.uuid ??
      "";

    setLaboratoryUuid(initialUuid);
    setMappings([]);
    setError(null);
    setFormError(null);
    setEditingMapping(null);
    setFormOpen(false);

    if (initialUuid) {
      void loadMappings(initialUuid);
    }

    void loadLabTests();
  }, [
    open,
    laboratories,
    loadMappings,
    loadLabTests,
  ]);

  const changeLaboratory = (
    nextUuid: string,
  ) => {
    setLaboratoryUuid(nextUuid);
    setEditingMapping(null);
    setFormOpen(false);
    void loadMappings(nextUuid);
  };

  const openCreate = () => {
    if (!laboratoryUuid) {
      setError(
        "Select a referral laboratory before adding a mapping.",
      );
      return;
    }

    if (
      selectedLaboratory &&
      selectedLaboratory.is_active === false
    ) {
      setError(
        "Activate this referral laboratory before creating an active test mapping.",
      );
    } else {
      setError(null);
    }

    setEditingMapping(null);
    setFormError(null);
    setFormOpen(true);
  };

  const openEdit = (
    mapping: ReferralTestMapping,
  ) => {
    setEditingMapping(mapping);
    setFormError(null);
    setFormOpen(true);
  };

  const toggleMappingStatus = async (
    mapping: ReferralTestMapping,
  ) => {
    const labTestUuid =
      mapping.lab_test?.uuid ??
      mapping.test_uuid ??
      "";

    if (!labTestUuid) {
      setError(
        "This mapping is missing its internal laboratory test reference and cannot be updated.",
      );
      return;
    }

    setSaving(true);
    setError(null);

    try {
      await referralApi.updateLaboratoryTestMapping(
        mapping.uuid,
        {
          lab_test_uuid: labTestUuid,
          external_test_code:
            mapping.external_test_code ?? "",
          external_test_name:
            mapping.external_test_name ?? "",
          cost:
            mapping.cost ?? "0.00",
          currency:
            mapping.currency ?? "NGN",
          turnaround_hours:
            mapping.turnaround_hours ?? null,
          specimen_requirements:
            mapping.specimen_requirements ?? null,
          is_active:
            mapping.is_active === false,
        },
      );

      await loadMappings(laboratoryUuid);
    } catch (requestError: unknown) {
      setError(
        apiErrorMessage(
          requestError,
          mapping.is_active === false
            ? "Failed to activate test mapping."
            : "Failed to deactivate test mapping.",
        ),
      );
    } finally {
      setSaving(false);
    }
  };

  const saveMapping = async (
    payload: ReferralTestMappingPayload,
  ) => {
    if (!laboratoryUuid) {
      throw new Error(
        "Select a referral laboratory.",
      );
    }

    setSaving(true);
    setFormError(null);

    try {
      if (editingMapping) {
        await referralApi.updateLaboratoryTestMapping(
          editingMapping.uuid,
          payload,
        );
      } else {
        await referralApi.createLaboratoryTestMapping(
          laboratoryUuid,
          payload,
        );
      }

      await loadMappings(laboratoryUuid);
    } catch (requestError: unknown) {
      setFormError(
        apiErrorMessage(
          requestError,
          editingMapping
            ? "Failed to update test mapping."
            : "Failed to create test mapping.",
        ),
      );

      throw requestError;
    } finally {
      setSaving(false);
    }
  };

  return (
    <>
      <Dialog
        open={open}
        onClose={saving ? undefined : onClose}
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
          Manage Referral Test Mappings
        </DialogTitle>

        <DialogContent>
          <Stack spacing={1.75} sx={{ pt: 1 }}>
            <Typography
              sx={{
                color: "#526175",
                fontSize: "0.82rem",
                lineHeight: 1.5,
              }}
            >
              Connect internal laboratory tests to the corresponding
              test codes, names, pricing and turnaround times used by
              each external referral laboratory.
            </Typography>

            {error ? (
              <Alert severity="error">
                {error}
              </Alert>
            ) : null}

            <Paper
              variant="outlined"
              sx={{
                p: 1.25,
                borderRadius: 2,
              }}
            >
              <Stack
                direction={{
                  xs: "column",
                  md: "row",
                }}
                spacing={1}
                sx={{
                  alignItems: {
                    xs: "stretch",
                    md: "center",
                  },
                }}
              >
                <TextField
                  select
                  label="Referral laboratory"
                  size="small"
                  value={laboratoryUuid}
                  onChange={(event) =>
                    changeLaboratory(
                      event.target.value,
                    )
                  }
                  sx={{
                    minWidth: {
                      xs: "100%",
                      md: 300,
                    },
                  }}
                >
                  {laboratories.length === 0 ? (
                    <MenuItem value="" disabled>
                      No referral laboratories configured
                    </MenuItem>
                  ) : (
                    laboratories.map((laboratory) => (
                      <MenuItem
                        key={laboratory.uuid}
                        value={laboratory.uuid}
                      >
                        {laboratory.name}
                        {laboratory.code
                          ? ` (${laboratory.code})`
                          : ""}
                        {laboratory.is_active
                          ? ""
                          : " · Inactive"}
                      </MenuItem>
                    ))
                  )}
                </TextField>

                <Box sx={{ flex: 1 }} />

                <Tooltip title="Refresh mappings">
                  <span>
                    <IconButton
                      onClick={() => {
                        if (laboratoryUuid) {
                          void loadMappings(laboratoryUuid);
                        }
                      }}
                      disabled={!laboratoryUuid || loading}
                    >
                      <RefreshRoundedIcon />
                    </IconButton>
                  </span>
                </Tooltip>

                <Button
                  variant="contained"
                  startIcon={<AddRoundedIcon />}
                  onClick={openCreate}
                  disabled={
                    !laboratoryUuid ||
                    labTestsLoading ||
                    selectedLaboratory?.is_active === false
                  }
                  sx={{ whiteSpace: "nowrap" }}
                >
                  Add Mapping
                </Button>
              </Stack>
            </Paper>

            {labTestsLoading ? (
              <Typography
                sx={{
                  color: "#526175",
                  fontSize: "0.78rem",
                }}
              >
                Loading internal laboratory tests...
              </Typography>
            ) : null}

            <Paper
              variant="outlined"
              sx={{
                minHeight: 220,
                borderRadius: 2,
                overflow: "hidden",
              }}
            >
              <TableContainer
              sx={{
                overflowX: "auto",
              }}
            >
                <Table
                size="small"
                aria-label="Referral test mappings"
                sx={{
                  minWidth: 760,
                }}
              >
                <TableHead>
                  <TableRow sx={{ bgcolor: "#f7f9fc" }}>
                    <TableCell
                      sx={{
                        fontWeight: 730,
                        color: "#344054",
                      }}
                    >
                      Internal Test
                    </TableCell>
                    <TableCell
                      sx={{
                        fontWeight: 730,
                        color: "#344054",
                      }}
                    >
                      External Test
                    </TableCell>
                    <TableCell
                      sx={{
                        fontWeight: 730,
                        color: "#344054",
                      }}
                    >
                      Cost
                    </TableCell>
                    <TableCell
                      sx={{
                        fontWeight: 730,
                        color: "#344054",
                      }}
                    >
                      TAT
                    </TableCell>
                    <TableCell
                      sx={{
                        fontWeight: 730,
                        color: "#344054",
                      }}
                    >
                      Status
                    </TableCell>
                    <TableCell
                      align="right"
                      sx={{
                        fontWeight: 730,
                        color: "#344054",
                      }}
                    >
                      Actions
                    </TableCell>
                  </TableRow>
                </TableHead>

                <TableBody>
                  {loading ? (
                    <TableRow>
                      <TableCell colSpan={6}>
                        <Box
                          sx={{
                            minHeight: 130,
                            display: "grid",
                            placeItems: "center",
                          }}
                        >
                          <Typography
                            sx={{
                              color: "#526175",
                              fontSize: "0.82rem",
                            }}
                          >
                            Loading mappings...
                          </Typography>
                        </Box>
                      </TableCell>
                    </TableRow>
                  ) : mappings.length === 0 ? (
                    <TableRow>
                      <TableCell
                        colSpan={6}
                        sx={{ borderBottom: 0 }}
                      >
                        <Box
                          sx={{
                            minHeight: 130,
                            display: "grid",
                            placeItems: "center",
                            px: 2,
                            textAlign: "center",
                          }}
                        >
                          <Box>
                            <Typography
                              sx={{
                                fontWeight: 720,
                                color: "#202939",
                                fontSize: "0.84rem",
                              }}
                            >
                              No mappings configured for this laboratory
                            </Typography>
                            <Typography
                              sx={{
                                mt: 0.25,
                                color: "#526175",
                                fontSize: "0.75rem",
                              }}
                            >
                              Add a mapping to connect an internal test
                              to the external laboratory catalogue.
                            </Typography>
                          </Box>
                        </Box>
                      </TableCell>
                    </TableRow>
                  ) : (
                    mappings.map((mapping) => (
                      <TableRow
                        key={mapping.uuid}
                        hover
                      >
                        <TableCell>
                          <Typography
                            sx={{
                              fontWeight: 650,
                              fontSize: "0.82rem",
                            }}
                          >
                            {mapping.lab_test?.name ??
                              mapping.test_name ??
                              "-"}
                          </Typography>
                        </TableCell>

                        <TableCell>
                          <Typography
                            sx={{
                              fontWeight: 620,
                              fontSize: "0.8rem",
                            }}
                          >
                            {mapping.external_test_name ?? "-"}
                          </Typography>
                          <Typography
                            sx={{
                              color: "#667085",
                              fontSize: "0.72rem",
                            }}
                          >
                            {mapping.external_test_code ?? "-"}
                          </Typography>
                        </TableCell>

                        <TableCell>
                          {mapping.currency ?? "NGN"}{" "}
                          {mapping.cost ?? "0.00"}
                        </TableCell>

                        <TableCell>
                          {mapping.turnaround_hours
                            ? `${mapping.turnaround_hours}h`
                            : "-"}
                        </TableCell>

                        <TableCell>
                          <Chip
                            size="small"
                            label={
                              mapping.is_active === false
                                ? "Inactive"
                                : "Active"
                            }
                            color={
                              mapping.is_active === false
                                ? "default"
                                : "success"
                            }
                            variant="outlined"
                          />
                        </TableCell>

                        <TableCell align="right">
                          <Stack
                            direction="row"
                            spacing={0.5}
                            sx={{
                              justifyContent: "flex-end",
                              alignItems: "center",
                            }}
                          >
                            <Button
                              size="small"
                              color={
                                mapping.is_active === false
                                  ? "success"
                                  : "inherit"
                              }
                              onClick={() => {
                                void toggleMappingStatus(
                                  mapping,
                                );
                              }}
                              disabled={saving}
                              sx={{
                                minWidth: 0,
                                px: 1,
                                whiteSpace: "nowrap",
                              }}
                            >
                              {mapping.is_active === false
                                ? "Activate"
                                : "Deactivate"}
                            </Button>

                            <Tooltip title="Edit mapping">
                              <IconButton
                                size="small"
                                onClick={() =>
                                  openEdit(mapping)
                                }
                                disabled={saving}
                              >
                                <EditOutlinedIcon fontSize="small" />
                              </IconButton>
                            </Tooltip>
                          </Stack>
                        </TableCell>
                      </TableRow>
                    ))
                  )}
                </TableBody>
                </Table>
              </TableContainer>
            </Paper>

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
          <Button onClick={onClose} disabled={saving}>
            Close
          </Button>
        </DialogActions>
      </Dialog>

      <ReferralTestMappingFormDialog
        open={formOpen}
        mapping={editingMapping}
        labTests={labTests}
        loading={saving}
        error={formError}
        onClose={() => {
          if (!saving) {
            setFormOpen(false);
            setEditingMapping(null);
            setFormError(null);
          }
        }}
        onSubmit={saveMapping}
      />
    </>
  );
}
