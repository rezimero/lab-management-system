import api from '../../../api/client';

import type {
  CriticalResultPage,
  NotificationMessage,
  NotificationPage,
  NotificationPreference,
  NotificationPreferences,
} from '../types/notification.types';

interface WrappedResource<T> {
  data: T;
}

export const fetchNotifications = async (
  page = 1,
  unread = false
): Promise<NotificationPage> => {
  const response = await api.get<NotificationPage>(
    '/notifications',
    {
      params: {
        page,
        per_page: 20,
        ...(unread ? { unread: true } : {}),
      },
    }
  );

  return response.data;
};

export const markNotificationRead = async (
  uuid: string
): Promise<NotificationMessage> => {
  const response = await api.patch<
    WrappedResource<NotificationMessage>
  >(`/notifications/${uuid}/read`);

  return response.data.data;
};

export const markAllNotificationsRead = async (): Promise<void> => {
  await api.patch('/notifications/read-all');
};

export const fetchNotificationPreferences = async (
): Promise<NotificationPreferences> => {
  const response = await api.get<NotificationPreferences>(
    '/notifications/preferences/current'
  );

  return response.data;
};

export const updateNotificationPreference = async (
  preference: NotificationPreference
): Promise<void> => {
  await api.put(
    '/notifications/preferences/current',
    preference
  );
};

export const fetchCriticalResultEscalations = async (
  page = 1,
  criticalUuid?: string
): Promise<CriticalResultPage> => {
  const response = await api.get<CriticalResultPage>(
    '/critical-result-escalations',
    {
      params: {
        page,
        per_page: 20,
        ...(criticalUuid ? { critical: criticalUuid } : {}),
      },
    }
  );

  return response.data;
};

export const acknowledgeCriticalResult = async (
  uuid: string,
  note: string
): Promise<void> => {
  await api.post(
    `/critical-result-escalations/${uuid}/acknowledge`,
    { note }
  );
};

export const resolveCriticalResult = async (
  uuid: string,
  note: string
): Promise<void> => {
  await api.post(
    `/critical-result-escalations/${uuid}/resolve`,
    { note }
  );
};
