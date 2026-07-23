import DeleteOutlineRounded from '@mui/icons-material/DeleteOutlineRounded';
import PowerSettingsNewRounded from '@mui/icons-material/PowerSettingsNewRounded';

import {
  Alert,
  Box,
  Button,
  CircularProgress,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  TextField,
  Typography,
} from '@mui/material';

import type {
  ConfirmationAction,
  TenantRecord,
} from './tenantManagementTypes';

interface TenantConfirmationDialogProps {
  tenant: TenantRecord | null;
  action: ConfirmationAction;
  title: string;
  busy: boolean;
  deleteConfirmation: string;
  deleteConfirmed: boolean;
  onDeleteConfirmationChange: (
    value: string
  ) => void;
  onClose: () => void;
  onConfirm: (
    tenant: TenantRecord,
    action: Exclude<
      ConfirmationAction,
      null
    >
  ) => void;
}

const TenantConfirmationDialog = ({
  tenant,
  action,
  title,
  busy,
  deleteConfirmation,
  deleteConfirmed,
  onDeleteConfirmationChange,
  onClose,
  onConfirm,
}: TenantConfirmationDialogProps) => {
  return (
    <Dialog
      open={Boolean(
        tenant &&
          action
      )}
      onClose={
        busy
          ? undefined
          : onClose
      }
      maxWidth="sm"
      fullWidth
    >
      <DialogTitle>
        {title}
      </DialogTitle>

      <DialogContent>
        {action === 'delete' ? (
          <>
            <Alert
              severity="error"
              sx={{
                mb: 2,
              }}
            >
              This permanently removes the
              tenant record and its database.
              This action cannot be undone.
            </Alert>

            <Typography
              sx={{
                mb: 1.5,
                color: 'text.secondary',
                fontSize: '0.76rem',
                lineHeight: 1.6,
              }}
            >
              Type{' '}
              <Box
                component="strong"
                sx={{
                  color: 'text.primary',
                }}
              >
                {tenant?.domain}
              </Box>{' '}
              to confirm deletion.
            </Typography>

            <TextField
              fullWidth
              autoFocus
              label="Tenant domain"
              value={
                deleteConfirmation
              }
              onChange={(event) => {
                onDeleteConfirmationChange(
                  event.target.value
                );
              }}
            />
          </>
        ) : (
          <Alert
            severity={
              action === 'suspend'
                ? 'warning'
                : 'info'
            }
          >
            {action === 'suspend'
              ? `${tenant?.name} will be prevented from operating normally until reactivated.`
              : `${tenant?.name} will be restored to active platform status.`}
          </Alert>
        )}
      </DialogContent>

      <DialogActions
        sx={{
          px: 3,
          pb: 2.5,
        }}
      >
        <Button
          variant="outlined"
          disabled={busy}
          onClick={onClose}
        >
          Cancel
        </Button>

        <Button
          variant="contained"
          color={
            action === 'delete'
              ? 'error'
              : action ===
                  'suspend'
                ? 'warning'
                : 'success'
          }
          disabled={
            busy ||
            !deleteConfirmed
          }
          startIcon={
            busy ? (
              <CircularProgress
                size={16}
                color="inherit"
              />
            ) : action ===
              'delete' ? (
              <DeleteOutlineRounded />
            ) : (
              <PowerSettingsNewRounded />
            )
          }
          onClick={() => {
            if (
              !tenant ||
              !action
            ) {
              return;
            }

            onConfirm(
              tenant,
              action
            );
          }}
        >
          {action === 'delete'
            ? 'Delete permanently'
            : action === 'suspend'
              ? 'Suspend tenant'
              : 'Activate tenant'}
        </Button>
      </DialogActions>
    </Dialog>
  );
};

export default TenantConfirmationDialog;
