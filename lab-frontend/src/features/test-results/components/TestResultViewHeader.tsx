import {
  Box,
  Button,
  IconButton,
  Typography,
} from '@mui/material';

import {
  ArrowBack as ArrowBackIcon,
  Print as PrintIcon,
} from '@mui/icons-material';

interface Props {
  onBack: () => void;
  onPrint: () => void;
}

const TestResultViewHeader = ({
  onBack,
  onPrint,
}: Props) => (
  <Box
    className="no-print"
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
    <Box
      sx={{
        display: 'flex',
        alignItems: 'center',
        gap: 1.5,
      }}
    >
      <IconButton
        aria-label="Back to test results"
        onClick={onBack}
      >
        <ArrowBackIcon />
      </IconButton>

      <Box>
        <Typography
          component="h2"
          variant="h5"
          sx={{
            fontWeight: 750,
          }}
        >
          Test Result Details
        </Typography>

        <Typography
          variant="body2"
          color="text.secondary"
        >
          Review recorded values and
          workflow history.
        </Typography>
      </Box>
    </Box>

    <Button
      variant="outlined"
      startIcon={<PrintIcon />}
      onClick={onPrint}
      sx={{
        borderRadius: 2,
        alignSelf: {
          xs: 'stretch',
          sm: 'center',
        },
      }}
    >
      Print result
    </Button>
  </Box>
);

export default TestResultViewHeader;
