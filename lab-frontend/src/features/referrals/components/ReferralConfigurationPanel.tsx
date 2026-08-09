import { useState } from "react";
import {
  Box,
  Paper,
  Tab,
  Tabs,
} from "@mui/material";

import type {
  ReferralLaboratory,
} from "../types/referral.types";

import ReferralLaboratoryDirectory from "./ReferralLaboratoryDirectory";
import ReferralLaboratoryTestMappings from "./ReferralLaboratoryTestMappings";

type Props = {
  laboratories:
    ReferralLaboratory[];
  mappings?: any[];
  canManage?: boolean;
  onSelectLaboratory: (
    laboratory:
      ReferralLaboratory,
  ) => void;
  onCreateLaboratory?: () => void;
  onEditLaboratory?: (
    laboratory:
      ReferralLaboratory,
  ) => void;
  onToggleLaboratory?: (
    laboratory:
      ReferralLaboratory,
  ) => void;
  onManageMappings?: () => void;
};

export default function ReferralConfigurationPanel({
  laboratories,
  mappings = [],
  canManage = false,
  onSelectLaboratory,
  onCreateLaboratory,
  onEditLaboratory,
  onToggleLaboratory,
  onManageMappings,
}: Props) {
  const [tab, setTab] =
    useState(0);

  return (
    <Paper
      variant="outlined"
      sx={{
        borderRadius: 2.5,
        overflow: "hidden",
        bgcolor:
          "background.paper",
      }}
    >
      <Box
        sx={{
          px: 1.5,
          borderBottom:
            "1px solid",
          borderColor: "divider",
        }}
      >
        <Tabs
          value={tab}
          onChange={(
            _,
            value,
          ) => setTab(value)}
          aria-label="Referral configuration"
          sx={{
            minHeight: 46,
            "& .MuiTab-root": {
              minHeight: 46,
              textTransform:
                "none",
              fontWeight: 680,
              fontSize:
                "0.84rem",
            },
          }}
        >
          <Tab label="Laboratory Directory" />
          <Tab label="Test Mappings" />
        </Tabs>
      </Box>

      {tab === 0 ? (
        <ReferralLaboratoryDirectory
          laboratories={
            laboratories
          }
          canManage={
            canManage
          }
          onSelect={
            onSelectLaboratory
          }
          onCreate={
            onCreateLaboratory
          }
          onEdit={
            onEditLaboratory
          }
          onToggleStatus={
            onToggleLaboratory
          }
        />
      ) : (
        <ReferralLaboratoryTestMappings
          mappings={mappings}
          canManage={
            canManage
          }
          onManage={
            onManageMappings
          }
        />
      )}
    </Paper>
  );
}
