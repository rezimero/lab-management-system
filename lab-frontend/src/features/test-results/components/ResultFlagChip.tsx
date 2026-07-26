import {
  Chip,
  Typography,
} from '@mui/material';

import type {
  TestResultValue,
} from '../../../types';

import {
  formatResultLabel,
  resultFlagColor,
} from '../utils/testResultDisplay';

interface Props {
  result: TestResultValue;
}

const ResultFlagChip = ({
  result,
}: Props) => {
  if (!result.flag) {
    return (
      <Typography
        variant="body2"
        color="text.secondary"
      >
        Not flagged
      </Typography>
    );
  }

  return (
    <Chip
      label={formatResultLabel(
        result.flag
      )}
      color={resultFlagColor(
        result.flag
      )}
      size="small"
      variant="outlined"
    />
  );
};

export default ResultFlagChip;
