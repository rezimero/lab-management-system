import api from '../../../api/client';

import type {
  LaboratoryProfile,
  LaboratoryProfileEnvelope,
  LaboratoryProfileFormValues,
  ProfileMediaKind,
} from '../types/laboratoryProfile.types';

const endpointForMedia = (
  kind: ProfileMediaKind
): string => (
  kind === 'logo'
    ? '/tenant/profile/logo'
    : '/tenant/profile/director-signature'
);

export const fetchLaboratoryProfile =
  async (): Promise<LaboratoryProfile> => {
    const response =
      await api.get<LaboratoryProfileEnvelope>(
        '/tenant/profile'
      );

    return response.data.data;
  };

export const updateLaboratoryProfile =
  async (
    payload: LaboratoryProfileFormValues
  ): Promise<LaboratoryProfileEnvelope> => {
    const response =
      await api.put<LaboratoryProfileEnvelope>(
        '/tenant/profile',
        payload
      );

    return response.data;
  };

export const uploadLaboratoryProfileMedia =
  async (
    kind: ProfileMediaKind,
    file: File
  ): Promise<LaboratoryProfileEnvelope> => {
    const payload = new FormData();

    payload.append('file', file);

    const response =
      await api.post<LaboratoryProfileEnvelope>(
        endpointForMedia(kind),
        payload
      );

    return response.data;
  };