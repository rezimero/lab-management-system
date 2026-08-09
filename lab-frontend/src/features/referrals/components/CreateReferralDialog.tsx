import {
  useEffect,
  useMemo,
  useState,
} from "react";

import {
  Alert,
  Box,
  Button,
  Checkbox,
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

import type {
  CreateReferralPayload,
  ReferralCreateMappingOption,
  ReferralOrderItemOption,
} from "../types/referral.types";

type Props = {
  open: boolean;
  orderItems: ReferralOrderItemOption[];
  mappings: ReferralCreateMappingOption[];
  loading?: boolean;
  optionsLoading?: boolean;
  mappingsLoading?: boolean;
  error?: string | null;
  onOrderItemChange: (
    orderItemUuid: string,
  ) => Promise<unknown> | unknown;
  onClose: () => void;
  onSubmit: (
    payload: CreateReferralPayload,
  ) => Promise<unknown> | unknown;
};

export default function CreateReferralDialog({
  open,
  orderItems,
  mappings,
  loading = false,
  optionsLoading = false,
  mappingsLoading = false,
  error,
  onOrderItemChange,
  onClose,
  onSubmit,
}: Props) {
  const [
    orderItemUuid,
    setOrderItemUuid,
  ] = useState("");

  const [
    mappingUuid,
    setMappingUuid,
  ] = useState("");

  const [
    selectedSpecimenUuids,
    setSelectedSpecimenUuids,
  ] = useState<string[]>([]);

  const [
    quotedCost,
    setQuotedCost,
  ] = useState("");

  const [
    externalReference,
    setExternalReference,
  ] = useState("");

  const [
    dispatchNotes,
    setDispatchNotes,
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

    setOrderItemUuid("");
    setMappingUuid("");
    setSelectedSpecimenUuids([]);
    setQuotedCost("");
    setExternalReference("");
    setDispatchNotes("");
    setLocalError(null);
  }, [open]);

  const selectedItem =
    useMemo(
      () =>
        orderItems.find(
          (item) =>
            item.uuid ===
            orderItemUuid,
        ) ?? null,
      [
        orderItems,
        orderItemUuid,
      ],
    );

  const selectedMapping =
    useMemo(
      () =>
        mappings.find(
          (mapping) =>
            mapping.uuid ===
            mappingUuid,
        ) ?? null,
      [
        mappings,
        mappingUuid,
      ],
    );

  useEffect(() => {
    if (!selectedItem) {
      setSelectedSpecimenUuids([]);
      return;
    }

    const available =
      selectedItem.specimens.map(
        (specimen) =>
          specimen.uuid,
      );

    setSelectedSpecimenUuids(
      (current) => {
        const retained =
          current.filter(
            (uuid) =>
              available.includes(
                uuid,
              ),
          );

        return retained.length > 0
          ? retained
          : available;
      },
    );
  }, [selectedItem]);

  useEffect(() => {
    if (
      mappings.length === 1
    ) {
      setMappingUuid(
        mappings[0].uuid,
      );
      return;
    }

    if (
      mappingUuid &&
      !mappings.some(
        (mapping) =>
          mapping.uuid ===
          mappingUuid,
      )
    ) {
      setMappingUuid("");
    }
  }, [
    mappings,
    mappingUuid,
  ]);

  const changeOrderItem = (
    nextUuid: string,
  ) => {
    setOrderItemUuid(nextUuid);
    setMappingUuid("");
    setSelectedSpecimenUuids([]);
    setQuotedCost("");
    setLocalError(null);

    void onOrderItemChange(
      nextUuid,
    );
  };

  const toggleSpecimen = (
    uuid: string,
  ) => {
    setSelectedSpecimenUuids(
      (current) =>
        current.includes(uuid)
          ? current.filter(
              (value) =>
                value !== uuid,
            )
          : [
              ...current,
              uuid,
            ],
    );
  };

  const submit = async () => {
    if (!orderItemUuid) {
      setLocalError(
        "Select a referral-ready laboratory order item.",
      );
      return;
    }

    if (!mappingUuid) {
      setLocalError(
        "Select an external referral test mapping.",
      );
      return;
    }

    if (
      selectedSpecimenUuids.length ===
      0
    ) {
      setLocalError(
        "Select at least one received specimen.",
      );
      return;
    }

    if (
      quotedCost.trim() !== ""
    ) {
      const numericCost =
        Number(quotedCost);

      if (
        !Number.isFinite(
          numericCost,
        ) ||
        numericCost < 0
      ) {
        setLocalError(
          "Quoted cost must be zero or a positive number.",
        );
        return;
      }
    }

    setLocalError(null);

    const payload:
      CreateReferralPayload = {
        order_item_uuid:
          orderItemUuid,
        referral_laboratory_test_uuid:
          mappingUuid,
        specimen_uuids:
          selectedSpecimenUuids,
      };

    if (
      quotedCost.trim() !== ""
    ) {
      payload.quoted_cost =
        Number(
          quotedCost,
        ).toFixed(2);
    }

    if (
      externalReference
        .trim() !== ""
    ) {
      payload.external_reference =
        externalReference.trim();
    }

    if (
      dispatchNotes.trim() !==
      ""
    ) {
      payload.dispatch_notes =
        dispatchNotes.trim();
    }

    try {
      await onSubmit(payload);
      onClose();
    } catch {
      // Parent exposes sanitized
      // API feedback.
    }
  };

  const busy =
    loading ||
    optionsLoading ||
    mappingsLoading;

  return (
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
        Create Referral
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
            Create a referral from an
            in-progress order item,
            choose its configured
            external test mapping, and
            attach received specimens.
          </Typography>

          {localError || error ? (
            <Alert severity="error">
              {localError ?? error}
            </Alert>
          ) : null}

          <TextField
            select
            label="Referral-ready order item"
            value={orderItemUuid}
            onChange={(event) =>
              changeOrderItem(
                event.target.value,
              )
            }
            disabled={
              optionsLoading ||
              loading
            }
            fullWidth
          >
            {orderItems.length ===
            0 ? (
              <MenuItem
                value=""
                disabled
              >
                {optionsLoading
                  ? "Loading eligible order items..."
                  : "No in-progress order items are currently eligible for referral"}
              </MenuItem>
            ) : (
              orderItems.map(
                (item) => (
                  <MenuItem
                    key={item.uuid}
                    value={item.uuid}
                  >
                    {
                      item.order_number
                    }{" "}
                    ·{" "}
                    {
                      item.patient_name
                    }{" "}
                    ·{" "}
                    {
                      item.test_name
                    }
                  </MenuItem>
                ),
              )
            )}
          </TextField>

          {selectedItem ? (
            <Paper
              variant="outlined"
              sx={{
                p: 1.25,
                borderRadius: 2,
                bgcolor: "#f8fafc",
              }}
            >
              <Typography
                sx={{
                  fontWeight: 720,
                  color: "#202939",
                  fontSize: "0.8rem",
                }}
              >
                {selectedItem.test_name}
              </Typography>

              <Typography
                sx={{
                  mt: 0.25,
                  color: "#526175",
                  fontSize: "0.74rem",
                }}
              >
                {selectedItem.patient_name}
                {selectedItem
                  .medical_record_number
                  ? ` · MRN ${selectedItem.medical_record_number}`
                  : ""}
                {" · "}
                Order {
                  selectedItem.order_number
                }
              </Typography>
            </Paper>
          ) : null}

          <TextField
            select
            label="External referral test"
            value={mappingUuid}
            onChange={(event) => {
              setMappingUuid(
                event.target.value,
              );
              setQuotedCost("");
              setLocalError(null);
            }}
            disabled={
              !orderItemUuid ||
              mappingsLoading ||
              loading
            }
            fullWidth
          >
            {mappings.length ===
            0 ? (
              <MenuItem
                value=""
                disabled
              >
                {mappingsLoading
                  ? "Loading matching external tests..."
                  : orderItemUuid
                    ? "No active referral mapping is configured for this test"
                    : "Select an order item first"}
              </MenuItem>
            ) : (
              mappings.map(
                (mapping) => (
                  <MenuItem
                    key={mapping.uuid}
                    value={
                      mapping.uuid
                    }
                  >
                    {
                      mapping.laboratory_name
                    }
                    {" · "}
                    {mapping.external_test_name ??
                      mapping.external_test_code ??
                      "Mapped test"}
                  </MenuItem>
                ),
              )
            )}
          </TextField>

          {selectedMapping ? (
            <Paper
              variant="outlined"
              sx={{
                p: 1.25,
                borderRadius: 2,
                bgcolor: "#fbfdff",
              }}
            >
              <Box
                sx={{
                  display: "grid",
                  gridTemplateColumns: {
                    xs: "1fr",
                    sm: "repeat(3, minmax(0, 1fr))",
                  },
                  gap: 1,
                }}
              >
                <Box>
                  <Typography
                    sx={{
                      color: "#667085",
                      fontSize: "0.7rem",
                    }}
                  >
                    Laboratory
                  </Typography>
                  <Typography
                    sx={{
                      fontWeight: 680,
                      fontSize: "0.8rem",
                    }}
                  >
                    {
                      selectedMapping.laboratory_name
                    }
                  </Typography>
                </Box>

                <Box>
                  <Typography
                    sx={{
                      color: "#667085",
                      fontSize: "0.7rem",
                    }}
                  >
                    External code
                  </Typography>
                  <Typography
                    sx={{
                      fontWeight: 680,
                      fontSize: "0.8rem",
                    }}
                  >
                    {selectedMapping.external_test_code ??
                      "-"}
                  </Typography>
                </Box>

                <Box>
                  <Typography
                    sx={{
                      color: "#667085",
                      fontSize: "0.7rem",
                    }}
                  >
                    Cost / TAT
                  </Typography>
                  <Typography
                    sx={{
                      fontWeight: 680,
                      fontSize: "0.8rem",
                    }}
                  >
                    {selectedMapping.currency ??
                      "NGN"}{" "}
                    {selectedMapping.cost ??
                      "0.00"}
                    {selectedMapping
                      .turnaround_hours
                      ? ` · ${selectedMapping.turnaround_hours}h`
                      : ""}
                  </Typography>
                </Box>
              </Box>

              {selectedMapping
                .specimen_requirements ? (
                <Typography
                  sx={{
                    mt: 1,
                    color: "#526175",
                    fontSize: "0.73rem",
                    lineHeight: 1.45,
                  }}
                >
                  Specimen requirements:{" "}
                  {[
                    selectedMapping
                      .specimen_requirements
                      .specimen_type,
                    selectedMapping
                      .specimen_requirements
                      .container,
                    selectedMapping
                      .specimen_requirements
                      .minimum_volume,
                    selectedMapping
                      .specimen_requirements
                      .instructions,
                  ]
                    .filter(Boolean)
                    .join(" · ") ||
                    "No additional requirements recorded"}
                </Typography>
              ) : null}
            </Paper>
          ) : null}

          <Box>
            <Typography
              sx={{
                mb: 0.75,
                fontWeight: 720,
                color: "#344054",
                fontSize: "0.78rem",
              }}
            >
              Received specimens
            </Typography>

            {!selectedItem ? (
              <Typography
                sx={{
                  color: "#667085",
                  fontSize: "0.76rem",
                }}
              >
                Select an order item to
                view eligible specimens.
              </Typography>
            ) : selectedItem
                .specimens.length ===
              0 ? (
              <Alert severity="warning">
                No received specimen is
                linked to this order
                item. A referral cannot
                be created until at
                least one linked
                specimen has been
                received.
              </Alert>
            ) : (
              <Paper
                variant="outlined"
                sx={{
                  borderRadius: 2,
                  overflow: "hidden",
                }}
              >
                {selectedItem.specimens.map(
                  (
                    specimen,
                    index,
                  ) => (
                    <Box
                      key={
                        specimen.uuid
                      }
                      onClick={() =>
                        toggleSpecimen(
                          specimen.uuid,
                        )
                      }
                      sx={{
                        px: 1.2,
                        py: 0.8,
                        display: "flex",
                        gap: 1,
                        alignItems:
                          "center",
                        cursor:
                          loading
                            ? "default"
                            : "pointer",
                        borderBottom:
                          index <
                          selectedItem
                            .specimens
                            .length -
                            1
                            ? "1px solid"
                            : "none",
                        borderColor:
                          "divider",
                        "&:hover": {
                          bgcolor:
                            loading
                              ? "transparent"
                              : "#f8fafc",
                        },
                      }}
                    >
                      <Checkbox
                        size="small"
                        checked={selectedSpecimenUuids.includes(
                          specimen.uuid,
                        )}
                        onChange={() =>
                          toggleSpecimen(
                            specimen.uuid,
                          )
                        }
                        onClick={(
                          event,
                        ) =>
                          event.stopPropagation()
                        }
                        disabled={
                          loading
                        }
                      />

                      <Box>
                        <Typography
                          sx={{
                            fontWeight: 670,
                            color: "#202939",
                            fontSize: "0.79rem",
                          }}
                        >
                          {
                            specimen.accession_number
                          }
                        </Typography>

                        <Typography
                          sx={{
                            color: "#526175",
                            fontSize: "0.72rem",
                          }}
                        >
                          {
                            specimen.specimen_type
                          }
                          {specimen.container_type
                            ? ` · ${specimen.container_type}`
                            : ""}
                          {" · Received"}
                        </Typography>
                      </Box>
                    </Box>
                  ),
                )}
              </Paper>
            )}
          </Box>

          <Typography
            sx={{
              pt: 0.25,
              fontWeight: 720,
              color: "#344054",
              fontSize: "0.78rem",
            }}
          >
            Optional referral details
          </Typography>

          <Box
            sx={{
              display: "grid",
              gridTemplateColumns: {
                xs: "1fr",
                sm: "repeat(2, minmax(0, 1fr))",
              },
              gap: 1.25,
            }}
          >
            <TextField
              label="Quoted cost override"
              type="number"
              value={quotedCost}
              onChange={(event) =>
                setQuotedCost(
                  event.target.value,
                )
              }
              disabled={loading}
              helperText={
                selectedMapping
                  ? `Leave blank to use ${selectedMapping.currency ?? "NGN"} ${selectedMapping.cost ?? "0.00"}`
                  : "Leave blank to use the mapping cost"
              }
              fullWidth
            />

            <TextField
              label="External reference"
              value={
                externalReference
              }
              onChange={(event) =>
                setExternalReference(
                  event.target.value,
                )
              }
              disabled={loading}
              fullWidth
            />
          </Box>

          <TextField
            label="Dispatch notes"
            value={dispatchNotes}
            onChange={(event) =>
              setDispatchNotes(
                event.target.value,
              )
            }
            disabled={loading}
            multiline
            minRows={2}
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
            busy ||
            !orderItemUuid ||
            !mappingUuid ||
            selectedSpecimenUuids.length ===
              0
          }
        >
          {loading
            ? "Creating..."
            : "Create Referral"}
        </Button>
      </DialogActions>
    </Dialog>
  );
}
