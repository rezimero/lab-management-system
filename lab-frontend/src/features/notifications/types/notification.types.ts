export type NotificationPriority =
  | 'normal'
  | 'high'
  | 'critical';

export interface NotificationMessage {
  uuid: string;
  category: string;
  event_key: string;
  priority: NotificationPriority;
  title: string;
  body: string;
  action_url: string | null;
  data: Record<string, unknown> | null;
  read_at: string | null;
  created_at: string | null;
}

export interface PaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface NotificationPage {
  data: NotificationMessage[];
  meta: PaginationMeta;
  unread_count: number;
}

export interface NotificationPreference {
  category: string;
  in_app_enabled: boolean;
  email_enabled: boolean;
}

export interface ChannelCapability {
  enabled: boolean;
  reason: string | null;
}

export interface NotificationPreferences {
  categories: NotificationPreference[];
  channels: {
    in_app: ChannelCapability;
    email: ChannelCapability;
    sms: ChannelCapability;
    whatsapp: ChannelCapability;
  };
  mandatory_categories: string[];
}

export interface CriticalResultEscalation {
  uuid: string;
  test_result_uuid: string;
  order_uuid: string;
  order_number: string | null;
  patient: {
    name: string;
    medical_record_number: string;
  } | null;
  test_name: string | null;
  critical_count: number;
  status: 'open' | 'acknowledged' | 'resolved';
  escalation_level: number;
  detected_at: string | null;
  acknowledgement_due_at: string | null;
  acknowledged_at: string | null;
  acknowledgement_note: string | null;
  resolved_at: string | null;
  resolution_note: string | null;
  last_escalated_at: string | null;
}

export interface CriticalResultPage {
  data: CriticalResultEscalation[];
  meta: PaginationMeta;
}
