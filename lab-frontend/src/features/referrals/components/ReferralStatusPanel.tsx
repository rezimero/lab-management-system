import {
  Box,
  Paper,
  Typography,
} from "@mui/material";

import type {
  Referral,
} from "../types/referral.types";

import ReferralDueDateBadge from "./ReferralDueDateBadge";
import ReferralStatusChip from "./ReferralStatusChip";

type Props = {
  referral: Referral;
};

export default function ReferralStatusPanel({
  referral,
}: Props) {
  return (
    <Paper
      component="section"
      aria-label="Referral workflow status"
      variant="outlined"
      sx={{
        p: 1.5,
        borderRadius: 2,
        boxShadow: "none",
        bgcolor: "#fbfcfe",
      }}
    >
      <Box
        sx={{
          display: "flex",
          flexDirection: {
            xs: "column",
            sm: "row",
          },
          alignItems: {
            xs: "flex-start",
            sm: "center",
          },
          justifyContent:
            "space-between",
          gap: 1.25,
        }}
      >
        <Box>
          <Typography
            sx={{
              fontWeight: 700,
              color: "#202939",
              fontSize: "0.9rem",
            }}
          >
            Workflow Status
          </Typography>

          <Typography
            sx={{
              mt: 0.2,
              color: "#526175",
              fontSize: "0.74rem",
            }}
          >
            Current referral state and turnaround deadline.
          </Typography>
        </Box>

        <Box
          sx={{
            display: "flex",
            alignItems: "center",
            flexWrap: "wrap",
            gap: 0.75,
          }}
        >
          <ReferralStatusChip
            status={referral.status}
          />

          <ReferralDueDateBadge
            dueDate={
              referral.turnaround_due_at
            }
            status={referral.status}
            isOverdue={
              referral.is_overdue
            }
          />
        </Box>
      </Box>

      {referral.is_overdue ? (
        <Typography
          role="status"
          sx={{
            mt: 1,
            color: "error.dark",
            fontSize: "0.76rem",
            fontWeight: 700,
          }}
        >
          This referral has passed its turnaround deadline.
        </Typography>
      ) : null}
    </Paper>
  );
}
