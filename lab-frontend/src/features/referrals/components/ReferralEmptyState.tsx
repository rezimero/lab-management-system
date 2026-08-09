import { Box, Button, Typography } from "@mui/material";
import AssignmentOutlinedIcon from "@mui/icons-material/AssignmentOutlined";
import AddRoundedIcon from "@mui/icons-material/AddRounded";

type Props = {
  title?: string;
  description?: string;
  actionLabel?: string;
  onAction?: () => void;
};

export default function ReferralEmptyState({
  title = "No referrals found",
  description = "No referrals match your current filters.",
  actionLabel = "Create New Referral",
  onAction,
}: Props) {
  return (
    <Box
      sx={{
        py: 5,
        px: 3,
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
        justifyContent: "center",
        textAlign: "center",
      }}
    >
      <Box
        sx={{
          width: 62,
          height: 62,
          borderRadius: "50%",
          bgcolor: "#edf4fb",
          color: "#9ab0c8",
          display: "grid",
          placeItems: "center",
          mb: 1.5,
        }}
      >
        <AssignmentOutlinedIcon sx={{ fontSize: 31 }} />
      </Box>

      <Typography
        variant="subtitle1"
        sx={{ fontWeight: 750 }}
      >
        {title}
      </Typography>

      <Typography
        variant="body2"
        sx={{
          color: "text.secondary",
          mt: 0.6,
          maxWidth: 420,
        }}
      >
        {description}
      </Typography>

      {onAction ? (
        <Button
          variant="outlined"
          startIcon={<AddRoundedIcon />}
          onClick={onAction}
          sx={{ mt: 2 }}
        >
          {actionLabel}
        </Button>
      ) : null}
    </Box>
  );
}
