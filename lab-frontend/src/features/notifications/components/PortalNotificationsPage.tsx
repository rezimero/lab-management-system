import {
  Alert,
  Box,
  Button,
  FormControlLabel,
  Pagination,
  Paper,
  Stack,
  Switch,
  Tab,
  Tabs,
  Typography,
} from '@mui/material';

import {
  useCallback,
  useEffect,
  useState,
} from 'react';

import {
  useNavigate,
} from 'react-router-dom';

import {
  fetchPortalNotificationPreferences,
  fetchPortalNotifications,
  markAllPortalNotificationsRead,
  markPortalNotificationRead,
  updatePortalNotificationPreference,
} from '../api/portalNotificationApi';

import NotificationList from './NotificationList';

import type {
  NotificationMessage,
  NotificationPage,
  NotificationPreferences,
} from '../types/notification.types';

import {
  apiErrorMessage,
} from '../../test-results/utils/apiErrorMessage';

interface PortalClient {
  get<T>(url: string, config?: unknown): Promise<{ data: T }>;
  patch<T>(url: string, data?: unknown): Promise<{ data: T }>;
  put<T>(url: string, data?: unknown): Promise<{ data: T }>;
}

interface Props {
  client: PortalClient;
  prefix: '/portal/patient' | '/portal/clinician';
  title: string;
}

const PortalNotificationsPage = ({
  client,
  prefix,
  title,
}: Props) => {
  const navigate = useNavigate();
  const [tab, setTab] = useState<'inbox' | 'preferences'>('inbox');
  const [page, setPage] = useState(1);
  const [inbox, setInbox] = useState<NotificationPage | null>(null);
  const [preferences, setPreferences] =
    useState<NotificationPreferences | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);

    try {
      const [nextInbox, nextPreferences] = await Promise.all([
        fetchPortalNotifications(client, prefix, page),
        fetchPortalNotificationPreferences(client, prefix),
      ]);
      setInbox(nextInbox);
      setPreferences(nextPreferences);
    } catch (requestError: unknown) {
      setError(apiErrorMessage(
        requestError,
        'Failed to load portal notifications.'
      ));
    } finally {
      setLoading(false);
    }
  }, [client, page, prefix]);

  useEffect(() => {
    void load();
  }, [load]);

  const open = async (message: NotificationMessage) => {
    if (!message.read_at) {
      await markPortalNotificationRead(
        client,
        prefix,
        message.uuid
      );
    }

    if (message.action_url) {
      navigate(message.action_url);
    }
  };

  return (
    <Stack spacing={2.5}>
      <Box>
        <Typography variant="h4" sx={{ fontWeight: 760 }}>
          {title}
        </Typography>
        <Typography color="text.secondary" sx={{ mt: 0.75 }}>
          Secure portal notices never include report contents in email.
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
          onChange={(_, value) => setTab(value)}
        >
          <Tab
            value="inbox"
            label={`Inbox${inbox?.unread_count ? ` (${inbox.unread_count})` : ''}`}
          />
          <Tab value="preferences" label="Preferences" />
        </Tabs>
      </Paper>

      {tab === 'inbox' && (
        <Stack spacing={2}>
          <Box sx={{ display: 'flex', justifyContent: 'flex-end' }}>
            <Button
              disabled={!inbox?.unread_count}
              onClick={() => {
                void markAllPortalNotificationsRead(client, prefix)
                  .then(load)
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
            onOpen={(message) => void open(message)}
            onMarkRead={(message) => {
              void markPortalNotificationRead(
                client,
                prefix,
                message.uuid
              ).then(load).catch((requestError: unknown) => {
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

      {tab === 'preferences' && preferences && (
        <Stack spacing={2}>
          {preferences.categories.map((preference) => (
            <Paper
              key={preference.category}
              variant="outlined"
              sx={{ p: 2.5 }}
            >
              <Typography sx={{ mb: 1, fontWeight: 720 }}>
                Report availability
              </Typography>
              <FormControlLabel
                control={
                  <Switch
                    checked={preference.in_app_enabled}
                    onChange={(_, checked) => {
                      void updatePortalNotificationPreference(
                        client,
                        prefix,
                        {
                          ...preference,
                          in_app_enabled: checked,
                        }
                      ).then(load).catch((requestError: unknown) => {
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
                      void updatePortalNotificationPreference(
                        client,
                        prefix,
                        {
                          ...preference,
                          email_enabled: checked,
                        }
                      ).then(load).catch((requestError: unknown) => {
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
            </Paper>
          ))}

          <Alert severity="warning">
            SMS: {preferences.channels.sms.reason}<br />
            WhatsApp: {preferences.channels.whatsapp.reason}
          </Alert>
        </Stack>
      )}
    </Stack>
  );
};

export default PortalNotificationsPage;
