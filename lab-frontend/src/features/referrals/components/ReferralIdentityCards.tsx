import {
  Box,
  Typography,
} from "@mui/material";

import type {
  Referral,
} from "../types/referral.types";

type Props = {
  referral: Referral;
};

export default function ReferralIdentityCards({
  referral,
}: Props) {
  const patient =
    referral.order_item?.order?.patient;

  const patientName =
    patient?.full_name ??
    (
      [
        patient?.first_name,
        patient?.last_name,
      ]
        .filter(Boolean)
        .join(" ") ||
      "-"
    );

  const laboratoryName =
    referral.laboratory?.name ??
    referral.referral_laboratory?.name ??
    referral.laboratory_snapshot?.name ??
    "-";

  const items = [
    {
      label: "Patient",
      value: patientName,
      supporting:
        patient?.medical_record_number,
    },
    {
      label: "Referral",
      value:
        referral.referral_number ??
        "-",
      supporting:
        referral.order_item?.order
          ?.order_number,
    },
    {
      label: "Laboratory",
      value: laboratoryName,
      supporting:
        referral.laboratory?.code ??
        referral.referral_laboratory
          ?.code ??
        referral.laboratory_snapshot
          ?.code,
    },
  ];

  return (
    <Box
      component="section"
      aria-label="Referral identity"
      sx={{
        display: "grid",
        gridTemplateColumns: {
          xs: "1fr",
          sm: "repeat(3, minmax(0, 1fr))",
        },
        gap: 1,
      }}
    >
      {items.map(
        (item) => (
          <Box
            key={item.label}
            sx={{
              minWidth: 0,
              p: 1.5,
              borderRadius: 2,
              border: "1px solid",
              borderColor: "divider",
              bgcolor: "#fbfcfe",
            }}
          >
            <Typography
              sx={{
                color: "#667085",
                fontSize: "0.72rem",
                fontWeight: 700,
              }}
            >
              {item.label}
            </Typography>

            <Typography
              sx={{
                mt: 0.3,
                color: "#202939",
                fontSize: "0.86rem",
                fontWeight: 700,
                overflowWrap:
                  "anywhere",
              }}
            >
              {item.value}
            </Typography>

            {item.supporting ? (
              <Typography
                sx={{
                  mt: 0.2,
                  color: "#526175",
                  fontSize: "0.7rem",
                  overflowWrap:
                    "anywhere",
                }}
              >
                {item.supporting}
              </Typography>
            ) : null}
          </Box>
        ),
      )}
    </Box>
  );
}
