import type {
  ClinicianPortalContext,
  ClinicianPortalLoginResponse,
  ClinicianPortalOrder,
  ClinicianPortalOrderStatus,
  ClinicianPortalPage,
  ClinicianPortalPatient,
  ClinicianPortalReport,
} from '../types/clinicianPortal.types';

import clinicianPortalClient from
  './clinicianPortalClient';

interface WrappedResource<T> {
  data: T;
}

export interface ClinicianPortalListParams {
  page: number;
  per_page: number;
  search?: string;
}

export interface ClinicianPortalOrderListParams
  extends ClinicianPortalListParams {
  status?: ClinicianPortalOrderStatus;
}

export interface ClinicianPortalPdfDownload {
  blob: Blob;
  contentDisposition?: string;
}

const unwrapResource = <T>(
  payload: T | WrappedResource<T>
): T => (
  typeof payload === 'object'
  && payload !== null
  && 'data' in payload
    ? (payload as WrappedResource<T>).data
    : payload as T
);

export const loginClinicianPortal = async (
  domain: string,
  email: string,
  password: string
): Promise<ClinicianPortalLoginResponse> => {
  const response =
    await clinicianPortalClient
      .post<ClinicianPortalLoginResponse>(
        '/portal/clinician/login',
        {
          domain,
          email,
          password,
        },
        {
          headers: {
            'X-Tenant-Domain': domain,
          },
        }
      );

  return response.data;
};

export const fetchClinicianPortalMe =
  async (): Promise<ClinicianPortalContext> => {
    const response =
      await clinicianPortalClient
        .get<ClinicianPortalContext>(
          '/portal/clinician/me'
        );

    return response.data;
  };

export const logoutClinicianPortal =
  async (): Promise<void> => {
    await clinicianPortalClient.post(
      '/portal/clinician/logout'
    );
  };

export const fetchClinicianPortalPatients =
  async (
    params: ClinicianPortalListParams
  ): Promise<
    ClinicianPortalPage<ClinicianPortalPatient>
  > => {
    const response =
      await clinicianPortalClient.get<
        ClinicianPortalPage<ClinicianPortalPatient>
      >(
        '/portal/clinician/patients',
        { params }
      );

    return response.data;
  };

export const fetchClinicianPortalOrders =
  async (
    params: ClinicianPortalOrderListParams
  ): Promise<
    ClinicianPortalPage<ClinicianPortalOrder>
  > => {
    const response =
      await clinicianPortalClient.get<
        ClinicianPortalPage<ClinicianPortalOrder>
      >(
        '/portal/clinician/orders',
        { params }
      );

    return response.data;
  };

export const fetchClinicianPortalOrder =
  async (
    uuid: string
  ): Promise<ClinicianPortalOrder> => {
    const response =
      await clinicianPortalClient.get<
        ClinicianPortalOrder
        | WrappedResource<ClinicianPortalOrder>
      >(`/portal/clinician/orders/${uuid}`);

    return unwrapResource(response.data);
  };

export const fetchClinicianPortalReports =
  async (
    params: ClinicianPortalListParams
  ): Promise<
    ClinicianPortalPage<ClinicianPortalReport>
  > => {
    const response =
      await clinicianPortalClient.get<
        ClinicianPortalPage<ClinicianPortalReport>
      >(
        '/portal/clinician/reports',
        { params }
      );

    return response.data;
  };

export const fetchClinicianPortalReport =
  async (
    uuid: string
  ): Promise<ClinicianPortalReport> => {
    const response =
      await clinicianPortalClient.get<
        ClinicianPortalReport
        | WrappedResource<ClinicianPortalReport>
      >(`/portal/clinician/reports/${uuid}`);

    return unwrapResource(response.data);
  };

export const downloadClinicianPortalReportPdf =
  async (
    uuid: string
  ): Promise<ClinicianPortalPdfDownload> => {
    const response =
      await clinicianPortalClient.get<Blob>(
        `/portal/clinician/reports/${uuid}/pdf`,
        {
          responseType: 'blob',
        }
      );

    const contentDisposition =
      response.headers['content-disposition'];

    return {
      blob: response.data,
      ...(
        typeof contentDisposition === 'string'
          ? { contentDisposition }
          : {}
      ),
    };
  };
