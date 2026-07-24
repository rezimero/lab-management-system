import {
  Box,
  IconButton,
  Typography,
} from '@mui/material';

import {
  ArrowBack as ArrowBackIcon,
} from '@mui/icons-material';

interface Props {
  title: string;
  isEdit: boolean;
  onBack: () => void;
}

const TestResultFormHeader = ({
  title,
  isEdit,
  onBack,
}: Props) => (
  <Box
    sx={{
      mb: 3,
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
        variant="h5"
        component="h2"
        sx={{
          fontWeight: 750,
        }}
      >
        {title}
      </Typography>

      <Typography
        variant="body2"
        color="text.secondary"
      >
        {isEdit
          ? 'Update an editable draft or rejected result.'
          : 'Record values for a laboratory order item.'}
      </Typography>
    </Box>
  </Box>
);

export default TestResultFormHeader;
