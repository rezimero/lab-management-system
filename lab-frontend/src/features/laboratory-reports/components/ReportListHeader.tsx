import {
  Box,
  Button,
  Typography,
} from '@mui/material';

import AddRounded from
  '@mui/icons-material/AddRounded';

interface Props {
  canGenerate: boolean;
  onGenerate: () => void;
}

const ReportListHeader = ({
  canGenerate,
  onGenerate,
}: Props) => (
  <Box
    sx={{
      mb: 3,
      display: 'flex',
      flexDirection: {
        xs: 'column',
        sm: 'row',
      },
      justifyContent: 'space-between',
      alignItems: {
        xs: 'stretch',
        sm: 'center',
      },
      gap: 2,
    }}
  >
    <Box>
      <Typography
        component="h2"
        variant="h5"
        sx={{
          fontWeight: 760,
        }}
      >
        Laboratory reports
      </Typography>

      <Typography
        variant="body2"
        color="text.secondary"
      >
        View immutable reports issued
        from completed laboratory orders.
      </Typography>
    </Box>

    {canGenerate && (
      <Button
        variant="contained"
        startIcon={<AddRounded />}
        onClick={onGenerate}
      >
        Generate report
      </Button>
    )}
  </Box>
);

export default ReportListHeader;
