import type {
  ReactNode,
} from 'react';

import {
  fireEvent,
  screen,
  waitFor,
} from '@testing-library/react';

import userEvent from '@testing-library/user-event';

import {
  beforeEach,
  describe,
  expect,
  it,
  vi,
} from 'vitest';

import api from '../api/client';

import {
  renderCreateForm,
} from './test-result-form/TestResultFormHarness';

import {
  orderItemUuid,
  orderResource,
  orderUuid,
  resultResource,
} from './test-result-form/testResultFixtures';

vi.mock('../api/client', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
  },
}));

vi.mock('../components/Layout/Layout', () => ({
  default: ({
    children,
    title,
  }: {
    children: ReactNode;
    title: string;
  }) => (
    <main>
      <h1>{title}</h1>
      {children}
    </main>
  ),
}));

const getMock = vi.mocked(api.get);
const postMock = vi.mocked(api.post);

describe(
  'TestResultForm create workflow',
  {
    timeout: 30_000,
  },
  () => {
    beforeEach(() => {
      vi.clearAllMocks();

      postMock.mockResolvedValue({
        data: {
          data: resultResource,
        },
      } as never);
    });

    it(
      'creates a draft from an order item',
      async () => {
        const user = userEvent.setup();

        getMock.mockResolvedValue({
          data: {
            data: orderResource,
          },
        } as never);

        renderCreateForm(
          `/test-results/new?order=${orderUuid}&item=${orderItemUuid}`
        );

        expect(
          await screen.findByRole(
            'heading',
            {
              name: 'Enter Test Result',
            }
          )
        ).toBeInTheDocument();

        fireEvent.change(
          screen.getByRole(
            'textbox',
            {
              name: 'Value',
            }
          ),
          {
            target: {
              value: '17.2',
            },
          }
        );

        await user.click(
          screen.getByRole(
            'combobox',
            {
              name: 'Flag',
            }
          )
        );

        await user.click(
          await screen.findByRole(
            'option',
            {
              name: 'High',
            }
          )
        );

        fireEvent.click(
          screen.getByRole(
            'button',
            {
              name: 'Create draft',
            }
          )
        );

        await waitFor(() => {
          expect(postMock)
            .toHaveBeenCalledWith(
              `/laboratory-order-items/${orderItemUuid}/test-result`,
              expect.objectContaining({
                results_data: [
                  expect.objectContaining({
                    name: 'Haemoglobin',
                    value: '17.2',
                    flag: 'high',
                  }),
                ],
              })
            );
        });

        expect(
          await screen.findByText(
            'Result detail route'
          )
        ).toBeInTheDocument();
      }
    );

    it(
      'requires order-item context',
      async () => {
        renderCreateForm(
          '/test-results/new'
        );

        expect(
          await screen.findByText(
            'Select a laboratory order item before entering a test result.'
          )
        ).toBeInTheDocument();

        expect(getMock)
          .not.toHaveBeenCalled();

        expect(postMock)
          .not.toHaveBeenCalled();
      }
    );
  }
);
