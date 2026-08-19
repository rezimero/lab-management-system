import {
  beforeEach,
  describe,
  expect,
  it,
  vi,
} from 'vitest';

import api from '../../../api/client';

import {
  createAnalyzer,
  fetchAnalyzerImports,
  reprocessAnalyzerImport,
  rotateAnalyzerCredential,
  saveAnalyzerParameterMapping,
  saveAnalyzerTestMapping,
} from './analyzerApi';

vi.mock('../../../api/client', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    patch: vi.fn(),
  },
}));

const getMock = vi.mocked(api.get);
const postMock = vi.mocked(api.post);

describe('analyzer API', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('creates an analyzer and exposes its one-time machine token', async () => {
    postMock.mockResolvedValue({
      data: {
        data: {
          uuid: 'analyzer-uuid',
          code: 'HEMA-01',
          name: 'Hematology Analyzer',
        },
        machine_token: 'secret-token',
      },
    } as never);

    const result = await createAnalyzer({
      branch_uuid: 'branch-uuid',
      code: 'HEMA-01',
      name: 'Hematology Analyzer',
    });

    expect(postMock).toHaveBeenCalledWith(
      '/analyzers',
      expect.objectContaining({
        branch_uuid: 'branch-uuid',
      })
    );
    expect(result.machine_token).toBe('secret-token');
  });

  it('rotates analyzer credentials through the dedicated endpoint', async () => {
    postMock.mockResolvedValue({
      data: {
        data: {
          uuid: 'analyzer-uuid',
        },
        machine_token: 'rotated-token',
      },
    } as never);

    const result = await rotateAnalyzerCredential('analyzer-uuid');

    expect(postMock).toHaveBeenCalledWith(
      '/analyzers/analyzer-uuid/credentials/rotate'
    );
    expect(result.machine_token).toBe('rotated-token');
  });

  it('saves test and parameter mappings', async () => {
    postMock.mockResolvedValue({
      data: {
        data: {
          uuid: 'mapping-uuid',
        },
      },
    } as never);

    await saveAnalyzerTestMapping(
      'analyzer-uuid',
      {
        lab_test_uuid: 'lab-test-uuid',
        instrument_test_code: 'CBC',
      }
    );

    await saveAnalyzerParameterMapping(
      'analyzer-uuid',
      'mapping-uuid',
      {
        instrument_parameter_code: 'HGB',
        target_parameter_name: 'Haemoglobin',
      }
    );

    expect(postMock).toHaveBeenNthCalledWith(
      1,
      '/analyzers/analyzer-uuid/test-mappings',
      expect.objectContaining({
        instrument_test_code: 'CBC',
      })
    );

    expect(postMock).toHaveBeenNthCalledWith(
      2,
      '/analyzers/analyzer-uuid/test-mappings/mapping-uuid/parameter-mappings',
      expect.objectContaining({
        instrument_parameter_code: 'HGB',
      })
    );
  });

  it('loads and reprocesses analyzer imports', async () => {
    getMock.mockResolvedValue({
      data: {
        data: [],
        statistics: {
          needs_review: 2,
        },
      },
    } as never);

    postMock.mockResolvedValue({
      data: {
        data: {
          uuid: 'import-uuid',
          status: 'imported',
        },
      },
    } as never);

    const page = await fetchAnalyzerImports('', 'needs_review');
    await reprocessAnalyzerImport('import-uuid');

    expect(getMock).toHaveBeenCalledWith(
      '/analyzer-imports',
      expect.objectContaining({
        params: expect.objectContaining({
          status: 'needs_review',
        }),
      })
    );
    expect(page.statistics?.needs_review).toBe(2);
    expect(postMock).toHaveBeenCalledWith(
      '/analyzer-imports/import-uuid/reprocess'
    );
  });
});
