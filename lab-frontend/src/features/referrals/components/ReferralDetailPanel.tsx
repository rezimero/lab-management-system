import { Box } from "@mui/material";

import ReferralDetails from "./ReferralDetails";
import ReferralIdentityCards from "./ReferralIdentityCards";
import ReferralStatusPanel from "./ReferralStatusPanel";

import type {
  Referral,
} from "../types/referral.types";

type Props = {
  referral: Referral | null;
};

export default function ReferralDetailPanel({
  referral,
}: Props) {
  if (!referral) {
    return null;
  }

  return (
    <Box
      sx={{
        display: "grid",
        gap: 2,
      }}
    >
      <ReferralIdentityCards
        referral={referral}
      />

      <ReferralStatusPanel
        referral={referral}
      />

      <ReferralDetails
        referral={referral}
      />
    </Box>
  );
}
