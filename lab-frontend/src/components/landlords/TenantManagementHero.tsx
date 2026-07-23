import AddBusinessRounded from '@mui/icons-material/AddBusinessRounded';
import VerifiedRounded from '@mui/icons-material/VerifiedRounded';

import {
  Box,
  Button,
  Card,
  CardContent,
  Chip,
  Typography,
} from '@mui/material';

interface TenantManagementHeroProps {
  onCreate: () => void;
}

const TenantManagementHero = ({
  onCreate,
}: TenantManagementHeroProps) => {
  return (
    <Card
      sx={{
        overflow: 'hidden',
        color: '#FFFFFF',
        border: 0,
        borderRadius: 5,
        background:
          'linear-gradient(135deg, #07111F 0%, #171334 50%, #0B3042 100%)',
        boxShadow:
          '0 26px 70px rgba(7,17,31,0.18)',
      }}
    >
      <CardContent
        sx={{
          display: 'grid',
          gridTemplateColumns: {
            xs: '1fr',
            lg: 'minmax(0,1fr) auto',
          },
          alignItems: 'center',
          gap: 3,
          p: {
            xs: 3,
            sm: 4,
          },
        }}
      >
        <Box>
          <Chip
            icon={<VerifiedRounded />}
            label="Landlord-controlled provisioning"
            sx={{
              mb: 2,
              color: '#DDD6FE',
              backgroundColor:
                'rgba(127,86,217,0.14)',

              '& .MuiChip-icon': {
                color: '#C4B5FD',
              },
            }}
          />

          <Typography
            component="h2"
            sx={{
              maxWidth: 760,
              fontSize: {
                xs: '1.8rem',
                sm: '2.35rem',
              },
              lineHeight: 1.08,
              fontWeight: 750,
              letterSpacing: '-0.045em',
            }}
          >
            Govern every laboratory
            tenant from one command surface.
          </Typography>

          <Typography
            sx={{
              maxWidth: 690,
              mt: 1.5,
              color:
                'rgba(255,255,255,0.57)',
              fontSize: '0.84rem',
              lineHeight: 1.7,
            }}
          >
            Provision laboratories, manage
            availability, secure administrator
            access and enter tenant maintenance
            sessions without weakening tenant
            isolation.
          </Typography>
        </Box>

        <Button
          variant="contained"
          size="large"
          startIcon={
            <AddBusinessRounded />
          }
          onClick={onCreate}
          sx={{
            minWidth: 210,
            minHeight: 54,
            color: '#FFFFFF',
            background:
              'linear-gradient(135deg, #7F56D9, #6941C6)',
            boxShadow:
              '0 14px 35px rgba(127,86,217,0.3)',
          }}
        >
          Create laboratory
        </Button>
      </CardContent>
    </Card>
  );
};

export default TenantManagementHero;
