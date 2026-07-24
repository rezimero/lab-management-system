import {
  useEffect,
  useState,
} from 'react';

import {
  fetchLaboratoryReports,
} from '../api/laboratoryReportApi';

import type {
  LaboratoryReportResource,
  ReportPageMeta,
} from '../types/laboratoryReport.types';

import {
  reportErrorMessage,
} from '../utils/reportDisplay';

const emptyMeta: ReportPageMeta = {
  current_page: 1,
  last_page: 1,
  per_page: 25,
  total: 0,
};

export const useLaboratoryReports = () => {
  const [
    reports,
    setReports,
  ] = useState<
    LaboratoryReportResource[]
  >([]);

  const [
    meta,
    setMeta,
  ] = useState(emptyMeta);

  const [
    page,
    setPage,
  ] = useState(1);

  const [
    query,
    setQuery,
  ] = useState('');

  const [
    search,
    setSearch,
  ] = useState('');

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
      setLoading(true);
      setError(null);

      try {
        const response =
          await fetchLaboratoryReports({
            page,
            per_page: 25,
            sort: 'generated_at',
            direction: 'desc',
            ...(search
              ? { search }
              : {}),
          });

        if (active) {
          setReports(
            Array.isArray(response.data)
              ? response.data
              : []
          );
          setMeta(
            response.meta ??
            emptyMeta
          );
        }
      } catch (requestError: unknown) {
        if (active) {
          setReports([]);
          setMeta(emptyMeta);
          setError(
            reportErrorMessage(
              requestError,
              'Failed to load laboratory reports.'
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
  }, [page, search]);

  const applySearch = () => {
    setPage(1);
    setSearch(query.trim());
  };

  return {
    reports,
    meta,
    page,
    query,
    search,
    loading,
    error,
    setPage,
    setQuery,
    applySearch,
  };
};
