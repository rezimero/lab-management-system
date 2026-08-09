
import { Chip } from "@mui/material";

type Props = {
 status?: string;
};

export default function ReferralStatusChip({status}: Props) {
 return (
  <Chip
   label={status ?? "Unknown"}
   size="small"
  />
 );
}
