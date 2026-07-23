import DeleteOutlineRounded from '@mui/icons-material/DeleteOutlineRounded';
import EditRounded from '@mui/icons-material/EditRounded';
import LockResetRounded from '@mui/icons-material/LockResetRounded';
import PowerSettingsNewRounded from '@mui/icons-material/PowerSettingsNewRounded';

import {
  Menu,
  MenuItem,
} from '@mui/material';

import type {
  ConfirmationAction,
  TenantRecord,
} from './tenantManagementTypes';

interface TenantActionMenuProps {
  anchorEl: HTMLElement | null;
  tenant: TenantRecord | null;
  onClose: () => void;
  onEdit: (
    tenant: TenantRecord
  ) => void;
  onResetPassword: (
    tenant: TenantRecord
  ) => void;
  onConfirm: (
    tenant: TenantRecord,
    action: Exclude<
      ConfirmationAction,
      null
    >
  ) => void;
}

const TenantActionMenu = ({
  anchorEl,
  tenant,
  onClose,
  onEdit,
  onResetPassword,
  onConfirm,
}: TenantActionMenuProps) => {
  return (
    <Menu
      anchorEl={anchorEl}
      open={Boolean(anchorEl)}
      onClose={onClose}
    >
      <MenuItem
        onClick={() => {
          if (tenant) {
            onEdit(tenant);
          }
        }}
      >
        <EditRounded
          sx={{
            mr: 1.25,
            fontSize: 20,
          }}
        />
        Edit tenant
      </MenuItem>

      <MenuItem
        onClick={() => {
          if (tenant) {
            onResetPassword(
              tenant
            );
          }
        }}
      >
        <LockResetRounded
          sx={{
            mr: 1.25,
            fontSize: 20,
          }}
        />
        Reset admin password
      </MenuItem>

      <MenuItem
        onClick={() => {
          if (tenant) {
            onConfirm(
              tenant,
              tenant.is_active
                ? 'suspend'
                : 'activate'
            );
          }
        }}
      >
        <PowerSettingsNewRounded
          sx={{
            mr: 1.25,
            fontSize: 20,
          }}
        />
        {tenant?.is_active
          ? 'Suspend tenant'
          : 'Activate tenant'}
      </MenuItem>

      <MenuItem
        sx={{
          color: 'error.main',
        }}
        onClick={() => {
          if (tenant) {
            onConfirm(
              tenant,
              'delete'
            );
          }
        }}
      >
        <DeleteOutlineRounded
          sx={{
            mr: 1.25,
            fontSize: 20,
          }}
        />
        Delete tenant
      </MenuItem>
    </Menu>
  );
};

export default TenantActionMenu;
