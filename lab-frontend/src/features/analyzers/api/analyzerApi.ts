import api from '../../../api/client';

import type {
  AnalyzerCredentialResult,
  AnalyzerEnvelope,
  AnalyzerInstrument,
  AnalyzerInstrumentPayload,
  AnalyzerLabTest,
  AnalyzerPage,
  AnalyzerParameterMapping,
  AnalyzerParameterMappingPayload,
  AnalyzerResultImport,
  AnalyzerTestMapping,
  AnalyzerTestMappingPayload,
} from '../types/analyzer.types';

export const fetchAnalyzers = async (
  search = ''
): Promise<AnalyzerPage<AnalyzerInstrument>> => {
  const response = await api.get<AnalyzerPage<AnalyzerInstrument>>(
    '/analyzers',
    {
      params: {
        page: 1,
        per_page: 100,
        search: search.trim() || undefined,
      },
    }
  );

  return response.data;
};

export const fetchAnalyzer = async (
  uuid: string
): Promise<AnalyzerInstrument> => {
  const response = await api.get<AnalyzerEnvelope<AnalyzerInstrument>>(
    `/analyzers/${uuid}`
  );

  return response.data.data;
};

export const createAnalyzer = async (
  payload: AnalyzerInstrumentPayload
): Promise<AnalyzerCredentialResult> => {
  const response = await api.post<AnalyzerEnvelope<AnalyzerInstrument>>(
    '/analyzers',
    payload
  );

  return {
    instrument: response.data.data,
    machine_token: response.data.machine_token ?? '',
    machine_token_notice: response.data.machine_token_notice,
  };
};

export const updateAnalyzer = async (
  uuid: string,
  payload: AnalyzerInstrumentPayload
): Promise<AnalyzerInstrument> => {
  const response = await api.put<AnalyzerEnvelope<AnalyzerInstrument>>(
    `/analyzers/${uuid}`,
    payload
  );

  return response.data.data;
};

export const deactivateAnalyzer = async (
  uuid: string
): Promise<AnalyzerInstrument> => {
  const response = await api.patch<AnalyzerEnvelope<AnalyzerInstrument>>(
    `/analyzers/${uuid}/deactivate`
  );

  return response.data.data;
};

export const rotateAnalyzerCredential = async (
  uuid: string
): Promise<AnalyzerCredentialResult> => {
  const response = await api.post<AnalyzerEnvelope<AnalyzerInstrument>>(
    `/analyzers/${uuid}/credentials/rotate`
  );

  return {
    instrument: response.data.data,
    machine_token: response.data.machine_token ?? '',
    machine_token_notice: response.data.machine_token_notice,
  };
};

export const fetchAnalyzerTestMappings = async (
  analyzerUuid: string
): Promise<AnalyzerPage<AnalyzerTestMapping>> => {
  const response = await api.get<AnalyzerPage<AnalyzerTestMapping>>(
    `/analyzers/${analyzerUuid}/test-mappings`,
    {
      params: {
        page: 1,
        per_page: 100,
      },
    }
  );

  return response.data;
};

export const saveAnalyzerTestMapping = async (
  analyzerUuid: string,
  payload: AnalyzerTestMappingPayload,
  mappingUuid?: string
): Promise<AnalyzerTestMapping> => {
  const response = mappingUuid
    ? await api.put<AnalyzerEnvelope<AnalyzerTestMapping>>(
        `/analyzers/${analyzerUuid}/test-mappings/${mappingUuid}`,
        payload
      )
    : await api.post<AnalyzerEnvelope<AnalyzerTestMapping>>(
        `/analyzers/${analyzerUuid}/test-mappings`,
        payload
      );

  return response.data.data;
};

export const fetchAnalyzerParameterMappings = async (
  analyzerUuid: string,
  testMappingUuid: string
): Promise<AnalyzerPage<AnalyzerParameterMapping>> => {
  const response = await api.get<AnalyzerPage<AnalyzerParameterMapping>>(
    `/analyzers/${analyzerUuid}/test-mappings/${testMappingUuid}/parameter-mappings`,
    {
      params: {
        page: 1,
        per_page: 100,
      },
    }
  );

  return response.data;
};

export const saveAnalyzerParameterMapping = async (
  analyzerUuid: string,
  testMappingUuid: string,
  payload: AnalyzerParameterMappingPayload,
  mappingUuid?: string
): Promise<AnalyzerParameterMapping> => {
  const url =
    `/analyzers/${analyzerUuid}/test-mappings/${testMappingUuid}/parameter-mappings`;

  const response = mappingUuid
    ? await api.put<AnalyzerEnvelope<AnalyzerParameterMapping>>(
        `${url}/${mappingUuid}`,
        payload
      )
    : await api.post<AnalyzerEnvelope<AnalyzerParameterMapping>>(
        url,
        payload
      );

  return response.data.data;
};

export const fetchAnalyzerImports = async (
  search = '',
  status = ''
): Promise<AnalyzerPage<AnalyzerResultImport>> => {
  const response = await api.get<AnalyzerPage<AnalyzerResultImport>>(
    '/analyzer-imports',
    {
      params: {
        page: 1,
        per_page: 100,
        search: search.trim() || undefined,
        status: status || undefined,
      },
    }
  );

  return response.data;
};

export const fetchAnalyzerImport = async (
  uuid: string
): Promise<AnalyzerResultImport> => {
  const response = await api.get<AnalyzerEnvelope<AnalyzerResultImport>>(
    `/analyzer-imports/${uuid}`
  );

  return response.data.data;
};

export const reprocessAnalyzerImport = async (
  uuid: string
): Promise<AnalyzerResultImport> => {
  const response = await api.post<AnalyzerEnvelope<AnalyzerResultImport>>(
    `/analyzer-imports/${uuid}/reprocess`
  );

  return response.data.data;
};

export const rejectAnalyzerImport = async (
  uuid: string,
  note: string
): Promise<AnalyzerResultImport> => {
  const response = await api.post<AnalyzerEnvelope<AnalyzerResultImport>>(
    `/analyzer-imports/${uuid}/reject`,
    {
      note,
    }
  );

  return response.data.data;
};

export const fetchAnalyzerLabTests = async (): Promise<AnalyzerLabTest[]> => {
  const response = await api.get<{
    data: AnalyzerLabTest[];
  }>(
    '/lab-tests',
    {
      params: {
        page: 1,
        per_page: 100,
      },
    }
  );

  return response.data.data;
};
