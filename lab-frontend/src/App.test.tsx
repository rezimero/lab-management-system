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

    window.history.pushState(
      {},
      '',
      '/login'
    );
  });

  it('renders the Rezhel sign-in experience', async () => {
    render(<App />);

    expect(
      await screen.findByRole('heading', {
        name: /welcome back/i,
      })
    ).toBeInTheDocument();

    expect(
      screen.getAllByText('Rezhel').length
    ).toBeGreaterThan(0);

    expect(
      screen.getByRole('button', {
        name: /enter laboratory workspace/i,
      })
    ).toBeInTheDocument();

    expect(
      screen.getByRole('textbox', {
        name: /laboratory domain/i,
      })
    ).toBeInTheDocument();

    expect(
      screen.getByRole('textbox', {
        name: /email address/i,
      })
    ).toBeInTheDocument();
  });
});