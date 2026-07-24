import api from '../../../api/client';

import type {
  TestResultResource,
} from '../../../types';

import type {
  LaboratoryOrderDetail,
  ResultFormPayload,
} from '../types/testResultForm.types';

interface WrappedResource<T> {
  data: T;
}

const unwrapResource = <T,>(
  payload: T | WrappedResource<T>
): T => (
  typeof payload === 'object' &&
  payload !== null &&
  'data' in payload
    ? (payload as WrappedResource<T>).data
    : payload as T
);

export const fetchLaboratoryOrder = async (
  orderUuid: string
): Promise<LaboratoryOrderDetail> => {
  const response = await api.get<
    | LaboratoryOrderDetail
    | WrappedResource<LaboratoryOrderDetail>
  >(`/laboratory-orders/${orderUuid}`);

  return unwrapResource(response.data);
};

export const fetchTestResult = async (
  resultUuid: string
): Promise<TestResultResource> => {
  const response = await api.get<
    | TestResultResource
    | WrappedResource<TestResultResource>
  >(`/test-results/${resultUuid}`);

  return unwrapResource(response.data);
};

export const createDraftResult = async (
  orderItemUuid: string,
  payload: ResultFormPayload
): Promise<TestResultResource> => {
  const response = await api.post(
    `/laboratory-order-items/${orderItemUuid}/test-result`,
    payload
  );

  return unwrapResource<TestResultResource>(
    response.data
  );
};

export const updateDraftResult = async (
  resultUuid: string,
  payload: ResultFormPayload
): Promise<TestResultResource> => {
  const response = await api.put(
    `/test-results/${resultUuid}`,
    payload
  );

  return unwrapResource<TestResultResource>(
    response.data
  );
};
