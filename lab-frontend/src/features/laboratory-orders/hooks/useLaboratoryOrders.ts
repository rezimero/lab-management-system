import { useEffect, useState } from 'react';
import { fetchLaboratoryOrders } from '../api/laboratoryOrderApi';
import type {
  LaboratoryOrderPageMeta,
  LaboratoryOrderPriority,
  LaboratoryOrderResource,
  LaboratoryOrderStatus,
} from '../types/laboratoryOrder.types';
import { orderErrorMessage } from '../utils/orderDisplay';

const emptyMeta: LaboratoryOrderPageMeta = {
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
};

export const useLaboratoryOrders = () => {
  const [orders, setOrders] = useState<LaboratoryOrderResource[]>([]);
  const [meta, setMeta] = useState(emptyMeta);
  const [page, setPage] = useState(1);
  const [query, setQuery] = useState('');
  const [status, setStatus] = useState('');
  const [priority, setPriority] = useState('');
  const [search, setSearch] = useState('');
  const [appliedStatus, setAppliedStatus] = useState('');
  const [appliedPriority, setAppliedPriority] = useState('');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let active = true;
    const load = async () => {
      setLoading(true);
      setError(null);
      try {
        const response = await fetchLaboratoryOrders({
          page,
          per_page: 15,
          ...(search ? { search } : {}),
          ...(appliedStatus ? {
            status: appliedStatus as LaboratoryOrderStatus,
          } : {}),
          ...(appliedPriority ? {
            priority: appliedPriority as LaboratoryOrderPriority,
          } : {}),
        });
        if (active) {
          setOrders(Array.isArray(response.data) ? response.data : []);
          setMeta(response.meta ?? emptyMeta);
        }
      } catch (requestError: unknown) {
        if (active) {
          setOrders([]);
          setMeta(emptyMeta);
          setError(orderErrorMessage(
            requestError,
            'Failed to load laboratory orders.'
          ));
        }
      } finally {
        if (active) setLoading(false);
      }
    };
    void load();
    return () => { active = false; };
  }, [page, search, appliedStatus, appliedPriority]);

  const applyFilters = () => {
    setPage(1);
    setSearch(query.trim());
    setAppliedStatus(status);
    setAppliedPriority(priority);
  };

  const clearFilters = () => {
    setPage(1);
    setQuery('');
    setStatus('');
    setPriority('');
    setSearch('');
    setAppliedStatus('');
    setAppliedPriority('');
  };

  return {
    orders, meta, page, query, status, priority,
    loading, error, setPage, setQuery, setStatus,
    setPriority, applyFilters, clearFilters,
  };
};
