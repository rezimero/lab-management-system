import {
  Box,
  Button,
  Chip,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Typography,
} from "@mui/material";
import AddRoundedIcon from "@mui/icons-material/AddRounded";
import ScienceOutlinedIcon from "@mui/icons-material/ScienceOutlined";
import ApartmentRoundedIcon from "@mui/icons-material/ApartmentRounded";

import type {
  ReferralLaboratory,
} from "../types/referral.types";

type Props = {
  laboratories: ReferralLaboratory[];
  canManage?: boolean;
  onSelect: (
    laboratory: ReferralLaboratory,
  ) => void;
  onCreate?: () => void;
  onEdit?: (
    laboratory: ReferralLaboratory,
  ) => void;
  onToggleStatus?: (
    laboratory: ReferralLaboratory,
  ) => void;
};

export default function ReferralLaboratoryDirectory({
  laboratories,
  canManage = false,
  onSelect,
  onCreate,
  onEdit,
  onToggleStatus,
}: Props) {
  const columnCount =
    canManage ? 5 : 4;

  return (
    <Box>
      <Box
        sx={{
          px: { xs: 1.25, sm: 2 },
          py: 1.3,
          display: "flex",
          flexDirection: {
            xs: "column",
            sm: "row",
          },
          alignItems: {
            xs: "stretch",
            sm: "center",
          },
          justifyContent:
            "space-between",
          gap: 1,
          borderBottom:
            "1px solid",
          borderColor: "divider",
        }}
      >
        <Box
          sx={{
            display: "flex",
            alignItems: "center",
            gap: 1,
            minWidth: 0,
          }}
        >
          <Box
            sx={{
              width: 30,
              height: 30,
              borderRadius: "50%",
              bgcolor: "#eaf4ff",
              color: "#1769d2",
              display: "grid",
              placeItems: "center",
              flexShrink: 0,
            }}
          >
            <ScienceOutlinedIcon
              sx={{ fontSize: 17 }}
            />
          </Box>

          <Box sx={{ minWidth: 0 }}>
            <Typography
              sx={{
                fontWeight: 730,
                fontSize: "0.87rem",
                color: "#202939",
              }}
            >
              External laboratories
            </Typography>

            <Typography
              sx={{
                mt: 0.15,
                color: "#526175",
                fontSize: "0.75rem",
              }}
            >
              Laboratories available for external referral workflows
            </Typography>
          </Box>
        </Box>

        {canManage ? (
          <Button
            size="small"
            variant="outlined"
            startIcon={
              <AddRoundedIcon />
            }
            onClick={onCreate}
            sx={{
              alignSelf: {
                xs: "flex-start",
                sm: "center",
              },
              flexShrink: 0,
              whiteSpace: "nowrap",
            }}
          >
            Add Laboratory
          </Button>
        ) : null}
      </Box>

      <TableContainer
        sx={{ minHeight: 132 }}
      >
        <Table size="small">
          <TableHead>
            <TableRow
              sx={{
                bgcolor: "#f7f9fc",
              }}
            >
              <TableCell
                sx={{
                  fontWeight: 730,
                  color: "#344054",
                }}
              >
                Name
              </TableCell>

              <TableCell
                sx={{
                  fontWeight: 730,
                  color: "#344054",
                }}
              >
                Code
              </TableCell>

              <TableCell
                sx={{
                  fontWeight: 730,
                  color: "#344054",
                }}
              >
                Location
              </TableCell>

              <TableCell
                sx={{
                  fontWeight: 730,
                  color: "#344054",
                }}
              >
                Status
              </TableCell>

              {canManage ? (
                <TableCell
                  sx={{
                    fontWeight: 730,
                    color: "#344054",
                  }}
                  align="right"
                >
                  Actions
                </TableCell>
              ) : null}
            </TableRow>
          </TableHead>

          <TableBody>
            {laboratories.length ===
            0 ? (
              <TableRow>
                <TableCell
                  colSpan={columnCount}
                  sx={{
                    borderBottom: 0,
                  }}
                >
                  <Box
                    sx={{
                      minHeight: 95,
                      px: 2,
                      py: 1.2,
                      display: "flex",
                      alignItems:
                        "center",
                      justifyContent:
                        "center",
                    }}
                  >
                    <Box
                      sx={{
                        width: "100%",
                        maxWidth: 520,
                        display: "flex",
                        alignItems:
                          "center",
                        gap: 1.2,
                        px: 1.45,
                        py: 1.05,
                        borderRadius: 2,
                        bgcolor:
                          "#f8fbff",
                        border:
                          "1px dashed #cfdae8",
                      }}
                    >
                      <Box
                        sx={{
                          width: 40,
                          height: 40,
                          borderRadius:
                            "50%",
                          bgcolor:
                            "#e9f2fb",
                          color:
                            "#7893ad",
                          display: "grid",
                          placeItems:
                            "center",
                          flexShrink: 0,
                        }}
                      >
                        <ApartmentRoundedIcon
                          sx={{
                            fontSize: 21,
                          }}
                        />
                      </Box>

                      <Box>
                        <Typography
                          sx={{
                            fontWeight:
                              720,
                            color:
                              "#202939",
                            fontSize:
                              "0.84rem",
                          }}
                        >
                          No laboratories configured
                        </Typography>

                        <Typography
                          sx={{
                            color:
                              "#526175",
                            mt: 0.22,
                            fontSize:
                              "0.74rem",
                          }}
                        >
                          {canManage
                            ? "Add a laboratory to make external referrals available."
                            : "No external referral laboratories are currently available."}
                        </Typography>
                      </Box>
                    </Box>
                  </Box>
                </TableCell>
              </TableRow>
            ) : (
              laboratories.map(
                (laboratory) => (
                  <TableRow
                    key={
                      laboratory.uuid
                    }
                    hover
                    onClick={() =>
                      onSelect(
                        laboratory,
                      )
                    }
                    sx={{
                      cursor:
                        "pointer",
                    }}
                  >
                    <TableCell>
                      <Typography
                        variant="body2"
                        sx={{
                          fontWeight:
                            650,
                        }}
                      >
                        {
                          laboratory.name
                        }
                      </Typography>
                    </TableCell>

                    <TableCell>
                      {laboratory.code ??
                        "-"}
                    </TableCell>

                    <TableCell>
                      {[
                        laboratory.city,
                        laboratory.state,
                      ]
                        .filter(Boolean)
                        .join(", ") ||
                        "-"}
                    </TableCell>

                    <TableCell>
                      <Chip
                        size="small"
                        label={
                          laboratory.is_active
                            ? "Active"
                            : "Inactive"
                        }
                        color={
                          laboratory.is_active
                            ? "success"
                            : "default"
                        }
                        variant="outlined"
                      />
                    </TableCell>

                    {canManage ? (
                      <TableCell
                        align="right"
                      >
                        <Button
                          size="small"
                          onClick={(
                            event,
                          ) => {
                            event.stopPropagation();
                            onEdit?.(
                              laboratory,
                            );
                          }}
                        >
                          Edit
                        </Button>

                        <Button
                          size="small"
                          color="inherit"
                          onClick={(
                            event,
                          ) => {
                            event.stopPropagation();
                            onToggleStatus?.(
                              laboratory,
                            );
                          }}
                        >
                          {laboratory.is_active
                            ? "Disable"
                            : "Enable"}
                        </Button>
                      </TableCell>
                    ) : null}
                  </TableRow>
                ),
              )
            )}
          </TableBody>
        </Table>
      </TableContainer>
    </Box>
  );
}
