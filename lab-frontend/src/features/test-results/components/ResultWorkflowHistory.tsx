import {
  Box,
  Grid,
  Typography,
} from '@mui/material';

import type {
  TestResultResource,
} from '../../../types';

import WorkflowHistoryCard from './WorkflowHistoryCard';

interface Props {
  result: TestResultResource;
}

const ResultWorkflowHistory = ({
  result,
}: Props) => (
  <Box>
    <Typography
      variant="subtitle1"
      sx={{
        mb: 1.5,
        fontWeight: 750,
      }}
    >
      Workflow history
    </Typography>

    <Grid
      container
      spacing={2}
    >
      <Grid
        size={{
          xs: 12,
          md: 4,
        }}
      >
        <WorkflowHistoryCard
          label="Entered by"
          person={result.entered_by}
          timestamp={result.created_at}
        />
      </Grid>

      <Grid
        size={{
          xs: 12,
          md: 4,
        }}
      >
        <WorkflowHistoryCard
          label="Submitted by"
          person={result.submitted_by}
          timestamp={result.submitted_at}
        />
      </Grid>

      <Grid
        size={{
          xs: 12,
          md: 4,
        }}
      >
        <WorkflowHistoryCard
          label="Reviewed by"
          person={result.reviewed_by}
          timestamp={result.reviewed_at}
        />
      </Grid>
    </Grid>
  </Box>
);

export default ResultWorkflowHistory;
