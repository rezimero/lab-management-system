export type BranchType =
  | 'laboratory'
  | 'collection_centre';

export interface BranchSummary {
  uuid: string;
  code: string;
  name: string;
  type: BranchType;
  parent?: {
    uuid: string;
    code: string;
    name: string;
  } | null;
  email?: string | null;
  phone?: string | null;
  city?: string | null;
  state?: string | null;
  country?: string | null;
  is_head_office: boolean;
  is_active: boolean;
  users_count?: number;
  collection_orders_count?: number;
  processing_orders_count?: number;
}

export interface BranchPage {
  data: BranchSummary[];
  meta?: {
    current_page: number;
    last_page: number;
    total: number;
  };
}

export interface BranchPayload {
  code: string;
  name: string;
  type: BranchType;
  parent_branch_uuid?: string;
  city?: string;
  state?: string;
  country?: string;
  is_head_office?: boolean;
  is_active?: boolean;
}

export type InvoiceStatus =
  | 'draft'
  | 'issued'
  | 'partially_paid'
  | 'paid'
  | 'void';

export interface InvoiceSummary {
  uuid: string;
  invoice_number: string;
  status: InvoiceStatus;
  currency: string;
  subtotal: string;
  discount_amount: string;
  tax_amount: string;
  total_amount: string;
  amount_paid: string;
  balance_due: string;
  due_at?: string | null;
  issued_at?: string | null;
  patient?: {
    id: number;
    medical_record_number: string;
    full_name: string;
  } | null;
  order?: {
    uuid: string;
    order_number: string;
    status: string;
  } | null;
  branch?: {
    uuid: string;
    code: string;
    name: string;
  };
  payments_count?: number;
}

export interface InvoicePage {
  data: InvoiceSummary[];
  meta?: {
    current_page: number;
    last_page: number;
    total: number;
  };
}

export interface PaymentReceipt {
  uuid: string;
  payment_number: string;
  receipt_number: string;
  amount: string;
  method: string;
  reference?: string | null;
  paid_at: string;
  invoice: {
    uuid: string;
    invoice_number: string;
    status: InvoiceStatus;
    currency: string;
    total_amount: string;
    amount_paid: string;
    balance_due: string;
  };
}

export interface PlanFeature {
  id: number;
  feature_key: string;
  name: string;
  is_enabled: boolean;
  limit_value?: number | null;
}

export interface CommercialPlan {
  id: number;
  uuid: string;
  code: string;
  name: string;
  description?: string | null;
  currency: string;
  monthly_price: string;
  annual_price: string;
  trial_days: number;
  is_default: boolean;
  is_public: boolean;
  is_active: boolean;
  features: PlanFeature[];
}

export interface TenantSubscriptionSummary {
  id: number;
  uuid: string;
  status: string;
  billing_interval: string;
  starts_at: string;
  trial_ends_at?: string | null;
  current_period_ends_at?: string | null;
  tenant: {
    id: number;
    name: string;
    domain: string;
    is_active: boolean;
  };
  plan: CommercialPlan;
}

export interface SubscriptionPage {
  data: TenantSubscriptionSummary[];
  current_page: number;
  last_page: number;
  total: number;
}

export type CommercialFeatureKey =
  | 'multi_branch'
  | 'billing'
  | 'inventory'
  | 'referral_labs'
  | 'patient_portal'
  | 'clinician_portal'
  | 'notifications'
  | 'analyzer_integration'
  | 'interoperability'
  | 'advanced_analytics';

export interface CommercialPlanPayload {
  code: string;
  name: string;
  description?: string;
  currency: string;
  monthly_price: string;
  annual_price: string;
  trial_days: number;
  is_default: boolean;
  is_public: boolean;
  is_active: boolean;
  sort_order: number;
  features: Array<{
    key: CommercialFeatureKey;
    name: string;
    is_enabled: boolean;
    limit_value?: number | null;
  }>;
}
