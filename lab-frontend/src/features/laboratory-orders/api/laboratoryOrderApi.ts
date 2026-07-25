import api from '../../../api/client';
import type { LaboratoryReportResource } from '../../laboratory-reports/types/laboratoryReport.types';
import type {
  CollectSpecimensPayload,
  LaboratoryOrderItemResource,
  LaboratoryOrderPage,
  LaboratoryOrderPriority,
  LaboratoryOrderResource,
  LaboratoryOrderSpecimen,
  LaboratoryOrderStatus,
  OrderTestResultPage,
  OrderTestResultSummary,
  ReplaceSpecimenPayload,
  SpecimenStatusPayload,
} from '../types/laboratoryOrder.types';

interface Wrapped<T> {
  data: T;
}

const unwrap = <T>(payload: T | Wrapped<T>): T => (
  typeof payload === 'object'
  && payload !== null
  && 'data' in payload
    ? (payload as Wrapped<T>).data
    : payload as T
);

export interface LaboratoryOrderListParams {
  page: number;
  per_page: number;
  search?: string;
  status?: LaboratoryOrderStatus;
  priority?: LaboratoryOrderPriority;
}

export const fetchLaboratoryOrders = async (
  params: LaboratoryOrderListParams
): Promise<LaboratoryOrderPage> => {
  const response = await api.get<LaboratoryOrderPage>(
    '/laboratory-orders',
    { params }
  );
  return response.data;
};

export const fetchLaboratoryOrder = async (
  uuid: string
): Promise<LaboratoryOrderResource> => {
  const response = await api.get<
    LaboratoryOrderResource
    | Wrapped<LaboratoryOrderResource>
  >(`/laboratory-orders/${uuid}`);
  return unwrap(response.data);
};

export const fetchLaboratoryOrderResults = async (
  uuid: string
): Promise<OrderTestResultSummary[]> => {
  const response = await api.get<OrderTestResultPage>(
    '/test-results',
    {
      params: {
        order_uuid: uuid,
        page: 1,
        per_page: 100,
      },
    }
  );
  return Array.isArray(response.data.data)
    ? response.data.data
    : [];
};

export const collectOrderSpecimens = async (
  uuid: string,
  payload: CollectSpecimensPayload
): Promise<LaboratoryOrderResource> => {
  const response = await api.post<
    LaboratoryOrderResource
    | Wrapped<LaboratoryOrderResource>
  >(`/laboratory-orders/${uuid}/specimens`, payload);
  return unwrap(response.data);
};

export const updateSpecimenStatus = async (
  uuid: string,
  payload: SpecimenStatusPayload
): Promise<LaboratoryOrderSpecimen> => {
  const response = await api.patch<
    LaboratoryOrderSpecimen
    | Wrapped<LaboratoryOrderSpecimen>
  >(`/specimens/${uuid}/status`, payload);
  return unwrap(response.data);
};

export const replaceSpecimen = async (
  uuid: string,
  payload: ReplaceSpecimenPayload
): Promise<LaboratoryOrderSpecimen> => {
  const response = await api.post<
    LaboratoryOrderSpecimen
    | Wrapped<LaboratoryOrderSpecimen>
  >(`/specimens/${uuid}/replacement`, payload);
  return unwrap(response.data);
};

export const startLaboratoryOrderItem = async (
  uuid: string
): Promise<LaboratoryOrderItemResource> => {
  const response = await api.post<
    LaboratoryOrderItemResource
    | Wrapped<LaboratoryOrderItemResource>
  >(`/laboratory-order-items/${uuid}/start`);
  return unwrap(response.data);
};

export const fetchReportForOrder = async (
  uuid: string
): Promise<LaboratoryReportResource> => {
  const response = await api.get<
    LaboratoryReportResource
    | Wrapped<LaboratoryReportResource>
  >(`/laboratory-orders/${uuid}/report`);
  return unwrap(response.data);
};

export const generateReportForOrder = async (
  uuid: string
): Promise<LaboratoryReportResource> => {
  const response = await api.post<
    LaboratoryReportResource
    | Wrapped<LaboratoryReportResource>
  >(`/laboratory-orders/${uuid}/report`);
  return unwrap(response.data);
};
