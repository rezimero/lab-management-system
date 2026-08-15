import {
  render,
  screen,
} from '@testing-library/react';

import userEvent from '@testing-library/user-event';
import {
  describe,
  expect,
  it,
  vi,
} from 'vitest';

import NotificationList from './NotificationList';

describe('notification list', () => {
  it('shows critical unread state and keeps actions explicit', async () => {
    const user = userEvent.setup();
    const onOpen = vi.fn();
    const onMarkRead = vi.fn();
    const message = {
      uuid: 'notice-1',
      category: 'critical_result',
      event_key: 'critical_result_detected',
      priority: 'critical' as const,
      title: 'Critical result requires acknowledgement',
      body: 'Open the secure workspace.',
      action_url: '/notifications?critical=record-1',
      data: null,
      read_at: null,
      created_at: '2026-08-13T08:00:00Z',
    };

    render(
      <NotificationList
        messages={[message]}
        loading={false}
        onOpen={onOpen}
        onMarkRead={onMarkRead}
      />
    );

    expect(screen.getByText('Critical')).toBeInTheDocument();
    expect(screen.getByText('New')).toBeInTheDocument();

    await user.click(screen.getByRole('button', {
      name: 'Mark read',
    }));
    expect(onMarkRead).toHaveBeenCalledWith(message);

    await user.click(screen.getByRole('button', {
      name: 'Open',
    }));
    expect(onOpen).toHaveBeenCalledWith(message);
  });

  it('renders an empty state without actions', () => {
    render(
      <NotificationList
        messages={[]}
        loading={false}
        onOpen={vi.fn()}
        onMarkRead={vi.fn()}
      />
    );

    expect(screen.getByText(
      'There are no notifications in this view.'
    )).toBeInTheDocument();
  });
});
