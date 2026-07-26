import {
  useEffect,
  useState,
} from 'react';

import api from '../../../api/client';

import type {
  PaginatedResource,
  TestResultResource,
  TestResultStatus,
} from '../../../types';

import {
  apiErrorMessage,
} from '../utils/apiErrorMessage';

export type TestResultStatusFilter =
  '' | TestResultStatus;

export const useTestResultsList = () => {
  const [results, setResults] = useState<TestResultResource[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [page, setPage] = useState(0);
  const [rowsPerPage, setRowsPerPage] = useState(10);
  const [total, setTotal] = useState(0);
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] =
    useState<TestResultStatusFilter>('');

  useEffect(() => {
    let active = true;

    const fetchResults = async () => {
      setLoading(true);
      setError(null);

      try {
        const response = await api.get<
          PaginatedResource<TestResultResource>
        >('/test-results', {
          params: {
            page: page + 1,
            per_page: rowsPerPage,
            search,
            status: statusFilter || undefined,
          },
        });

        if (!active) return;

        setResults(response.data.data ?? []);
        setTotal(response.data.meta?.total ?? 0);
      } catch (requestError: unknown) {
        if (!active) return;

        console.error(
          'Error fetching test results:',
          requestError
        );

        setError(
          apiErrorMessage(
            requestError,
            'Failed to load test results.'
          )
        );
        setResults([]);
        setTotal(0);
      } finally {
        if (active) setLoading(false);
      }
    };

    void fetchResults();

    return () => {
      active = false;
    };
  }, [page, rowsPerPage, search, statusFilter]);

  const changeSearch = (value: string): void => {
    setSearch(value);
    setPage(0);
  };

  const changeStatusFilter = (
    value: TestResultStatusFilter
  ): void => {
    setStatusFilter(value);
    setPage(0);
  };

  const changeRowsPerPage = (value: number): void => {
    setRowsPerPage(value);
    setPage(0);
  };

  return {
    results,
    loading,
    error,
    page,
    rowsPerPage,
    total,
    search,
    statusFilter,
    setPage,
    changeSearch,
    changeStatusFilter,
    changeRowsPerPage,
  };
};