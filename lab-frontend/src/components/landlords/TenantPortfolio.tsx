import ApartmentRounded from '@mui/icons-material/ApartmentRounded';
import RefreshRounded from '@mui/icons-material/RefreshRounded';
import SearchRounded from '@mui/icons-material/SearchRounded';

import {
  Box,
  Card,
  CardContent,
  CircularProgress,
  IconButton,
  InputAdornment,
  TextField,
  Tooltip,
  Typography,
} from '@mui/material';

import type {
  MouseEvent,
} from 'react';

import TenantPortfolioRow from './TenantPortfolioRow';

import type {
  TenantRecord,
} from './tenantManagementTypes';

interface TenantPortfolioProps {
  search: string;
  tenants: TenantRecord[];
  isFetching: boolean;
  maintenancePending: boolean;
  onSearchChange: (
    value: string
  ) => void;
  onRefresh: () => void;
  onMaintain: (
    tenant: TenantRecord
  ) => void;
  onManage: (
    event: MouseEvent<HTMLElement>,
    tenant: TenantRecord
  ) => void;
}

const TenantPortfolio = ({
  search,
  tenants,
  isFetching,
  maintenancePending,
  onSearchChange,
  onRefresh,
  onMaintain,
  onManage,
}: TenantPortfolioProps) => {
  return (
    <Card>
      <CardContent>
        <Box
          sx={{
            display: 'flex',
            alignItems: {
              xs: 'stretch',
              sm: 'center',
            },
            justifyContent:
              'space-between',
            flexDirection: {
              xs: 'column',
              sm: 'row',
            },
            gap: 2,
            mb: 2.5,
          }}
        >
          <Box>
            <Typography
              variant="overline"
              sx={{
                color: '#6941C6',
              }}
            >
              Tenant portfolio
            </Typography>

            <Typography
              component="h3"
              sx={{
                mt: 0.45,
                fontSize: '1.3rem',
                fontWeight: 710,
                letterSpacing: '-0.025em',
              }}
            >
              Managed laboratories
            </Typography>
          </Box>

          <Box
            sx={{
              display: 'flex',
              alignItems: 'center',
              gap: 1,
            }}
          >
            <TextField
              size="small"
              placeholder="Search tenants"
              value={search}
              onChange={(event) => {
                onSearchChange(
                  event.target.value
                );
              }}
              slotProps={{
                input: {
                  startAdornment: (
                    <InputAdornment position="start">
                      <SearchRounded />
                    </InputAdornment>
                  ),
                },
              }}
            />

            <Tooltip title="Refresh tenants">
              <IconButton
                disabled={isFetching}
                onClick={onRefresh}
              >
                {isFetching ? (
                  <CircularProgress
                    size={20}
                  />
                ) : (
                  <RefreshRounded />
                )}
              </IconButton>
            </Tooltip>
          </Box>
        </Box>

        {tenants.length === 0 ? (
          <Box
            sx={{
              minHeight: 290,
              display: 'grid',
              placeItems: 'center',
              textAlign: 'center',
            }}
          >
            <Box>
              <ApartmentRounded
                sx={{
                  color: 'text.disabled',
                  fontSize: 48,
                }}
              />

              <Typography
                sx={{
                  mt: 1.5,
                  fontWeight: 680,
                }}
              >
                No laboratories found
              </Typography>

              <Typography
                sx={{
                  mt: 0.5,
                  color: 'text.secondary',
                  fontSize: '0.75rem',
                }}
              >
                Create a new laboratory or
                revise the search term.
              </Typography>
            </Box>
          </Box>
        ) : (
          <Box
            sx={{
              display: 'grid',
              gap: 1.25,
            }}
          >
            {tenants.map((tenant) => (
              <TenantPortfolioRow
                key={tenant.id}
                tenant={tenant}
                maintenancePending={
                  maintenancePending
                }
                onMaintain={onMaintain}
                onManage={onManage}
              />
            ))}
          </Box>
        )}
      </CardContent>
    </Card>
  );
};

export default TenantPortfolio;
