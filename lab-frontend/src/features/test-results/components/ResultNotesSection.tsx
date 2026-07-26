import {
  Alert,
  Box,
  Paper,
  Typography,
} from '@mui/material';

import type {
  TestResultResource,
} from '../../../types';

interface Props {
  result: TestResultResource;
}

const ResultNotesSection = ({
  result,
}: Props) => (
  <>
    {result.notes && (
      <Box sx={{ mb: 3 }}>
        <Typography
          variant="subtitle2"
          sx={{
            mb: 1,
            fontWeight: 750,
          }}
        >
          Result notes
        </Typography>

        <Paper
          variant="outlined"
          sx={{
            p: 2,
            borderRadius: 2,
          }}
        >
          <Typography
            variant="body2"
            sx={{
              whiteSpace: 'pre-wrap',
            }}
          >
            {result.notes}
          </Typography>
        </Paper>
      </Box>
    )}

    {result.rejection_reason && (
      <Alert
        severity="error"
        sx={{
          mb: 3,
        }}
      >
        <Typography
          variant="subtitle2"
          sx={{
            mb: 0.5,
            fontWeight: 750,
          }}
        >
          Rejection reason
        </Typography>

        {result.rejection_reason}
      </Alert>
    )}
  </>
);

export default ResultNotesSection;
