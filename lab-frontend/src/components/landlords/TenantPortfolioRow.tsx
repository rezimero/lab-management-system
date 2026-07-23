import ApartmentRounded from '@mui/icons-material/ApartmentRounded';
import EngineeringRounded from '@mui/icons-material/EngineeringRounded';
import MoreHorizRounded from '@mui/icons-material/MoreHorizRounded';

import {
  Avatar,
  Box,
  Button,
  Chip,
  IconButton,
  Paper,
  Tooltip,
  Typography,
  alpha,
} from '@mui/material';

import type {
  MouseEvent,
} from 'react';

import {
  safeNumber,
} from './tenantManagementSelectors';

import type {
  TenantRecord,
} from './tenantManagementTypes';

interface TenantPortfolioRowProps {
  tenant: TenantRecord;
  maintenancePending: boolean;
  onMaintain: (
    tenant: TenantRecord
  ) => void;
  onManage: (
    event: MouseEvent<HTMLElement>,
    tenant: TenantRecord
  ) => void;
}

const TenantPortfolioRow = ({
  tenant,
  maintenancePending,
  onMaintain,
  onManage,
}: TenantPortfolioRowProps) => {
  const statistics = [
    {
      label: 'Users',
      value: tenant.stats?.users,
    },
    {
      label: 'Patients',
      value:
        tenant.stats?.patients,
    },
    {
      label: 'Tests',
      value:
        tenant.stats?.lab_tests,
    },
    {
      label: 'Results',
      value:
        tenant.stats?.test_results,
    },
  ];

  return (
    <Paper
      variant="outlined"
      sx={{
        display: 'grid',
        gridTemplateColumns: {
          xs: '1fr',
          lg: 'minmax(260px,1.4fr) repeat(4,minmax(70px,0.35fr)) auto',
        },
        alignItems: 'center',
        gap: {
          xs: 1.5,
          lg: 1,
        },
        p: 1.75,
        borderRadius: 3.25,
        transition:
          'transform 180ms ease, border-color 180ms ease, box-shadow 180ms ease',

        '&:hover': {
          transform:
            'translateY(-2px)',
          borderColor:
            alpha(
              '#7F56D9',
              0.2
            ),
          boxShadow:
            '0 12px 30px rgba(11,18,32,0.06)',
        },
      }}
    >
      <Box
        sx={{
          minWidth: 0,
          display: 'flex',
          alignItems: 'center',
          gap: 1.35,
        }}
      >
        <Avatar
          sx={{
            width: 46,
            height: 46,
            flexShrink: 0,
            color: '#6941C6',
            background:
              'linear-gradient(145deg, rgba(127,86,217,0.13), rgba(8,145,178,0.08))',
          }}
        >
          <ApartmentRounded />
        </Avatar>

        <Box sx={{ minWidth: 0 }}>
          <Box
            sx={{
              display: 'flex',
              alignItems: 'center',
              flexWrap: 'wrap',
              gap: 0.75,
            }}
          >
            <Typography
              sx={{
                overflow:
                  'hidden',
                fontSize:
                  '0.82rem',
                fontWeight: 680,
                textOverflow:
                  'ellipsis',
                whiteSpace:
                  'nowrap',
              }}
            >
              {tenant.name}
            </Typography>

            <Chip
              label={
                tenant.is_active
                  ? 'Active'
                  : 'Suspended'
              }
              size="small"
              color={
                tenant.is_active
                  ? 'success'
                  : 'error'
              }
              sx={{
                height: 23,
                fontSize:
                  '0.59rem',
              }}
            />
          </Box>

          <Typography
            sx={{
              mt: 0.35,
              color:
                'text.secondary',
              fontSize: '0.67rem',
            }}
          >
            {tenant.domain}
          </Typography>
        </Box>
      </Box>

      {statistics.map((item) => (
        <Box
          key={item.label}
          sx={{
            textAlign: {
              xs: 'left',
              lg: 'center',
            },
          }}
        >
          <Typography
            sx={{
              fontSize: '0.82rem',
              fontWeight: 690,
            }}
          >
            {safeNumber(
              item.value
            ).toLocaleString()}
          </Typography>

          <Typography
            sx={{
              mt: 0.15,
              color:
                'text.secondary',
              fontSize: '0.58rem',
            }}
          >
            {item.label}
          </Typography>
        </Box>
      ))}

      <Box
        sx={{
          display: 'flex',
          justifyContent: {
            xs: 'flex-start',
            lg: 'flex-end',
          },
          gap: 0.75,
        }}
      >
        <Tooltip title="Enter maintenance mode">
          <span>
            <Button
              size="small"
              variant="outlined"
              disabled={
                !tenant.is_active ||
                maintenancePending
              }
              startIcon={
                <EngineeringRounded />
              }
              onClick={() => {
                onMaintain(tenant);
              }}
            >
              Maintain
            </Button>
          </span>
        </Tooltip>

        <IconButton
          aria-label={`Manage ${tenant.name}`}
          onClick={(event) => {
            onManage(
              event,
              tenant
            );
          }}
        >
          <MoreHorizRounded />
        </IconButton>
      </Box>
    </Paper>
  );
};

export default TenantPortfolioRow;
