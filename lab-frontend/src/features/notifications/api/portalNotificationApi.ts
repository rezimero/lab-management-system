import type {
  NotificationMessage,
  NotificationPage,
  NotificationPreference,
  NotificationPreferences,
} from '../types/notification.types';

interface PortalClient {
  get<T>(url: string, config?: unknown): Promise<{ data: T }>;
  patch<T>(url: string, data?: unknown): Promise<{ data: T }>;
  put<T>(url: string, data?: unknown): Promise<{ data: T }>;
}

interface WrappedResource<T> {
  data: T;
}

export const fetchPortalNotifications = async (
  client: PortalClient,
  prefix: '/portal/patient' | '/portal/clinician',
  page = 1
): Promise<NotificationPage> => {
  const response = await client.get<NotificationPage>(
    `${prefix}/notifications`,
    {
      params: {
        page,
        per_page: 20,
      },
    }
  );

  return response.data;
};

export const markPortalNotificationRead = async (
  client: PortalClient,
  prefix: '/portal/patient' | '/portal/clinician',
  uuid: string
): Promise<NotificationMessage> => {
  const response = await client.patch<
    WrappedResource<NotificationMessage>
  >(`${prefix}/notifications/${uuid}/read`);

  return response.data.data;
};

export const markAllPortalNotificationsRead = async (
  client: PortalClient,
  prefix: '/portal/patient' | '/portal/clinician'
): Promise<void> => {
  await client.patch(`${prefix}/notifications/read-all`);
};

export const fetchPortalNotificationPreferences = async (
  client: PortalClient,
  prefix: '/portal/patient' | '/portal/clinician'
): Promise<NotificationPreferences> => {
  const response = await client.get<NotificationPreferences>(
    `${prefix}/notification-preferences`
  );

  return response.data;
};

export const updatePortalNotificationPreference = async (
  client: PortalClient,
  prefix: '/portal/patient' | '/portal/clinician',
  preference: NotificationPreference
): Promise<void> => {
  await client.put(
    `${prefix}/notification-preferences`,
    preference
  );
};
