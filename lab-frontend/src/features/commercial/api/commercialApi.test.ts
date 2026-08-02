import {
  beforeEach,
  describe,
  expect,
  it,
  vi,
} from 'vitest';

import api from '../../../api/client';

import {
  assignSubscription,
  createBranch,
  createInvoice,
  recordPayment,
  saveCommercialPlan,
} from './commercialApi';

import type {
  CommercialPlanPayload,
} from '../types/commercial.types';

vi.mock('../../../api/client', () => ({
  default: {
    post: vi.fn(),
    put: vi.fn(),
  },
}));

const postMock =
  vi.mocked(api.post);

const putMock =
  vi.mocked(api.put);

describe('commercial platform API', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('creates a collection centre', async () => {
    postMock.mockResolvedValue({
      data: {
        data: {
          uuid: 'branch-uuid',
          code: 'CC-WUSE',
          name: 'Wuse Collection Centre',
          type: 'collection_centre',
          is_head_office: false,
          is_active: true,
        },
      },
    } as never);

    await createBranch({
      code: 'CC-WUSE',
      name: 'Wuse Collection Centre',
      type: 'collection_centre',
      parent_branch_uuid:
        'processing-branch-uuid',
    });

    expect(postMock).toHaveBeenCalledWith(
      '/branches',
      expect.objectContaining({
        code: 'CC-WUSE',
        parent_branch_uuid:
          'processing-branch-uuid',
      })
    );
  });

  it('creates and pays an order invoice', async () => {
    postMock
      .mockResolvedValueOnce({
        data: {
          data: {
            uuid: 'invoice-uuid',
          },
        },
      } as never)
      .mockResolvedValueOnce({
        data: {
          data: {
            uuid: 'payment-uuid',
          },
        },
      } as never);

    await createInvoice(
      'order-uuid',
      '1000.00',
      '0.00'
    );

    await recordPayment(
      'invoice-uuid',
      '5000.00',
      'bank_transfer',
      'BANK-001'
    );

    expect(postMock).toHaveBeenNthCalledWith(
      1,
      '/invoices',
      {
        laboratory_order_uuid:
          'order-uuid',
        discount_amount: '1000.00',
        tax_amount: '0.00',
      }
    );

    expect(postMock).toHaveBeenNthCalledWith(
      2,
      '/invoices/invoice-uuid/payments',
      {
        amount: '5000.00',
        method: 'bank_transfer',
        reference: 'BANK-001',
      }
    );
  });

  it('saves a plan and assigns it to a tenant', async () => {
    const payload:
      CommercialPlanPayload = {
        code: 'growth',
        name: 'Growth',
        currency: 'NGN',
        monthly_price: '50000.00',
        annual_price: '500000.00',
        trial_days: 14,
        is_default: false,
        is_public: true,
        is_active: true,
        sort_order: 10,
        features: [
          {
            key: 'multi_branch',
            name:
              'Multi-branch and collection centres',
            is_enabled: true,
          },
        ],
      };

    postMock.mockResolvedValue({
      data: {
        data: {
          uuid: 'plan-uuid',
        },
      },
    } as never);

    putMock.mockResolvedValue({
      data: {
        data: {
          uuid: 'subscription-uuid',
        },
      },
    } as never);

    await saveCommercialPlan(payload);

    await assignSubscription(
      7,
      'plan-uuid',
      'active',
      'monthly'
    );

    expect(postMock).toHaveBeenCalledWith(
      '/landlord/plans',
      payload
    );

    expect(putMock).toHaveBeenCalledWith(
      '/landlord/tenants/7/subscription',
      {
        plan_uuid: 'plan-uuid',
        status: 'active',
        billing_interval: 'monthly',
      }
    );
  });
});
