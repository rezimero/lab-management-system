import api from '../../../api/client';
import type {
  CreateLaboratoryOrderPayload,
  OrderCreationResult,
  OrderLabTestOption,
  OrderPatientOption,
  OrderReferringClinicianOption,
} from '../types/laboratoryOrderCreation.types';
import type {
  LaboratoryOrderResource,
} from '../types/laboratoryOrder.types';

interface DataEnvelope<T> {
  data: T;
  message?: string;
}

const collection = <T>(payload: unknown): T[] => {
  if (Array.isArray(payload)) return payload as T[];
  if (
    typeof payload === 'object'
    && payload !== null
    && 'data' in payload
    && Array.isArray((payload as { data?: unknown }).data)
  ) {
    return (payload as { data: T[] }).data;
  }
  return [];
};

const activeOnly = <T extends { is_active?: boolean }>(
  values: T[]
): T[] => values.filter((value) => value.is_active !== false);

export const fetchOrderPatients = async (
  search: string
): Promise<OrderPatientOption[]> => {
  const response = await api.get('/patients', {
    params: {
      page: 1,
      per_page: 50,
      search: search.trim() || undefined,
    },
  });
  return activeOnly(collection<OrderPatientOption>(response.data));
};

export const fetchOrderReferringClinicians = async (
  search: string
): Promise<OrderReferringClinicianOption[]> => {
  const response = await api.get(
    '/referring-clinicians',
    {
      params: {
        page: 1,
        per_page: 50,
        search:
          search.trim() || undefined,
        is_active: 1,
      },
    }
  );

  return activeOnly(
    collection<OrderReferringClinicianOption>(
      response.data
    )
  );
};

export const fetchOrderLabTests = async (
  search: string
): Promise<OrderLabTestOption[]> => {
  const response = await api.get('/lab-tests', {
    params: {
      page: 1,
      per_page: 50,
      search: search.trim() || undefined,
    },
  });
  return activeOnly(collection<OrderLabTestOption>(response.data));
};

export const createLaboratoryOrder = async (
  payload: CreateLaboratoryOrderPayload
): Promise<OrderCreationResult> => {
  const response = await api.post<
    DataEnvelope<LaboratoryOrderResource>
  >('/laboratory-orders', payload);
  return {
    order: response.data.data,
    message: response.data.message,
  };
};
