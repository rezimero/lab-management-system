import {
  Box,
  Button,
} from '@mui/material';

import {
  Save as SaveIcon,
} from '@mui/icons-material';

interface Props {
  isEdit: boolean;
  saving: boolean;
  canSave: boolean;
  blocked: boolean;
  onCancel: () => void;
}

const TestResultFormActions = ({
  isEdit,
  saving,
  canSave,
  blocked,
  onCancel,
}: Props) => (
  <Box
    sx={{
      mt: 3,
      display: 'flex',
      flexDirection: {
        xs: 'column-reverse',
        sm: 'row',
      },
      justifyContent: 'flex-end',
      gap: 1.5,
    }}
  >
    <Button
      type="button"
      variant="outlined"
      onClick={onCancel}
    >
      Cancel
    </Button>

    {!blocked && (
      <Button
        type="submit"
        variant="contained"
        startIcon={
          saving
            ? undefined
            : <SaveIcon />
        }
        disabled={!canSave}
      >
        {saving
          ? 'Saving...'
          : isEdit
            ? 'Save changes'
            : 'Create draft'}
      </Button>
    )}
  </Box>
);

export default TestResultFormActions;
