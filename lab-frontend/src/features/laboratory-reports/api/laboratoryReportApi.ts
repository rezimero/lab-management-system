import api from '../../../api/client';

import type {
  LaboratoryReportPage,
  LaboratoryReportResource,
} from '../types/laboratoryReport.types';

interface WrappedReport {
  data: LaboratoryReportResource;
}

const unwrapReport = (
  payload:
    | LaboratoryReportResource
    | WrappedReport
): LaboratoryReportResource => (
  'data' in payload
    ? payload.data
    : payload
);

export interface ReportListParams {
  page: number;
  per_page: number;
  search?: string;
  sort: 'generated_at';
  direction: 'desc';
}

export const fetchLaboratoryReports =
  async (
    params: ReportListParams
  ): Promise<LaboratoryReportPage> => {
    const response =
      await api.get<
        LaboratoryReportPage
      >('/laboratory-reports', {
        params,
      });

    return response.data;
  };

export const fetchLaboratoryReport =
  async (
    uuid: string
  ): Promise<LaboratoryReportResource> => {
    const response = await api.get<
      | LaboratoryReportResource
      | WrappedReport
    >(`/laboratory-reports/${uuid}`);

    return unwrapReport(response.data);
  };

export const generateLaboratoryReport =
  async (
    orderUuid: string
  ): Promise<LaboratoryReportResource> => {
    const response = await api.post<
      | LaboratoryReportResource
      | WrappedReport
    >(
      `/laboratory-orders/${orderUuid}/report`
    );

    return unwrapReport(response.data);
  };
