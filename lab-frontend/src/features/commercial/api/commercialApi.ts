import api from '../../../api/client';

import type {
  BranchPage,
  BranchPayload,
  BranchSummary,
  CommercialPlan,
  CommercialPlanPayload,
  InvoicePage,
  InvoiceSummary,
  PaymentReceipt,
  SubscriptionPage,
  TenantSubscriptionSummary,
} from '../types/commercial.types';

interface DataEnvelope<T> {
  data: T;
  message?: string;
}

export const fetchBranches = async (): Promise<BranchPage> => {
  const response = await api.get<BranchPage>('/branches', {
    params: {
      page: 1,
      per_page: 100,
    },
  });

  return response.data;
};

export const createBranch = async (
  payload: BranchPayload
): Promise<BranchSummary> => {
  const response = await api.post<DataEnvelope<BranchSummary>>(
    '/branches',
    payload
  );

  return response.data.data;
};

export const fetchInvoices = async (
  search = '',
  status = ''
): Promise<InvoicePage> => {
  const response = await api.get<InvoicePage>('/invoices', {
    params: {
      page: 1,
      per_page: 100,
      search: search.trim() || undefined,
      status: status || undefined,
    },
  });

  return response.data;
};

export const createInvoice = async (
  orderUuid: string,
  discountAmount: string,
  taxAmount: string
): Promise<InvoiceSummary> => {
  const response = await api.post<DataEnvelope<InvoiceSummary>>(
    '/invoices',
    {
      laboratory_order_uuid: orderUuid.trim(),
      discount_amount: discountAmount || '0',
      tax_amount: taxAmount || '0',
    }
  );

  return response.data.data;
};

export const issueInvoice = async (
  invoiceUuid: string
): Promise<InvoiceSummary> => {
  const response = await api.post<DataEnvelope<InvoiceSummary>>(
    `/invoices/${invoiceUuid}/issue`
  );

  return response.data.data;
};

export const recordPayment = async (
  invoiceUuid: string,
  amount: string,
  method: string,
  reference?: string
): Promise<PaymentReceipt> => {
  const response = await api.post<DataEnvelope<PaymentReceipt>>(
    `/invoices/${invoiceUuid}/payments`,
    {
      amount,
      method,
      reference: reference?.trim() || undefined,
    }
  );

  return response.data.data;
};

export const fetchCommercialPlans = async (): Promise<CommercialPlan[]> => {
  const response = await api.get<DataEnvelope<CommercialPlan[]>>(
    '/landlord/plans'
  );

  return response.data.data;
};


export const saveCommercialPlan = async (
  payload: CommercialPlanPayload,
  planUuid?: string
): Promise<CommercialPlan> => {
  const response = planUuid
    ? await api.put<DataEnvelope<CommercialPlan>>(
        `/landlord/plans/${planUuid}`,
        payload
      )
    : await api.post<DataEnvelope<CommercialPlan>>(
        '/landlord/plans',
        payload
      );

  return response.data.data;
};

export const fetchSubscriptions = async (): Promise<SubscriptionPage> => {
  const response = await api.get<SubscriptionPage>(
    '/landlord/subscriptions',
    {
      params: {
        page: 1,
        per_page: 100,
      },
    }
  );

  return response.data;
};

export const assignSubscription = async (
  tenantId: number,
  planUuid: string,
  status: string,
  billingInterval: string
): Promise<TenantSubscriptionSummary> => {
  const response = await api.put<DataEnvelope<TenantSubscriptionSummary>>(
    `/landlord/tenants/${tenantId}/subscription`,
    {
      plan_uuid: planUuid,
      status,
      billing_interval: billingInterval,
    }
  );

  return response.data.data;
};
