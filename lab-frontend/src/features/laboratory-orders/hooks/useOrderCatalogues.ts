import { useEffect, useState } from 'react';
import {
  fetchOrderLabTests,
  fetchOrderPatients,
} from '../api/laboratoryOrderCreationApi';
import type {
  OrderLabTestOption,
  OrderPatientOption,
} from '../types/laboratoryOrderCreation.types';
import { orderErrorMessage } from '../utils/orderDisplay';

export const useOrderCatalogues = (enabled = true) => {
  const [patientSearch, setPatientSearch] = useState('');
  const [testSearch, setTestSearch] = useState('');
  const [patients, setPatients] = useState<OrderPatientOption[]>([]);
  const [tests, setTests] = useState<OrderLabTestOption[]>([]);
  const [patientsLoading, setPatientsLoading] = useState(true);
  const [testsLoading, setTestsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!enabled) {
      setPatientsLoading(false);
      return;
    }
    let active = true;
    const timer = window.setTimeout(async () => {
      setPatientsLoading(true);
      try {
        const next = await fetchOrderPatients(patientSearch);
        if (active) setPatients(next);
      } catch (requestError: unknown) {
        if (active) setError(orderErrorMessage(
          requestError,
          'Failed to load active patients.'
        ));
      } finally {
        if (active) setPatientsLoading(false);
      }
    }, 200);
    return () => {
      active = false;
      window.clearTimeout(timer);
    };
  }, [enabled, patientSearch]);

  useEffect(() => {
    if (!enabled) {
      setTestsLoading(false);
      return;
    }
    let active = true;
    const timer = window.setTimeout(async () => {
      setTestsLoading(true);
      try {
        const next = await fetchOrderLabTests(testSearch);
        if (active) setTests(next);
      } catch (requestError: unknown) {
        if (active) setError(orderErrorMessage(
          requestError,
          'Failed to load active laboratory tests.'
        ));
      } finally {
        if (active) setTestsLoading(false);
      }
    }, 200);
    return () => {
      active = false;
      window.clearTimeout(timer);
    };
  }, [enabled, testSearch]);

  return {
    patientSearch,
    testSearch,
    patients,
    tests,
    patientsLoading,
    testsLoading,
    error,
    setPatientSearch,
    setTestSearch,
    clearError: () => setError(null),
  };
};
