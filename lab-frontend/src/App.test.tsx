import {
  render,
  screen,
} from '@testing-library/react';

import {
  beforeEach,
  describe,
  expect,
  it,
} from 'vitest';

import App from './App';

describe('application bootstrap', () => {
  beforeEach(() => {
    localStorage.clear();
    window.history.pushState({}, '', '/login');
  });

  it('renders the sign-in experience', async () => {
    render(<App />);

    expect(
      await screen.findByRole('heading', {
        name: /lab manager/i,
      })
    ).toBeInTheDocument();

    expect(
      screen.getByRole('button', {
        name: /^sign in$/i,
      })
    ).toBeInTheDocument();
  });
});