
import { Stack } from "@mui/material";
import ReferralSummaryCards from "./ReferralSummaryCards";

type Props = {
 summary?: any;
 children?: React.ReactNode;
};

export default function ReferralWorkspaceSections({
 summary,
 children,
}: Props) {
 return (
  <Stack spacing={3}>
   <ReferralSummaryCards summary={summary} />
   {children}
  </Stack>
 );
}
