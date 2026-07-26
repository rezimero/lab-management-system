import {
  Chip,
  Paper,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Typography,
} from '@mui/material';

import type {
  ReportResultItem,
} from '../types/laboratoryReport.types';

import {
  formatReportLabel,
} from '../utils/reportDisplay';

interface Props {
  item: ReportResultItem;
}

const ReportResultValues = ({
  item,
}: Props) => {
  const values = Array.isArray(
    item.results_data
  )
    ? item.results_data
    : [];

  return (
    <Paper
      className="report-result-card"
      variant="outlined"
      sx={{
        mb: 2.5,
        overflow: 'hidden',
      }}
    >
      <Typography
        variant="subtitle1"
        sx={{
          px: 2,
          pt: 2,
          fontWeight: 750,
        }}
      >
        {item.test_name ??
          'Laboratory test'}
      </Typography>

      <Typography
        variant="caption"
        color="text.secondary"
        sx={{
          px: 2,
          display: 'block',
          mb: 1.5,
        }}
      >
        {item.category ??
          'Uncategorised'}
      </Typography>

      <TableContainer>
        <Table size="small">
          <TableHead>
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
                Reference
              </TableCell>
              <TableCell>
                Flag
              </TableCell>
            </TableRow>
          </TableHead>

          <TableBody>
            {values.map(
              (value, index) => (
                <TableRow
                  key={
                    `${value.name}-${index}`
                  }
                >
                  <TableCell>
                    {value.name ||
                      `Parameter ${index + 1}`}
                  </TableCell>
                  <TableCell>
                    {value.value === null ||
                    value.value === ''
                      ? 'Not recorded'
                      : String(value.value)}
                  </TableCell>
                  <TableCell>
                    {value.unit || '—'}
                  </TableCell>
                  <TableCell>
                    {value.reference_range ||
                      '—'}
                  </TableCell>
                  <TableCell>
                    {value.flag ? (
                      <Chip
                        label={
                          formatReportLabel(
                            value.flag
                          )
                        }
                        size="small"
                        variant="outlined"
                      />
                    ) : (
                      'Not flagged'
                    )}
                  </TableCell>
                </TableRow>
              )
            )}
          </TableBody>
        </Table>
      </TableContainer>

      {item.notes && (
        <Typography
          variant="body2"
          sx={{
            p: 2,
            whiteSpace: 'pre-wrap',
          }}
        >
          Notes: {item.notes}
        </Typography>
      )}
    </Paper>
  );
};

export default ReportResultValues;
