import ApartmentRounded from '@mui/icons-material/ApartmentRounded';
import AssignmentTurnedInRounded from '@mui/icons-material/AssignmentTurnedInRounded';
import BiotechRounded from '@mui/icons-material/BiotechRounded';
import PeopleAltRounded from '@mui/icons-material/PeopleAltRounded';
import PowerSettingsNewRounded from '@mui/icons-material/PowerSettingsNewRounded';

import {
  Box,
  Paper,
  Typography,
  alpha,
} from '@mui/material';

import type {
  TenantTotals,
} from './tenantManagementTypes';

interface TenantSummaryCardsProps {
  tenantCount: number;
  activeTenants: number;
  totals: TenantTotals;
}

const TenantSummaryCards = ({
  tenantCount,
  activeTenants,
  totals,
}: TenantSummaryCardsProps) => {
  const metrics = [
    {
      label: 'Tenants',
      value: tenantCount,
      icon: <ApartmentRounded />,
      accent: '#7F56D9',
    },
    {
      label: 'Active',
      value: activeTenants,
      icon:
        <PowerSettingsNewRounded />,
      accent: '#079455',
    },
    {
      label: 'Users',
      value: totals.users,
      icon: <PeopleAltRounded />,
      accent: '#246BFD',
    },
    {
      label: 'Tests',
      value: totals.tests,
      icon: <BiotechRounded />,
      accent: '#0891B2',
    },
    {
      label: 'Results',
      value: totals.results,
      icon:
        <AssignmentTurnedInRounded />,
      accent: '#DC6803',
    },
  ];

  return (
    <Box
      sx={{
        display: 'grid',
        gridTemplateColumns: {
          xs: 'repeat(2, minmax(0,1fr))',
          xl: 'repeat(5, minmax(0,1fr))',
        },
        gap: 1.5,
      }}
    >
      {metrics.map((metric) => (
        <Paper
          key={metric.label}
          sx={{
            display: 'flex',
            alignItems: 'center',
            gap: 1.25,
            p: 1.75,
            borderRadius: 3.25,
          }}
        >
          <Box
            sx={{
              width: 39,
              height: 39,
              display: 'grid',
              placeItems: 'center',
              flexShrink: 0,
              borderRadius: 2.5,
              color: metric.accent,
              backgroundColor:
                alpha(
                  metric.accent,
                  0.075
                ),

              '& svg': {
                fontSize: 20,
              },
            }}
          >
            {metric.icon}
          </Box>

          <Box>
            <Typography
              sx={{
                fontSize: '1.25rem',
                lineHeight: 1,
                fontWeight: 730,
              }}
            >
              {metric.value.toLocaleString()}
            </Typography>

            <Typography
              sx={{
                mt: 0.4,
                color: 'text.secondary',
                fontSize: '0.62rem',
              }}
            >
              {metric.label}
            </Typography>
          </Box>
        </Paper>
      ))}
    </Box>
  );
};

export default TenantSummaryCards;
