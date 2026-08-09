import {
  Box,
  Divider,
  Typography,
} from "@mui/material";

import type {
  Referral,
} from "../types/referral.types";

type Props = {
  referral: Referral | null;
};

const humanize = (
  value: string,
) => (
  value
    .replaceAll("_", " ")
    .replace(
      /\b\w/g,
      (letter) =>
        letter.toUpperCase(),
    )
);

export default function ReferralTimeline({
  referral,
}: Props) {
  const events =
    referral?.timeline ??
    referral?.events ??
    [];

  return (
    <Box
      component="section"
      aria-label="Referral workflow timeline"
      sx={{
        display: "grid",
        gap: 1,
      }}
    >
      <Typography
        variant="h6"
        sx={{
          fontWeight: 700,
          fontSize: "0.98rem",
        }}
      >
        Workflow Timeline
      </Typography>

      <Divider />

      {!events.length ? (
        <Typography
          role="status"
          sx={{
            color: "#526175",
            fontSize: "0.82rem",
          }}
        >
          {referral
            ? "No workflow activity yet"
            : "Select a referral to view workflow history"}
        </Typography>
      ) : (
        <Box
          sx={{
            display: "grid",
            gap: 0,
          }}
        >
          {events.map(
            (
              event,
              index,
            ) => {
              const when =
                event.occurred_at ??
                event.created_at;

              const statusChange =
                event.from_status ||
                event.to_status
                  ? [
                      event.from_status,
                      event.to_status,
                    ]
                      .filter(Boolean)
                      .map((value) =>
                        humanize(
                          String(value),
                        ),
                      )
                      .join(" → ")
                  : null;

              const reason =
                typeof event.payload
                  ?.reason === "string"
                  ? event.payload.reason
                  : null;

              return (
                <Box
                  key={
                    event.uuid ??
                    index
                  }
                  sx={{
                    position:
                      "relative",
                    pl: 2.25,
                    pb:
                      index <
                      events.length - 1
                        ? 1.5
                        : 0,
                    "&::before": {
                      content: '""',
                      position:
                        "absolute",
                      left: 5,
                      top: 10,
                      bottom:
                        index <
                        events.length - 1
                          ? -3
                          : "auto",
                      height:
                        index <
                        events.length - 1
                          ? "calc(100% - 4px)"
                          : 0,
                      width: "1px",
                      bgcolor:
                        "#d7dee8",
                    },
                    "&::after": {
                      content: '""',
                      position:
                        "absolute",
                      zIndex: 1,
                      left: 1,
                      top: 5,
                      width: 9,
                      height: 9,
                      borderRadius:
                        "50%",
                      bgcolor:
                        "primary.main",
                      boxShadow:
                        "0 0 0 3px #eef4ff",
                    },
                  }}
                >
                  <Typography
                    sx={{
                      fontWeight: 700,
                      color: "#202939",
                      fontSize: "0.82rem",
                    }}
                  >
                    {humanize(
                      event.event_type ??
                        event.status ??
                        "Event",
                    )}
                  </Typography>

                  {statusChange ? (
                    <Typography
                      sx={{
                        mt: 0.15,
                        color: "#526175",
                        fontSize: "0.75rem",
                      }}
                    >
                      {statusChange}
                    </Typography>
                  ) : null}

                  {event.description ? (
                    <Typography
                      sx={{
                        mt: 0.15,
                        color: "#526175",
                        fontSize: "0.75rem",
                      }}
                    >
                      {
                        event.description
                      }
                    </Typography>
                  ) : null}

                  {reason ? (
                    <Typography
                      sx={{
                        mt: 0.15,
                        color: "#526175",
                        fontSize: "0.75rem",
                      }}
                    >
                      Reason: {reason}
                    </Typography>
                  ) : null}

                  <Typography
                    sx={{
                      color: "#667085",
                      fontSize: "0.7rem",
                      mt: 0.25,
                    }}
                  >
                    {when
                      ? new Date(
                          when,
                        ).toLocaleString()
                      : "Time not recorded"}
                    {event.actor?.name
                      ? ` · ${event.actor.name}`
                      : ""}
                  </Typography>
                </Box>
              );
            },
          )}
        </Box>
      )}
    </Box>
  );
}
