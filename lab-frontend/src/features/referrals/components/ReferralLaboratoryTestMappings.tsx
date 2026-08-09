import {
  Box,
  Button,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Typography,
} from "@mui/material";
import AccountTreeRoundedIcon from "@mui/icons-material/AccountTreeRounded";
import SettingsOutlinedIcon from "@mui/icons-material/SettingsOutlined";
import HubOutlinedIcon from "@mui/icons-material/HubOutlined";

type Props = {
  mappings?: any[];
  canManage?: boolean;
  onManage?: () => void;
};

export default function ReferralLaboratoryTestMappings({
  mappings = [],
  canManage = false,
  onManage,
}: Props) {
  return (
    <Box>
      <Box
        sx={{
          px: {
            xs: 1.25,
            sm: 2,
          },
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
              bgcolor: "#f1ebff",
              color: "#6f42d9",
              display: "grid",
              placeItems: "center",
              flexShrink: 0,
            }}
          >
            <AccountTreeRoundedIcon
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
              Test mappings
            </Typography>

            <Typography
              sx={{
                mt: 0.15,
                color: "#526175",
                fontSize: "0.75rem",
              }}
            >
              Match internal tests with external laboratory test names
            </Typography>
          </Box>
        </Box>

        {canManage ? (
          <Button
            size="small"
            variant="outlined"
            startIcon={
              <SettingsOutlinedIcon />
            }
            onClick={onManage}
            sx={{
              alignSelf: {
                xs: "flex-start",
                sm: "center",
              },
              flexShrink: 0,
              whiteSpace: "nowrap",
              color: "#6a3fd2",
              borderColor:
                "#cdbcf5",
            }}
          >
            Manage Mappings
          </Button>
        ) : null}
      </Box>

      {mappings.length === 0 ? (
        <Box
          sx={{
            minHeight: 132,
            px: 2,
            py: 1.2,
            display: "flex",
            alignItems: "center",
            justifyContent:
              "center",
          }}
        >
          <Box
            sx={{
              width: "100%",
              maxWidth: 520,
              display: "flex",
              alignItems: "center",
              gap: 1.2,
              px: 1.45,
              py: 1.05,
              borderRadius: 2,
              bgcolor: "#fbf9ff",
              border:
                "1px dashed #ddd1f6",
            }}
          >
            <Box
              sx={{
                width: 40,
                height: 40,
                borderRadius: "50%",
                bgcolor: "#efe8ff",
                color: "#6f42d9",
                display: "grid",
                placeItems: "center",
                flexShrink: 0,
              }}
            >
              <HubOutlinedIcon
                sx={{ fontSize: 21 }}
              />
            </Box>

            <Box>
              <Typography
                sx={{
                  fontWeight: 720,
                  color: "#202939",
                  fontSize: "0.84rem",
                }}
              >
                No test mappings configured
              </Typography>

              <Typography
                sx={{
                  color: "#526175",
                  mt: 0.22,
                  fontSize: "0.74rem",
                }}
              >
                {canManage
                  ? "Configure mappings for accurate external test matching."
                  : "No external test mappings are currently configured."}
              </Typography>
            </Box>
          </Box>
        </Box>
      ) : (
        <TableContainer
          sx={{ minHeight: 132 }}
        >
          <Table size="small">
            <TableHead>
              <TableRow
                sx={{
                  bgcolor:
                    "#f7f9fc",
                }}
              >
                <TableCell
                  sx={{
                    fontWeight:
                      730,
                    color:
                      "#344054",
                  }}
                >
                  Local Test
                </TableCell>

                <TableCell
                  sx={{
                    fontWeight:
                      730,
                    color:
                      "#344054",
                  }}
                >
                  External Test
                </TableCell>

                <TableCell
                  sx={{
                    fontWeight:
                      730,
                    color:
                      "#344054",
                  }}
                >
                  Status
                </TableCell>
              </TableRow>
            </TableHead>

            <TableBody>
              {mappings.map(
                (mapping) => (
                  <TableRow
                    key={
                      mapping.uuid ??
                      mapping.id
                    }
                  >
                    <TableCell>
                      {mapping.test_name ??
                        mapping.lab_test
                          ?.name ??
                        mapping.test
                          ?.name ??
                        "-"}
                    </TableCell>

                    <TableCell>
                      {mapping.external_test_name ??
                        "-"}
                    </TableCell>

                    <TableCell>
                      {mapping.is_active ===
                      false
                        ? "Inactive"
                        : "Active"}
                    </TableCell>
                  </TableRow>
                ),
              )}
            </TableBody>
          </Table>
        </TableContainer>
      )}
    </Box>
  );
}
