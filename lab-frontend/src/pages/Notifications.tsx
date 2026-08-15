import {
  Alert,
  Box,
  Button,
  Card,
  CardContent,
  Chip,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  Divider,
  FormControlLabel,
  Pagination,
  Paper,
  Stack,
  Switch,
  Tab,
  Tabs,
  TextField,
  Typography,
} from '@mui/material';

import {
  useCallback,
  useEffect,
  useMemo,
  useState,
} from 'react';

import {
  useNavigate,
  useSearchParams,
} from 'react-router-dom';

import Layout from '../components/Layout/Layout';

import {
  useAuth,
} from '../contexts/AuthContext';

import {
  acknowledgeCriticalResult,
  fetchCriticalResultEscalations,
  fetchNotificationPreferences,
  fetchNotifications,
  markAllNotificationsRead,
  markNotificationRead,
  resolveCriticalResult,
  updateNotificationPreference,
} from '../features/notifications/api/notificationApi';

import NotificationList from
  '../features/notifications/components/NotificationList';

import type {
  CriticalResultEscalation,
  NotificationMessage,
  NotificationPage,
  NotificationPreferences,
} from '../features/notifications/types/notification.types';

import {
  apiErrorMessage,
} from '../features/test-results/utils/apiErrorMessage';

type TabValue = 'inbox' | 'critical' | 'preferences';
type ActionKind = 'acknowledge' | 'resolve';

interface CriticalAction {
  kind: ActionKind;
  escalation: CriticalResultEscalation;
}

const displayDate = (value: string | null): string => {
  if (!value) {
    return '—';
  }

  const date = new Date(value);

  return Number.isNaN(date.getTime())
    ? '—'
    : date.toLocaleString();
};

const categoryLabel = (category: string): string => (
  category
    .split('_')
    .map((part) => (
      part.charAt(0).toUpperCase() + part.slice(1)
    ))
    .join(' ')
);

