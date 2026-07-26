import api from '../../../api/client';

import type {
  TestResultResource,
} from '../../../types';

interface WrappedResource {
  data: TestResultResource;
}

const unwrap = (
  payload:
    | TestResultResource
    | WrappedResource
): TestResultResource => (
  'data' in payload
    ? payload.data
    : payload
);

export const fetchTestResultResource = async (
  uuid: string
): Promise<TestResultResource> => {
  const response = await api.get<
    | TestResultResource
    | WrappedResource
  >(`/test-results/${uuid}`);

  return unwrap(response.data);
};

export const submitTestResult = async (
  uuid: string
): Promise<TestResultResource> => {
  const response = await api.post(
    `/test-results/${uuid}/submit`
  );

  return unwrap(response.data);
};

export const approveTestResult = async (
  uuid: string
): Promise<TestResultResource> => {
  const response = await api.post(
    `/test-results/${uuid}/approve`
  );

  return unwrap(response.data);
};

export const rejectTestResult = async (
  uuid: string,
  rejectionReason: string
): Promise<TestResultResource> => {
  const response = await api.post(
    `/test-results/${uuid}/reject`,
    {
      rejection_reason:
        rejectionReason.trim(),
    }
  );

  return unwrap(response.data);
};
