import {
  Alert,
  Box,
  Divider,
  Typography,
} from "@mui/material";

import type {
  Referral,
} from "../types/referral.types";

type Props = {
  referral: Referral;
};

const formatDateTime = (
  value: string,
): string => (
  new Date(value).toLocaleString(
    undefined,
    {
      year: "numeric",
      month: "short",
      day: "2-digit",
      hour: "2-digit",
      minute: "2-digit",
    },
  )
);

const displayValue = (
  value: unknown,
): string => {
  if (
    value === null ||
    value === undefined ||
    value === ""
  ) {
    return "Not recorded";
  }

  return String(value);
};

const moneyLabel = (
  currency: string | null | undefined,
  value: string | number | null | undefined,
): string => {
  if (
    value === null ||
    value === undefined ||
    value === ""
  ) {
    return "Not recorded";
  }

  return `${currency ?? "NGN"} ${value}`;
};

export default function ReferralDetails({
  referral,
}: Props) {
  const test =
    referral.test ??
    referral.test_snapshot;

  const order =
    referral.order_item?.order;

  const createdBy =
    referral.created_by?.name ??
    referral.creator?.name ??
    referral.created_by_name ??
    "Not recorded";

  const localTest =
    test?.local_test_name ??
    referral.order_item?.test_name ??
    "Not recorded";

  const externalTest =
    test?.external_test_name ??
    "Not recorded";

  const externalCode =
    test?.external_test_code ??
    "Not recorded";

  const latest =
    referral.latest_external_result;

  const information = [
    {
      label: "Laboratory order",
      value:
        order?.order_number ??
        "Not recorded",
    },
    {
      label: "Ordered test",
      value: localTest,
    },
    {
      label: "External test",
      value: externalTest,
    },
    {
      label: "External test code",
      value: externalCode,
    },
    {
      label: "External reference",
      value: displayValue(
        referral.external_reference,
      ),
    },
    {
      label: "Created by",
      value: createdBy,
    },
    {
      label: "Quoted cost",
      value: moneyLabel(
        referral.currency ??
          test?.currency,
        referral.quoted_cost,
      ),
    },
    {
      label: "Final cost",
      value: moneyLabel(
        referral.currency ??
          test?.currency,
        referral.final_cost,
      ),
    },
  ];

  return (
    <Box
      component="section"
      aria-label="Referral information"
      sx={{
        display: "grid",
        gap: 1.5,
      }}
    >
      <Typography
        variant="h6"
        sx={{
          fontWeight: 700,
          fontSize: "0.98rem",
        }}
      >
        Referral Information
      </Typography>

      <Divider />

      <Box
        sx={{
          display: "grid",
          gridTemplateColumns: {
            xs: "1fr",
            sm: "repeat(2, minmax(0, 1fr))",
          },
          gap: 1.5,
        }}
      >
        {information.map(
          (item) => (
            <Box
              key={item.label}
              sx={{
                minWidth: 0,
              }}
            >
              <Typography
                sx={{
                  color: "#667085",
                  fontSize: "0.72rem",
                  fontWeight: 650,
                }}
              >
                {item.label}
              </Typography>

              <Typography
                sx={{
                  mt: 0.2,
                  color: "#202939",
                  fontSize: "0.82rem",
                  fontWeight: 650,
                  overflowWrap:
                    "anywhere",
                }}
              >
                {item.value}
              </Typography>
            </Box>
          ),
        )}
      </Box>

      {referral.dispatch_notes ? (
        <Box>
          <Typography
            sx={{
              color: "#667085",
              fontSize: "0.72rem",
              fontWeight: 650,
            }}
          >
            Dispatch notes
          </Typography>

          <Typography
            sx={{
              mt: 0.3,
              color: "#344054",
              fontSize: "0.8rem",
              lineHeight: 1.5,
              whiteSpace: "pre-wrap",
            }}
          >
            {referral.dispatch_notes}
          </Typography>
        </Box>
      ) : null}

      {referral.status ===
        "rejected" &&
      referral.rejection_reason ? (
        <Alert severity="warning">
          Rejection reason:{" "}
          {
            referral.rejection_reason
          }
        </Alert>
      ) : null}

      {referral.status ===
        "cancelled" &&
      referral.cancellation_reason ? (
        <Alert severity="info">
          Cancellation reason:{" "}
          {
            referral.cancellation_reason
          }
        </Alert>
      ) : null}

      {latest ? (
        <Box
          sx={{
            display: "grid",
            gap: 1.25,
          }}
        >
          <Divider />

          <Typography
            sx={{
              fontWeight: 700,
              color: "#202939",
              fontSize: "0.9rem",
            }}
          >
            Latest External Result
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
            <Box>
              <Typography
                sx={{
                  color: "#667085",
                  fontSize: "0.72rem",
                  fontWeight: 650,
                }}
              >
                Version / report
              </Typography>
              <Typography
                sx={{
                  mt: 0.2,
                  color: "#202939",
                  fontSize: "0.8rem",
                }}
              >
                Version {latest.version}
                {" · "}
                {latest.external_report_number ??
                  "Report number not recorded"}
              </Typography>
            </Box>

            <Box>
              <Typography
                sx={{
                  color: "#667085",
                  fontSize: "0.72rem",
                  fontWeight: 650,
                }}
              >
                Received / standard result
              </Typography>
              <Typography
                sx={{
                  mt: 0.2,
                  color: "#202939",
                  fontSize: "0.8rem",
                }}
              >
                {latest.received_at
                  ? formatDateTime(
                      latest.received_at,
                    )
                  : "Not recorded"}
                {" · "}
                {referral.standard_result
                  ?.status ??
                  "Standard result not created"}
              </Typography>
            </Box>
          </Box>
        </Box>
      ) : null}
    </Box>
  );
}