const Notifications = () => {
  const auth = useAuth();
  const navigate = useNavigate();
  const [searchParams, setSearchParams] = useSearchParams();
  const focusedCritical = searchParams.get('critical')?.trim() ?? '';
  const [tab, setTab] = useState<TabValue>('inbox');
  const [page, setPage] = useState(1);
  const [inbox, setInbox] = useState<NotificationPage | null>(null);
  const [critical, setCritical] = useState<CriticalResultEscalation[]>([]);
  const [criticalPage, setCriticalPage] = useState(1);
  const [criticalLastPage, setCriticalLastPage] = useState(1);
  const [preferences, setPreferences] =
    useState<NotificationPreferences | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [action, setAction] = useState<CriticalAction | null>(null);
  const [note, setNote] = useState('');
  const [saving, setSaving] = useState(false);

  const canManageCritical = useMemo(
    () => [
      'superadmin',
      'admin',
      'doctor',
      'lab_technician',
    ].includes(auth.user?.role ?? ''),
    [auth.user?.role]
  );

  const loadInbox = useCallback(async () => {
    const next = await fetchNotifications(page);
    setInbox(next);
  }, [page]);

  const loadCritical = useCallback(async () => {
    if (!canManageCritical) {
      setCritical([]);
      return;
    }

    const next = await fetchCriticalResultEscalations(
      criticalPage,
      focusedCritical || undefined
    );
    setCritical(next.data);
    setCriticalLastPage(next.meta.last_page);
  }, [canManageCritical, criticalPage, focusedCritical]);

  const loadPreferences = useCallback(async () => {
    setPreferences(await fetchNotificationPreferences());
  }, []);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);

    try {
      await Promise.all([
        loadInbox(),
        loadCritical(),
        loadPreferences(),
      ]);
    } catch (requestError: unknown) {
      setError(apiErrorMessage(
        requestError,
        'Failed to load notifications.'
      ));
    } finally {
      setLoading(false);
    }
  }, [loadCritical, loadInbox, loadPreferences]);

  useEffect(() => {
    void load();
  }, [load]);

  useEffect(() => {
    if (focusedCritical && canManageCritical) {
      setTab('critical');
    }
  }, [canManageCritical, focusedCritical]);

  const openMessage = async (message: NotificationMessage) => {
    if (!message.read_at) {
      await markNotificationRead(message.uuid);
    }

    if (message.action_url) {
      navigate(message.action_url);
    }
  };

  const submitAction = async () => {
    if (!action || note.trim().length < 5) {
      return;
    }

    setSaving(true);
    setError(null);

    try {
      if (action.kind === 'acknowledge') {
        await acknowledgeCriticalResult(
          action.escalation.uuid,
          note.trim()
        );
      } else {
        await resolveCriticalResult(
          action.escalation.uuid,
          note.trim()
        );
      }

      setAction(null);
      setNote('');
      await Promise.all([loadCritical(), loadInbox()]);
    } catch (requestError: unknown) {
      setError(apiErrorMessage(
        requestError,
        'The critical-result action failed.'
      ));
    } finally {
      setSaving(false);
    }
  };

  return (
    <Layout title="Notifications">
      <Stack spacing={2.5}>
        <Box>
          <Typography variant="h4" sx={{ fontWeight: 760 }}>
            Notifications and critical results
          </Typography>
          <Typography color="text.secondary" sx={{ mt: 0.75 }}>
            Review secure in-app notices, critical-result escalation records and channel preferences.
          </Typography>
        </Box>

        {error && (
          <Alert severity="error" onClose={() => setError(null)}>
            {error}
          </Alert>
        )}

        <Paper variant="outlined">
          <Tabs
            value={tab}
            onChange={(_, value: TabValue) => setTab(value)}
            variant="scrollable"
          >
            <Tab
              value="inbox"
              label={`Inbox${inbox?.unread_count ? ` (${inbox.unread_count})` : ''}`}
            />
            {canManageCritical && (
              <Tab value="critical" label="Critical results" />
            )}
            <Tab value="preferences" label="Preferences" />
          </Tabs>
        </Paper>

        {tab === 'inbox' && (
          <Stack spacing={2}>
            <Box sx={{ display: 'flex', justifyContent: 'flex-end' }}>
              <Button
                disabled={!inbox?.unread_count}
                onClick={() => {
                  void markAllNotificationsRead()
                    .then(loadInbox)
                    .catch((requestError: unknown) => {
                      setError(apiErrorMessage(
                        requestError,
                        'Failed to mark notifications as read.'
                      ));
                    });
                }}
              >
                Mark all read
              </Button>
            </Box>

            <NotificationList
              messages={inbox?.data ?? []}
              loading={loading}
              onOpen={(message) => {
                void openMessage(message);
              }}
              onMarkRead={(message) => {
                void markNotificationRead(message.uuid)
                  .then(loadInbox)
                  .catch((requestError: unknown) => {
                    setError(apiErrorMessage(
                      requestError,
                      'Failed to mark the notification as read.'
                    ));
                  });
              }}
            />

            {(inbox?.meta.last_page ?? 1) > 1 && (
              <Pagination
                page={page}
                count={inbox?.meta.last_page ?? 1}
                onChange={(_, next) => setPage(next)}
              />
            )}
          </Stack>
        )}

        {tab === 'critical' && canManageCritical && (
          <Stack spacing={2}>
            {focusedCritical && (
              <Alert
                severity="info"
                action={
                  <Button
                    color="inherit"
                    size="small"
                    onClick={() => {
                      setSearchParams({});
                      setCriticalPage(1);
                    }}
                  >
                    Show all
                  </Button>
                }
              >
                Showing the critical-result record selected from a secure notification.
              </Alert>
            )}

            {critical.length === 0 && !loading ? (
              <Alert severity="success">
                No critical-result escalation records are waiting in this view.
              </Alert>
            ) : critical.map((item) => (
              <Card key={item.uuid} variant="outlined">
                <CardContent>
                  <Stack spacing={1.5}>
                    <Stack
                      direction={{ xs: 'column', sm: 'row' }}
                      spacing={1}
                      sx={{ alignItems: { sm: 'center' } }}
                    >
                      <Typography variant="h6" sx={{ fontWeight: 720 }}>
                        {item.test_name ?? 'Critical laboratory result'}
                      </Typography>
                      <Chip
                        size="small"
                        color={item.status === 'open'
                          ? 'error'
                          : item.status === 'acknowledged'
                            ? 'warning'
                            : 'success'}
                        label={item.status}
                      />
                      <Chip
                        size="small"
                        variant="outlined"
                        label={`Level ${item.escalation_level}`}
                      />
                    </Stack>

                    <Typography variant="body2">
                      {item.patient?.name ?? 'Patient unavailable'} · {item.patient?.medical_record_number ?? 'No MRN'}
                    </Typography>
                    <Typography variant="body2" color="text.secondary">
                      Order {item.order_number ?? item.order_uuid} · {item.critical_count} critical analyte flag(s)
                    </Typography>
                    <Typography variant="caption" color="text.secondary">
                      Detected {displayDate(item.detected_at)} · acknowledgement due {displayDate(item.acknowledgement_due_at)}
                    </Typography>

                    <Divider />

                    <Stack direction="row" spacing={1} sx={{ flexWrap: 'wrap' }}>
                      <Button
                        variant="outlined"
                        onClick={() => navigate(
                          `/test-results/${item.test_result_uuid}`
                        )}
                      >
                        Review result
                      </Button>
                      {item.status === 'open' && (
                        <Button
                          variant="contained"
                          color="error"
                          onClick={() => setAction({
                            kind: 'acknowledge',
                            escalation: item,
                          })}
                        >
                          Acknowledge
                        </Button>
                      )}
                      {item.status === 'acknowledged' && (
                        <Button
                          variant="contained"
                          color="success"
                          onClick={() => setAction({
                            kind: 'resolve',
                            escalation: item,
                          })}
                        >
                          Resolve
                        </Button>
                      )}
                    </Stack>
                  </Stack>
                </CardContent>
              </Card>
            ))}

            {criticalLastPage > 1 && (
              <Pagination
                page={criticalPage}
                count={criticalLastPage}
                onChange={(_, next) => setCriticalPage(next)}
              />
            )}
          </Stack>
        )}

        {tab === 'preferences' && preferences && (
          <Stack spacing={2}>
            <Alert severity="info">
              Critical-result and account-security notifications are mandatory. SMS and WhatsApp remain off until reviewed providers are configured.
            </Alert>

            {preferences.categories.map((preference) => (
              <Paper
                key={preference.category}
                variant="outlined"
                sx={{ p: 2.5 }}
              >
                <Stack spacing={1}>
                  <Typography sx={{ fontWeight: 720 }}>
                    {categoryLabel(preference.category)}
                  </Typography>
                  <FormControlLabel
                    control={
                      <Switch
                        checked={preference.in_app_enabled}
                        onChange={(_, checked) => {
                          const next = {
                            ...preference,
                            in_app_enabled: checked,
                          };
                          void updateNotificationPreference(next)
                            .then(loadPreferences)
                            .catch((requestError: unknown) => {
                              setError(apiErrorMessage(
                                requestError,
                                'Failed to update the preference.'
                              ));
                            });
                        }}
                      />
                    }
                    label="In-app"
                  />
                  <FormControlLabel
                    control={
                      <Switch
                        checked={preference.email_enabled}
                        disabled={!preferences.channels.email.enabled}
                        onChange={(_, checked) => {
                          const next = {
                            ...preference,
                            email_enabled: checked,
                          };
                          void updateNotificationPreference(next)
                            .then(loadPreferences)
                            .catch((requestError: unknown) => {
                              setError(apiErrorMessage(
                                requestError,
                                'Failed to update the preference.'
                              ));
                            });
                        }}
                      />
                    }
                    label="Email"
                  />
                </Stack>
              </Paper>
            ))}

            <Alert severity="warning">
              SMS: {preferences.channels.sms.reason}<br />
              WhatsApp: {preferences.channels.whatsapp.reason}
            </Alert>
          </Stack>
        )}
      </Stack>

      <Dialog
        open={action !== null}
        onClose={() => {
          if (!saving) {
            setAction(null);
            setNote('');
          }
        }}
        fullWidth
        maxWidth="sm"
      >
        <DialogTitle>
          {action?.kind === 'resolve'
            ? 'Resolve critical result'
            : 'Acknowledge critical result'}
        </DialogTitle>
        <DialogContent>
          <TextField
            autoFocus
            fullWidth
            multiline
            minRows={3}
            sx={{ mt: 1 }}
            label={action?.kind === 'resolve'
              ? 'Resolution note'
              : 'Acknowledgement note'}
            value={note}
            onChange={(event) => setNote(event.target.value)}
            helperText="Record a concise operational note; do not paste credentials or tokens."
          />
        </DialogContent>
        <DialogActions>
          <Button
            disabled={saving}
            onClick={() => {
              setAction(null);
              setNote('');
            }}
          >
            Cancel
          </Button>
          <Button
            variant="contained"
            disabled={saving || note.trim().length < 5}
            onClick={() => void submitAction()}
          >
            {saving ? 'Saving…' : 'Confirm'}
          </Button>
        </DialogActions>
      </Dialog>
    </Layout>
  );
};

export default Notifications;
