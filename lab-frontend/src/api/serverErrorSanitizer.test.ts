import {
  describe,
  expect,
  it,
} from 'vitest';

import {
  SERVER_ERROR_MESSAGE,
  sanitizeServerError,
} from './serverErrorSanitizer';

describe('sanitizeServerError', () => {
  it('replaces a 500 database payload', () => {
    const error = {
      response: {
        status: 500,
        data: {
          message:
            'SQLSTATE[42S02]: table missing',
          exception:
            'Illuminate Database QueryException',
        },
      },
    };

    const result = sanitizeServerError(error);

    expect(result).toBe(error);
    expect(error.response.data).toEqual({
      message: SERVER_ERROR_MESSAGE,
    });

    expect(
      JSON.stringify(error.response.data)
    ).not.toContain('SQLSTATE');
  });

  it('replaces every 5xx payload', () => {
    const error = {
      response: {
        status: 503,
        data: {
          errors: {
            server: [
              'Internal infrastructure details',
            ],
          },
        },
      },
    };

    sanitizeServerError(error);

    expect(error.response.data).toEqual({
      message: SERVER_ERROR_MESSAGE,
    });
  });

  it('preserves useful 422 validation data', () => {
    const data = {
      message: 'The selected test is invalid.',
      errors: {
        test: [
          'The selected test is invalid.',
        ],
      },
    };

    const error = {
      response: {
        status: 422,
        data,
      },
    };

    sanitizeServerError(error);

    expect(error.response.data).toBe(data);
  });

  it('leaves network errors unchanged', () => {
    const error = {
      message: 'Network Error',
    };

    expect(
      sanitizeServerError(error)
    ).toBe(error);
  });
});