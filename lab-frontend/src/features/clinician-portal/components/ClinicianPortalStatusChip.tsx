import {
  Chip,
} from '@mui/material';

import {
  alpha,
} from '@mui/material/styles';

import {
  formatPortalLabel,
} from '../utils/clinicianPortalDisplay';

interface ClinicianPortalStatusChipProps {
  value?: string | null;
}

const ClinicianPortalStatusChip = ({
  value,
}: ClinicianPortalStatusChipProps) => {
  const normalized =
    value?.toLowerCase() ?? '';

  const tone = (() => {
    if (
      normalized === 'completed'
      || normalized === 'issued'
    ) {
      return {
        color: '#067647',
        background: '#ECFDF3',
      };
    }

    if (
      normalized === 'amended'
      || normalized === 'in_progress'
      || normalized === 'sample_collected'
    ) {
      return {
        color: '#175CD3',
        background: '#EFF8FF',
      };
    }

    if (
      normalized === 'cancelled'
    ) {
      return {
        color: '#B42318',
        background: '#FEF3F2',
      };
    }

    return {
      color: '#344054',
      background: '#F2F4F7',
    };
  })();

  return (
    <Chip
      size="small"
      label={formatPortalLabel(value)}
      sx={{
        color: tone.color,
        backgroundColor: tone.background,
        border: '1px solid',
        borderColor: alpha(
          tone.color,
          0.13
        ),
      }}
    />
  );
};

export default ClinicianPortalStatusChip;
