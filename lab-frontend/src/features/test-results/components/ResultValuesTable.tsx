import {
  Alert,
  Box,
  Paper,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Typography,
  alpha,
  useTheme,
} from '@mui/material';

import {
  Assignment as AssignmentIcon,
} from '@mui/icons-material';

import type {
  TestResultValue,
} from '../../../types';

import ResultFlagChip from './ResultFlagChip';

interface Props {
  results: TestResultValue[];
}

const displayValue = (
  value: string | number | null
): string => (
  value === null ||
  value === ''
    ? 'Not recorded'
    : String(value)
);

const ResultValuesTable = ({
  results,
}: Props) => {
  const theme = useTheme();

  return (
    <Box sx={{ mb: 3 }}>
      <Typography
        variant="subtitle1"
        sx={{
          mb: 1.5,
          fontWeight: 750,
        }}
      >
        <AssignmentIcon
          sx={{
            mr: 1,
            verticalAlign: 'middle',
          }}
        />
        Recorded parameters
      </Typography>

      {results.length === 0 ? (
        <Alert severity="info">
          No parameter values have been
          recorded for this result.
        </Alert>
      ) : (
        <TableContainer
          component={Paper}
          variant="outlined"
        >
          <Table>
            <TableHead
              sx={{
                backgroundColor: alpha(
                  theme.palette.primary.main,
                  0.05
                ),
              }}
            >
              <TableRow>
                <TableCell>
                  Parameter
                </TableCell>
                <TableCell>
                  Value
                </TableCell>
                <TableCell>
                  Unit
                </TableCell>
                <TableCell>
                  Reference range
                </TableCell>
                <TableCell>
                  Flag
                </TableCell>
                <TableCell>
                  Notes
                </TableCell>
              </TableRow>
            </TableHead>

            <TableBody>
              {results.map(
                (item, index) => (
                  <TableRow
                    key={
                      `${item.name}-${index}`
                    }
                  >
                    <TableCell
                      sx={{
                        fontWeight: 700,
                      }}
                    >
                      {item.name ||
                        `Parameter ${index + 1}`}
                    </TableCell>

                    <TableCell>
                      {displayValue(
                        item.value
                      )}
                    </TableCell>

                    <TableCell>
                      {item.unit ||
                        'Not recorded'}
                    </TableCell>

                    <TableCell>
                      {item.reference_range ||
                        'Not recorded'}
                    </TableCell>

                    <TableCell>
                      <ResultFlagChip
                        result={item}
                      />
                    </TableCell>

                    <TableCell>
                      {item.notes || '—'}
                    </TableCell>
                  </TableRow>
                )
              )}
            </TableBody>
          </Table>
        </TableContainer>
      )}
    </Box>
  );
};

export default ResultValuesTable;
