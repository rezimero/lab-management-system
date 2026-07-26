import {
  Avatar,
  Box,
  IconButton,
  TableCell,
  TableRow,
  Typography,
  alpha,
  useTheme,
} from '@mui/material';

import {
  Science as ScienceIcon,
  Visibility as VisibilityIcon,
} from '@mui/icons-material';

import type { TestResultResource } from '../../../types';

import {
  formatTestResultDate,
  testResultPatientInitials,
} from '../utils/testResultListDisplay';

import { TestResultStatusChip } from './TestResultStatusChip';

interface TestResultTableRowProps {
  result: TestResultResource;
  onView: (uuid: string) => void;
}

export const TestResultTableRow = ({
  result,
  onView,
}: TestResultTableRowProps) => {
  const theme = useTheme();
  const orderItem = result.order_item;
  const order = orderItem?.order;
  const patient = order?.patient;
  const testName = orderItem?.test_name || 'Unknown test';

  return (
    <TableRow
      hover
      sx={{
        '&:hover': {
          backgroundColor: alpha(theme.palette.primary.main, 0.02),
        },
        transition: 'background-color 0.2s ease',
      }}
    >
      <TableCell>
        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
          <Avatar
            sx={{
              width: 32,
              height: 32,
              bgcolor: theme.palette.info.main,
              fontSize: '0.75rem',
              fontWeight: 600,
            }}
          >
            {testResultPatientInitials(
              patient?.first_name,
              patient?.last_name
            )}
          </Avatar>
          <Box>
            <Typography variant="body2" sx={{ fontWeight: 500 }}>
              {patient?.full_name || 'Patient unavailable'}
            </Typography>
            <Typography variant="caption" color="text.secondary">
              {patient?.medical_record_number || 'No MRN'}
            </Typography>
          </Box>
        </Box>
      </TableCell>

      <TableCell>
        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
          <ScienceIcon sx={{ fontSize: 16, color: 'text.secondary' }} />
          <Box>
            <Typography variant="body2">{testName}</Typography>
            <Typography variant="caption" color="text.secondary">
              {orderItem?.category || 'Uncategorised'}
            </Typography>
          </Box>
        </Box>
      </TableCell>

      <TableCell>
        <Typography variant="body2">
          {order?.order_number || 'Order unavailable'}
        </Typography>
        <Typography variant="caption" color="text.secondary">
          {order?.priority || '-'}
        </Typography>
      </TableCell>

      <TableCell>{formatTestResultDate(result.created_at)}</TableCell>
      <TableCell><TestResultStatusChip status={result.status} /></TableCell>
      <TableCell>{formatTestResultDate(result.reviewed_at)}</TableCell>

      <TableCell align="right">
        <IconButton
          aria-label={`View ${testName}`}
          size="small"
          onClick={() => onView(result.uuid)}
          sx={{
            color: theme.palette.info.main,
            '&:hover': {
              backgroundColor: alpha(theme.palette.info.main, 0.08),
            },
          }}
        >
          <VisibilityIcon fontSize="small" />
        </IconButton>
      </TableCell>
    </TableRow>
  );
};