import type {
  PatientPortalContext,
  PatientPortalLoginResponse,
  PatientPortalOrder,
  PatientPortalOrderStatus,
  PatientPortalPage,
  PatientPortalReport,
} from '../types/patientPortal.types';

import patientPortalClient from
  './patientPortalClient';

interface WrappedResource<T> {
  data: T;
}

export interface PatientPortalListParams {
  page: number;
  per_page: number;
  search?: string;
}

export interface PatientPortalOrderListParams
  extends PatientPortalListParams {
  status?: PatientPortalOrderStatus;
}

export interface PatientPortalPdfDownload {
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

export const loginPatientPortal = async (
  domain: string,
  email: string,
  password: string
): Promise<PatientPortalLoginResponse> => {
  const response =
    await patientPortalClient
      .post<PatientPortalLoginResponse>(
        '/portal/patient/login',
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

export const fetchPatientPortalMe =
  async (): Promise<PatientPortalContext> => {
    const response =
      await patientPortalClient
        .get<PatientPortalContext>(
          '/portal/patient/me'
        );

    return response.data;
  };

export const logoutPatientPortal =
  async (): Promise<void> => {
    await patientPortalClient.post(
      '/portal/patient/logout'
    );
  };

export const fetchPatientPortalOrders =
  async (
    params: PatientPortalOrderListParams
  ): Promise<
    PatientPortalPage<PatientPortalOrder>
  > => {
    const response =
      await patientPortalClient.get<
        PatientPortalPage<PatientPortalOrder>
      >(
        '/portal/patient/orders',
        { params }
      );

    return response.data;
  };

export const fetchPatientPortalOrder =
  async (
    uuid: string
  ): Promise<PatientPortalOrder> => {
    const response =
      await patientPortalClient.get<
        PatientPortalOrder
        | WrappedResource<PatientPortalOrder>
      >(`/portal/patient/orders/${uuid}`);

    return unwrapResource(response.data);
  };

export const fetchPatientPortalReports =
  async (
    params: PatientPortalListParams
  ): Promise<
    PatientPortalPage<PatientPortalReport>
  > => {
    const response =
      await patientPortalClient.get<
        PatientPortalPage<PatientPortalReport>
      >(
        '/portal/patient/reports',
        { params }
      );

    return response.data;
  };

export const fetchPatientPortalReport =
  async (
    uuid: string
  ): Promise<PatientPortalReport> => {
    const response =
      await patientPortalClient.get<
        PatientPortalReport
        | WrappedResource<PatientPortalReport>
      >(`/portal/patient/reports/${uuid}`);

    return unwrapResource(response.data);
  };

export const downloadPatientPortalReportPdf =
  async (
    uuid: string
  ): Promise<PatientPortalPdfDownload> => {
    const response =
      await patientPortalClient.get<Blob>(
        `/portal/patient/reports/${uuid}/pdf`,
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
