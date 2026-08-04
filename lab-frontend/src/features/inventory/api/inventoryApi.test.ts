import {
  beforeEach,
  describe,
  expect,
  it,
  vi,
} from 'vitest';

import api from '../../../api/client';

import {
  createInventoryAdjustment,
  createInventoryReceipt,
  createOrderItemInventoryConsumption,
  fetchInventoryStock,
  saveLabTestInventoryRequirements,
} from './inventoryApi';

vi.mock('../../../api/client', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
  },
}));

const getMock = vi.mocked(api.get);
const postMock = vi.mocked(api.post);
const putMock = vi.mocked(api.put);

describe('inventory API', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('loads branch-filtered stock', async () => {
    getMock.mockResolvedValue({ data: { data: [] } } as never);

    await fetchInventoryStock('glucose', 'branch-uuid');

    expect(getMock).toHaveBeenCalledWith(
      '/inventory/stock',
      {
        params: expect.objectContaining({
          search: 'glucose',
          branch_uuid: 'branch-uuid',
          per_page: 100,
        }),
      }
    );
  });

  it('posts a lot-tracked stock receipt', async () => {
    postMock.mockResolvedValue({
      data: { data: { uuid: 'receipt-uuid' } },
    } as never);

    await createInventoryReceipt({
      branch_uuid: 'branch-uuid',
      received_at: '2026-08-02T21:00',
      lines: [
        {
          item_uuid: 'item-uuid',
          external_lot_number: 'LOT-100',
          expires_at: '2027-08-02',
          quantity: '12.500000',
          unit_cost: '100.0000',
          currency: 'NGN',
        },
      ],
    });

    expect(postMock).toHaveBeenCalledWith(
      '/inventory/receipts',
      expect.objectContaining({
        branch_uuid: 'branch-uuid',
        lines: [
          expect.objectContaining({
            item_uuid: 'item-uuid',
            quantity: '12.500000',
          }),
        ],
      })
    );
  });

  it('posts an explicit outbound adjustment', async () => {
    postMock.mockResolvedValue({
      data: { data: { uuid: 'adjustment-uuid' } },
    } as never);

    await createInventoryAdjustment({
      branch_uuid: 'branch-uuid',
      reason: 'Damaged vial',
      adjusted_at: '2026-08-02T21:00',
      lines: [
        {
          lot_uuid: 'lot-uuid',
          direction: 'out',
          quantity: '1.000000',
        },
      ],
    });

    expect(postMock).toHaveBeenCalledWith(
      '/inventory/adjustments',
      expect.objectContaining({
        lines: [
          expect.objectContaining({
            direction: 'out',
          }),
        ],
      })
    );
  });

  it('records consumption against an order item', async () => {
    postMock.mockResolvedValue({
      data: { data: { uuid: 'consumption-uuid' } },
    } as never);

    await createOrderItemInventoryConsumption(
      'order-item-uuid',
      {
        consumed_at: '2026-08-02T21:00',
        lines: [
          {
            lot_uuid: 'lot-uuid',
            quantity: '2.000000',
          },
        ],
      }
    );

    expect(postMock).toHaveBeenCalledWith(
      '/laboratory-order-items/order-item-uuid/inventory-consumptions',
      expect.objectContaining({
        lines: [
          expect.objectContaining({
            lot_uuid: 'lot-uuid',
          }),
        ],
      })
    );
  });

  it('saves test requirements as suggestions', async () => {
    putMock.mockResolvedValue({
      data: { data: { requirements: [] } },
    } as never);

    await saveLabTestInventoryRequirements(
      'test-uuid',
      [
        {
          item_uuid: 'item-uuid',
          quantity: '0.500000',
          is_active: true,
        },
      ]
    );

    expect(putMock).toHaveBeenCalledWith(
      '/lab-tests/test-uuid/inventory-requirements',
      {
        requirements: [
          {
            item_uuid: 'item-uuid',
            quantity: '0.500000',
            is_active: true,
          },
        ],
      }
    );
  });
});
