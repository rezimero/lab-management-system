
import { Chip, Stack } from "@mui/material";

type Props = {
  status?: string | null;
  dueDate?: string | null;
};

export default function OrderReferralStatus({
  status,
  dueDate,
}: Props) {
  if (!status) {
    return <Chip size="small" label="No Referral" />;
  }

  return (
    <Stack spacing={1}>
      <Chip
        size="small"
        label={`Referral: ${status}`}
      />

      {dueDate && (
        <Chip
          size="small"
          label={`Expected: ${new Date(dueDate).toLocaleDateString()}`}
        />
      )}
    </Stack>
  );
}
