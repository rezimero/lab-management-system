import {
  useEffect,
  useState,
} from 'react';

import type {
  TestResultResource,
} from '../../../types';

import {
  fetchTestResultResource,
} from '../api/testResultViewApi';

import {
  apiErrorMessage,
} from '../utils/apiErrorMessage';

export const useTestResultResource = (
  uuid?: string
) => {
  const [
    result,
    setResult,
  ] = useState<TestResultResource | null>(
    null
  );

  const [
    loading,
    setLoading,
  ] = useState(true);

  const [
    error,
    setError,
  ] = useState<string | null>(null);

  useEffect(() => {
    let active = true;

    const load = async () => {
      if (!uuid) {
        if (active) {
          setError(
            'A test result UUID was not provided.'
          );
          setLoading(false);
        }

        return;
      }

      setLoading(true);
      setError(null);

      try {
        const loaded =
          await fetchTestResultResource(
            uuid
          );

        if (active) {
          setResult(loaded);
        }
      } catch (requestError: unknown) {
        if (active) {
          setResult(null);
          setError(
            apiErrorMessage(
              requestError,
              'Failed to load test result'
            )
          );
        }
      } finally {
        if (active) {
          setLoading(false);
        }
      }
    };

    void load();

    return () => {
      active = false;
    };
  }, [uuid]);

  return {
    result,
    loading,
    error,
    setResult,
  };
};
