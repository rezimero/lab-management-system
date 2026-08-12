import api from '../../../api/client';

import type {
  ReferringClinicianPage,
  ReferringClinicianPayload,
  ReferringClinicianResource,
} from '../types/referringClinician.types';

interface Wrapped<T> {
  data: T;
  message?: string;
}

export interface ReferringClinicianListParams {
  page?: number;
  per_page?: number;
  search?: string;
  is_active?: 0 | 1;
}

export const fetchReferringClinicians = async (
  params: ReferringClinicianListParams = {}
): Promise<ReferringClinicianPage> => {
  const response = await api.get<ReferringClinicianPage>(
    '/referring-clinicians',
    { params }
  );

  return response.data;
};

export const createReferringClinician = async (
  payload: ReferringClinicianPayload
): Promise<ReferringClinicianResource> => {
  const response = await api.post<
    Wrapped<ReferringClinicianResource>
  >('/referring-clinicians', payload);

  return response.data.data;
};

export const updateReferringClinician = async (
  uuid: string,
  payload: ReferringClinicianPayload
): Promise<ReferringClinicianResource> => {
  const response = await api.put<
    Wrapped<ReferringClinicianResource>
  >(`/referring-clinicians/${uuid}`, payload);

  return response.data.data;
};

export const setReferringClinicianActive = async (
  uuid: string,
  active: boolean
): Promise<ReferringClinicianResource> => {
  const response = await api.patch<
    Wrapped<ReferringClinicianResource>
  >(
    `/referring-clinicians/${uuid}/${
      active ? 'activate' : 'deactivate'
    }`
  );

  return response.data.data;
};
