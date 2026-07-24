import {
  Divider,
  Grid,
  Paper,
  Typography,
} from '@mui/material';
import {
  Person as PersonIcon,
  Science as ScienceIcon,
} from '@mui/icons-material';
import type {
  TestResultResource,
} from '../../../types';
import {
  formatResultLabel,
} from '../utils/testResultDisplay';
const Field = ({
  label,
  value,
  bold = false,
}: {
  label: string;
  value: string;
  bold?: boolean;
}) => (
  <Grid size={{ xs: 12 }}>
    <Typography
      variant="caption"
      color="text.secondary"
    >
      {label}
    </Typography>
    <Typography
      variant="body2"
      sx={{
        fontWeight: bold
          ? 700
          : undefined,
        wordBreak: 'break-word',
      }}
    >
      {value}
    </Typography>
  </Grid>
);
interface Props {
  result: TestResultResource;
}
const ResultIdentityCards = ({
  result,
}: Props) => {
  const item = result.order_item;
  const order = item?.order;
  const patient = order?.patient;
  return (
    <Grid
      container
      spacing={2.5}
      sx={{
        mb: 3,
      }}
    >
      <Grid
        size={{
          xs: 12,
          md: 6,
        }}
      >
        <Paper
          variant="outlined"
          sx={{
            p: 2.25,
            height: '100%',
            borderRadius: 2.5,
          }}
        >
          <Typography
            variant="subtitle1"
            color="primary"
            sx={{
              mb: 1,
              fontWeight: 750,
            }}
          >
            <PersonIcon
              sx={{
                mr: 1,
                verticalAlign: 'middle',
              }}
            />
            Patient
          </Typography>
          <Divider sx={{ mb: 1.5 }} />
          <Grid
            container
            spacing={1.5}
          >
            <Field
              label="Full name"
              value={
                patient?.full_name ??
                'Not recorded'
              }
              bold
            />
            <Field
              label="Medical record number"
              value={
                patient
                  ?.medical_record_number ??
                'Not recorded'
              }
            />
          </Grid>
        </Paper>
      </Grid>
      <Grid
        size={{
          xs: 12,
          md: 6,
        }}
      >
        <Paper
          variant="outlined"
          sx={{
            p: 2.25,
            height: '100%',
            borderRadius: 2.5,
          }}
        >
          <Typography
            variant="subtitle1"
            color="primary"
            sx={{
              mb: 1,
              fontWeight: 750,
            }}
          >
            <ScienceIcon
              sx={{
                mr: 1,
                verticalAlign: 'middle',
              }}
            />
            Test and order item
          </Typography>
          <Divider sx={{ mb: 1.5 }} />
          <Grid
            container
            spacing={1.5}
          >
            <Field
              label="Test name"
              value={
                item?.test_name ??
                'Not recorded'
              }
              bold
            />
            <Field
              label="Category"
              value={
                item?.category ??
                'Not recorded'
              }
            />
            <Field
              label="Item status"
              value={formatResultLabel(
                item?.status
              )}
            />
            <Field
              label="Order status"
              value={formatResultLabel(
                order?.status
              )}
            />
            <Field
              label="Order UUID"
              value={
                order?.uuid ??
                'Not recorded'
              }
            />
          </Grid>
        </Paper>
      </Grid>
    </Grid>
  );
};
export default ResultIdentityCards;
