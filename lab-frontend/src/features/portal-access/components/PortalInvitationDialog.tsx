import {
  CheckCircleRounded,
  ContentCopyRounded,
} from '@mui/icons-material';

import {
  Alert,
  Button,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  Stack,
  TextField,
  Typography,
} from '@mui/material';

import {
  useEffect,
  useMemo,
  useState,
} from 'react';

import type {
  PortalAccessRow,
  PortalInvitationIssueResult,
} from '../types/portalAccessAdmin.types';

interface Props {
  row: PortalAccessRow | null;
  open: boolean;
  saving: boolean;
  error: string | null;
  result: PortalInvitationIssueResult | null;
  onClose: () => void;
  onSubmit: (email: string) => void;
}

const buildActivationLink = (
  result: PortalInvitationIssueResult
): string => {
  const origin = window.location.origin;
  const query = new URLSearchParams({
    tenant: result.activation.tenant_domain,
  });
  const fragment = new URLSearchParams({
    token: result.activation.token,
  });

  return `${origin}${result.activation.path}?${query.toString()}#${fragment.toString()}`;
};

const PortalInvitationDialog = ({
  row,
  open,
  saving,
  error,
  result,
  onClose,
  onSubmit,
}: Props) => {
  const [email, setEmail] = useState('');
  const [copied, setCopied] = useState(false);
  const [copyError, setCopyError] = useState(false);

  useEffect(() => {
    if (open) {
      setEmail(
        row?.account?.email
        ?? row?.subject.email
        ?? ''
      );
      setCopied(false);
      setCopyError(false);
    }
  }, [open, row]);

  const activationLink = useMemo(
    () => result
      ? buildActivationLink(result)
      : null,
    [result]
  );

  const copyLink = async () => {
    if (!activationLink) {
      return;
    }

    try {
      if (!navigator.clipboard) {
        throw new Error('Clipboard API unavailable.');
      }

      await navigator.clipboard.writeText(
        activationLink
      );
      setCopied(true);
      setCopyError(false);
    } catch {
      setCopied(false);
      setCopyError(true);
    }
  };

  return (
    <Dialog
      open={open}
      onClose={saving ? undefined : onClose}
      fullWidth
      maxWidth="sm"
    >
      <DialogTitle>
        {result
          ? 'Activation link created'
          : 'Create portal invitation'}
      </DialogTitle>

      <DialogContent>
        <Stack spacing={2} sx={{ mt: 0.5 }}>
          <Typography color="text.secondary">
            {row?.subject.name}
          </Typography>

          {error && (
            <Alert severity="error">{error}</Alert>
          )}

          {result && activationLink ? (
            <>
              <Alert
                severity="success"
                icon={<CheckCircleRounded />}
              >
                Copy this link now. For security, the raw invitation token is shown only in this response.
              </Alert>

              <TextField
                label="Activation link"
                value={activationLink}
                multiline
                minRows={3}
                slotProps={{
                  input: { readOnly: true },
                }}
              />

              {copyError && (
                <Alert severity="warning">
                  Automatic copying is unavailable. Select and copy the activation link manually.
                </Alert>
              )}

              <Button
                variant="contained"
                startIcon={<ContentCopyRounded />}
                onClick={() => {
                  void copyLink();
                }}
              >
                {copied ? 'Copied' : 'Copy activation link'}
              </Button>
            </>
          ) : (
            <>
              <TextField
                autoFocus
                required
                type="email"
                label="Portal login email"
                value={email}
                onChange={(event) => {
                  setEmail(event.target.value);
                }}
                helperText="A pending invitation will be revoked when it is replaced."
              />

              <Typography
                variant="caption"
                color="text.secondary"
              >
                Activated accounts cannot be re-invited. Password reset will be handled by the notifications milestone.
              </Typography>
            </>
          )}
        </Stack>
      </DialogContent>

      <DialogActions sx={{ px: 3, pb: 2.5 }}>
        <Button
          onClick={onClose}
          disabled={saving}
        >
          {result ? 'Done' : 'Cancel'}
        </Button>

        {!result && (
          <Button
            variant="contained"
            disabled={saving || !email.trim()}
            onClick={() => onSubmit(email.trim())}
          >
            {saving ? 'Creating…' : 'Create invitation'}
          </Button>
        )}
      </DialogActions>
    </Dialog>
  );
};

export default PortalInvitationDialog;
