import {
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

import type { TestResultResource } from '../../../types';

import { TestResultTableRow } from './TestResultTableRow';

interface TestResultsTableProps {
  results: TestResultResource[];
  loading: boolean;
  onViewResult: (uuid: string) => void;
}

export const TestResultsTable = ({
  results,
  loading,
  onViewResult,
}: TestResultsTableProps) => {
  const theme = useTheme();

  return (
    <TableContainer>
      <Table>
        <TableHead>
          <TableRow
            sx={{
              backgroundColor: alpha(
                theme.palette.background.default,
                0.4
              ),
            }}
          >
            <TableCell sx={{ fontWeight: 700 }}>Patient</TableCell>
            <TableCell sx={{ fontWeight: 700 }}>Test</TableCell>
            <TableCell sx={{ fontWeight: 700 }}>Order</TableCell>
            <TableCell sx={{ fontWeight: 700 }}>Entered</TableCell>
            <TableCell sx={{ fontWeight: 700 }}>Status</TableCell>
            <TableCell sx={{ fontWeight: 700 }}>Reviewed</TableCell>
            <TableCell align="right" sx={{ fontWeight: 700 }}>
              Actions
            </TableCell>
          </TableRow>
        </TableHead>

        <TableBody>
          {loading ? (
            <TableRow>
              <TableCell colSpan={7} align="center" sx={{ py: 8 }}>
                <Typography color="text.secondary">
                  Loading test results...
                </Typography>
              </TableCell>
            </TableRow>
          ) : results.length === 0 ? (
            <TableRow>
              <TableCell colSpan={7} align="center" sx={{ py: 8 }}>
                <Typography color="text.secondary">
                  No test results found
                </Typography>
              </TableCell>
            </TableRow>
          ) : (
            results.map((result) => (
              <TestResultTableRow
                key={result.uuid}
                result={result}
                onView={onViewResult}
              />
            ))
          )}
        </TableBody>
      </Table>
    </TableContainer>
  );
};