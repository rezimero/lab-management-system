import { Chip } from "@mui/material";

type Props = {
  dueDate?: string | null;
  status?: string;
  isOverdue?: boolean;
};

const TERMINAL_STATUSES = [
  "completed",
  "rejected",
  "cancelled",
];

const formatDueDate = (
  value: string,
): string => (
  new Date(value).toLocaleString(
    undefined,
    {
      day: "2-digit",
      month: "short",
      hour: "2-digit",
      minute: "2-digit",
    },
  )
);

export default function ReferralDueDateBadge({
  dueDate,
  status,
  isOverdue,
}: Props) {
  if (!dueDate) {
    return (
      <Chip
        size="small"
        label={
          status === "draft"
            ? "Set on dispatch"
            : "No due date"
        }
      />
    );
  }

  const terminal =
    TERMINAL_STATUSES.includes(
      status ?? "",
    );

  const computedOverdue =
    !terminal &&
    new Date(dueDate).getTime() <
      Date.now();

  const overdue =
    !terminal &&
    (
      isOverdue === true ||
      (
        isOverdue === undefined &&
        computedOverdue
      )
    );

  return (
    <Chip
      size="small"
      color={
        overdue
          ? "error"
          : "default"
      }
      variant={
        overdue
          ? "outlined"
          : "filled"
      }
      label={
        overdue
          ? "Overdue"
          : formatDueDate(
              dueDate,
            )
      }
    />
  );
}
