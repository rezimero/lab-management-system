import type {
  ReactNode,
} from 'react';

import {
  fireEvent,
  screen,
  waitFor,
} from '@testing-library/react';

import {
  beforeEach,
  describe,
  expect,
  it,
  vi,
} from 'vitest';

import api from '../api/client';

import {
  renderEditForm,
} from './test-result-form/TestResultFormHarness';

import {
  resultResource,
  resultUuid,
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
const putMock = vi.mocked(api.put);

describe(
  'TestResultForm edit workflow',
  {
    timeout: 30_000,
  },
  () => {
    beforeEach(() => {
      vi.clearAllMocks();

      putMock.mockResolvedValue({
        data: {
          data: resultResource,
        },
      } as never);
    });

    it(
      'updates a rejected UUID result',
      async () => {
        getMock.mockResolvedValue({
          data: {
            data: {
              ...resultResource,
              status: 'rejected',
              rejection_reason:
                'Repeat the measurement.',
            },
          },
        } as never);

        renderEditForm(resultUuid);

        expect(
          await screen.findByDisplayValue(
            '13.6'
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
              value: '13.9',
            },
          }
        );

        fireEvent.click(
          screen.getByRole(
            'button',
            {
              name: 'Save changes',
            }
          )
        );

        await waitFor(() => {
          expect(putMock)
            .toHaveBeenCalledWith(
              `/test-results/${resultUuid}`,
              expect.objectContaining({
                results_data: [
                  expect.objectContaining({
                    value: '13.9',
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
      'blocks a submitted result',
      async () => {
        getMock.mockResolvedValue({
          data: {
            data: {
              ...resultResource,
              status: 'submitted',
            },
          },
        } as never);

        renderEditForm(resultUuid);

        expect(
          await screen.findByText(
            'A submitted test result cannot be edited.'
          )
        ).toBeInTheDocument();

        expect(
          screen.queryByRole(
            'button',
            {
              name: 'Save changes',
            }
          )
        ).not.toBeInTheDocument();

        expect(putMock)
          .not.toHaveBeenCalled();
      }
    );
  }
);
