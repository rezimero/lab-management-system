import { Box, Typography } from '@mui/material';
import Layout from '../components/Layout/Layout';
import ReferralWorkspace from '../features/referrals/components/ReferralWorkspace';

export default function ReferralLaboratories() {
  return (
    <Layout title="Referrals">
      <Box sx={{ mb: 1.75 }}>
        <Typography
          variant="h5"
          sx={{
            fontWeight: 700,
            lineHeight: 1.2,
          }}
        >
          Referrals
        </Typography>

        <Typography
          variant="body2"
          sx={{
            mt: 0.45,
            color: '#536176',
            fontSize: '0.875rem',
            lineHeight: 1.5,
          }}
        >
          Track tests sent to external laboratories. Laboratory Orders manage
          internal requests; Referrals manage external dispatch,
          acknowledgement and result return.
        </Typography>
      </Box>

      <ReferralWorkspace />
    </Layout>
  );
}
