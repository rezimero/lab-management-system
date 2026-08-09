import { Paper, Stack, Typography } from "@mui/material";

export default function ReferralWorkspaceHeader() {
  return (
    <Paper sx={{ p: 3, borderRadius: 3 }}>
      <Stack spacing={1}>
        <Typography variant="h5" sx={{ fontWeight: 700 }}>
          Referral Clinical Workspace
        </Typography>
        <Typography color="text.secondary">
          Manage laboratory referrals, turnaround time, workflow and results.
        </Typography>
      </Stack>
    </Paper>
  );
}