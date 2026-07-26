import {
  Box,
  Paper,
  Typography,
  alpha,
  useTheme,
} from '@mui/material';

import type {
  TestResultResource,
} from '../../../types';

import {
  formatResultDateTime,
} from '../utils/testResultDisplay';

import ResultIdentityCards from './ResultIdentityCards';
import ResultNotesSection from './ResultNotesSection';
import ResultStatusPanel from './ResultStatusPanel';
import ResultValuesTable from './ResultValuesTable';
import ResultWorkflowHistory from './ResultWorkflowHistory';

interface Props {
  result: TestResultResource;
}

const TestResultDetailPaper = ({
  result,
}: Props) => {
  const theme = useTheme();
  const results = Array.isArray(
    result.results_data
  )
    ? result.results_data
    : [];

  return (
    <Paper
      className="print-paper"
      sx={{
        p: {
          xs: 2,
          sm: 3,
          lg: 4,
        },
        borderRadius: 3,
        backgroundColor: '#FFFFFF',
        border: `1px solid ${alpha(
          theme.palette.divider,
          0.12
        )}`,
      }}
    >
      <Box
        sx={{
          mb: 3,
          pb: 2.5,
          borderBottom:
            `1px solid ${alpha(
              theme.palette.divider,
              0.12
            )}`,
        }}
      >
        <Typography
          variant="h4"
          sx={{
            fontWeight: 800,
            color:
              theme.palette.primary.main,
          }}
        >
          Laboratory Test Result
        </Typography>

        <Typography
          variant="body2"
          color="text.secondary"
          sx={{
            mt: 0.5,
          }}
        >
          Result UUID: {result.uuid}
        </Typography>

        <Typography
          variant="caption"
          color="text.secondary"
          sx={{
            display: 'block',
            mt: 0.5,
          }}
        >
          Created:{' '}
          {formatResultDateTime(
            result.created_at
          )}
        </Typography>
      </Box>

      <ResultStatusPanel
        result={result}
      />

      <ResultIdentityCards
        result={result}
      />

      <ResultValuesTable
        results={results}
      />

      <ResultNotesSection
        result={result}
      />

      <ResultWorkflowHistory
        result={result}
      />

      <Box
        sx={{
          mt: 3,
          pt: 2,
          borderTop:
            `1px solid ${alpha(
              theme.palette.divider,
              0.12
            )}`,
        }}
      >
        <Typography
          variant="caption"
          color="text.secondary"
        >
          Last updated:{' '}
          {formatResultDateTime(
            result.updated_at
          )}
        </Typography>
      </Box>
    </Paper>
  );
};

export default TestResultDetailPaper;
