import type { ReactNode } from "react";
import { Box, Paper, Typography } from "@mui/material";
import AssignmentOutlinedIcon from "@mui/icons-material/AssignmentOutlined";
import DescriptionOutlinedIcon from "@mui/icons-material/DescriptionOutlined";
import SendRoundedIcon from "@mui/icons-material/SendRounded";
import HowToRegRoundedIcon from "@mui/icons-material/HowToRegRounded";
import HourglassBottomRoundedIcon from "@mui/icons-material/HourglassBottomRounded";
import CheckCircleRoundedIcon from "@mui/icons-material/CheckCircleRounded";

type Props = { summary: any };

type SummaryCard = {
  key: string;
  label: string;
  caption: string;
  value: number;
  icon: ReactNode;
  iconColor: string;
  iconBackground: string;
  accent: string;
  tint: string;
};

export default function ReferralSummaryCards({ summary }: Props) {
  const cards: SummaryCard[] = [
    { key: "total", label: "Total Referrals", caption: "All time referrals", value: summary?.total ?? 0, icon: <AssignmentOutlinedIcon sx={{ fontSize: 21 }} />, iconColor: "#1769d2", iconBackground: "#e8f3ff", accent: "#2f80ed", tint: "#fbfdff" },
    { key: "draft", label: "Draft", caption: "Not dispatched", value: summary?.draft ?? 0, icon: <DescriptionOutlinedIcon sx={{ fontSize: 21 }} />, iconColor: "#6f42d9", iconBackground: "#f0eaff", accent: "#8b5cf6", tint: "#fdfcff" },
    { key: "dispatched", label: "Dispatched", caption: "Sent to laboratories", value: summary?.dispatched ?? 0, icon: <SendRoundedIcon sx={{ fontSize: 20 }} />, iconColor: "#1685cf", iconBackground: "#e7f4ff", accent: "#38a3e6", tint: "#fbfdff" },
    { key: "acknowledged", label: "Acknowledged", caption: "Confirmed by labs", value: summary?.acknowledged ?? 0, icon: <HowToRegRoundedIcon sx={{ fontSize: 21 }} />, iconColor: "#b56b00", iconBackground: "#fff4d9", accent: "#e7a11a", tint: "#fffdf8" },
    { key: "awaiting-result", label: "Awaiting Result", caption: "Results pending", value: summary?.awaiting_result ?? 0, icon: <HourglassBottomRoundedIcon sx={{ fontSize: 20 }} />, iconColor: "#cf6516", iconBackground: "#fff0e5", accent: "#ef7b2d", tint: "#fffaf7" },
    { key: "completed", label: "Completed", caption: "Workflow complete", value: summary?.completed ?? 0, icon: <CheckCircleRoundedIcon sx={{ fontSize: 21 }} />, iconColor: "#087a50", iconBackground: "#e1f7ee", accent: "#18a66f", tint: "#f8fdfb" },
  ];

  return (
    <Box
      component="section"
      aria-label="Referral summary"
      sx={{
        display: "grid",
        gridTemplateColumns: {
          xs: "repeat(2, minmax(0, 1fr))",
          md: "repeat(3, minmax(0, 1fr))",
        },
        "@media (max-width: 429.95px)": {
          gridTemplateColumns: "1fr",
        },
        "@media (min-width: 1920px)": {
          gridTemplateColumns: "repeat(6, minmax(0, 1fr))",
        },
        gap: { xs: 0.9, sm: 1.1 },
      }}
    >
      {cards.map((card) => (
        <Paper
          key={card.key}
          component="article"
          aria-label={`${card.label}: ${card.value}`}
          variant="outlined"
          sx={{
            minHeight: { xs: 78, sm: 86 },
            px: { xs: 1.1, sm: 1.4 },
            py: 1.15,
            borderRadius: 2.25,
            display: "flex",
            alignItems: "center",
            gap: 1.1,
            bgcolor: card.tint,
            borderColor: "#dde3eb",
            position: "relative",
            overflow: "hidden",
            boxShadow: "0 2px 7px rgba(15, 23, 42, 0.025)",
            "&::before": {
              content: '""',
              position: "absolute",
              left: 0,
              top: 0,
              bottom: 0,
              width: 3,
              bgcolor: card.accent,
            },
          }}
        >
          <Box
            sx={{
              width: { xs: 34, sm: 38 },
              height: { xs: 34, sm: 38 },
              borderRadius: "50%",
              bgcolor: card.iconBackground,
              color: card.iconColor,
              display: "grid",
              placeItems: "center",
              flexShrink: 0,
            }}
          >
            {card.icon}
          </Box>

          <Box sx={{ minWidth: 0 }}>
            <Typography sx={{ fontWeight: 740, color: "#1f2937", fontSize: { xs: "0.72rem", sm: "0.78rem" }, lineHeight: 1.15 }}>
              {card.label}
            </Typography>
            <Typography sx={{ fontWeight: 820, mt: 0.2, color: "#111827", fontSize: { xs: "0.98rem", sm: "1.05rem" }, lineHeight: 1 }}>
              {card.value}
            </Typography>
            <Typography sx={{ display: { xs: "none", sm: "block" }, mt: 0.5, color: "#526175", lineHeight: 1.2, fontSize: "0.72rem" }}>
              {card.caption}
            </Typography>
          </Box>
        </Paper>
      ))}
    </Box>
  );
}
