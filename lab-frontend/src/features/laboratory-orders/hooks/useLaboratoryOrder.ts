import { useCallback, useEffect, useState } from 'react';
import {
  fetchLaboratoryOrder,
  fetchLaboratoryOrderResults,
} from '../api/laboratoryOrderApi';
import type { LaboratoryOrderResource } from '../types/laboratoryOrder.types';
import { orderErrorMessage } from '../utils/orderDisplay';

export const useLaboratoryOrder = (
  uuid?: string
) => {
  const [order, setOrder] = useState<LaboratoryOrderResource | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [version, setVersion] = useState(0);

  const refresh = useCallback(() => {
    setVersion((current) => current + 1);
  }, []);

  useEffect(() => {
    let active = true;
    const load = async () => {
      if (!uuid) {
        setError('A laboratory order UUID was not provided.');
        setLoading(false);
        return;
      }
      setLoading(true);
      setError(null);
      try {
        const [resource, results] = await Promise.all([
          fetchLaboratoryOrder(uuid),
          fetchLaboratoryOrderResults(uuid),
        ]);
        const resultByItem = new Map(
          results
            .filter((result) => result.order_item?.uuid)
            .map((result) => [result.order_item!.uuid, result])
        );
        if (active) {
          setOrder({
            ...resource,
            items: (resource.items ?? []).map((item) => ({
              ...item,
              test_result: resultByItem.get(item.uuid)
                ?? item.test_result
                ?? null,
            })),
          });
        }
      } catch (requestError: unknown) {
        if (active) {
          setOrder(null);
          setError(orderErrorMessage(
            requestError,
            'Failed to load laboratory order.'
          ));
        }
      } finally {
        if (active) setLoading(false);
      }
    };
    void load();
    return () => { active = false; };
  }, [uuid, version]);

  return { order, loading, error, refresh };
};
