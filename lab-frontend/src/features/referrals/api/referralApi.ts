import api from "../../../api/client";
import type {
  CaptureReferralResultPayload,
  CreateReferralPayload,
  Referral,
  ReferralLaboratory,
  ReferralLaboratoryPayload,
  ReferralLabTestOption,
  ReferralPage,
  ReferralPageMeta,
  ReferralReasonPayload,
  ReferralSummary,
  ReferralTestMapping,
  ReferralTestMappingPayload,
} from "../types/referral.types";

interface Wrapped<T> {
  data: T;
}

const unwrap = <T>(payload: T | Wrapped<T>): T => (
  typeof payload === "object" &&
  payload !== null &&
  "data" in payload &&
  !Array.isArray(payload)
    ? (payload as Wrapped<T>).data
    : payload as T
);

const arrayFrom = <T>(payload: unknown): T[] => {
  if (Array.isArray(payload)) {
    return payload as T[];
  }

  if (
    typeof payload === "object" &&
    payload !== null &&
    "data" in payload &&
    Array.isArray((payload as { data?: unknown }).data)
  ) {
    return (payload as { data: T[] }).data;
  }

  return [];
};

const pageFrom = (
  payload: unknown,
  count: number,
): ReferralPageMeta => {
  if (
    typeof payload !== "object" ||
    payload === null
  ) {
    return {
      current_page: 1,
      last_page: 1,
      per_page: count,
      total: count,
    };
  }

  const value =
    payload as Record<string, unknown>;

  const nestedMeta =
    typeof value.meta === "object" &&
    value.meta !== null
      ? value.meta as Record<string, unknown>
      : null;

  const numberValue = (
    source: Record<string, unknown> | null,
    key: string,
    fallback: number,
  ) => {
    const raw = source?.[key];

    return (
      typeof raw === "number" &&
      Number.isFinite(raw)
    )
      ? raw
      : fallback;
  };

  return {
    current_page:
      numberValue(
        nestedMeta,
        "current_page",
        0,
      ) ||
      numberValue(
        value,
        "current_page",
        1,
      ),
    last_page:
      numberValue(
        nestedMeta,
        "last_page",
        0,
      ) ||
      numberValue(
        value,
        "last_page",
        1,
      ),
    per_page:
      numberValue(
        nestedMeta,
        "per_page",
        0,
      ) ||
      numberValue(
        value,
        "per_page",
        count,
      ),
    total:
      numberValue(
        nestedMeta,
        "total",
        0,
      ) ||
      numberValue(
        value,
        "total",
        count,
      ),
  };
};

export interface ReferralListParams {
  page?: number;
  per_page?: number;
  search?: string;
  status?: string;
  laboratory_uuid?: string;
  branch_uuid?: string;
  order_item_uuid?: string;
  date_from?: string;
  date_to?: string;
  overdue?: boolean;
}

const getReferrals = async (
  params?: ReferralListParams,
): Promise<ReferralPage> => {
  const response = await api.get(
    "/referrals",
    {
      params:
        normaliseReferralFilters(
          params,
        ),
    },
  );

  const values =
    arrayFrom<Referral>(response.data);

  return {
    data: values,
    meta: pageFrom(
      response.data,
      values.length,
    ),
  };
};



const normaliseReferralFilters = (
  params?: ReferralListParams,
): Record<string, unknown> => ({
  ...(params ?? {}),
  overdue:
    typeof params?.overdue === "boolean"
      ? params.overdue
        ? 1
        : 0
      : params?.overdue,
});

const getReferralSummary = async (
  params?: ReferralListParams,
): Promise<ReferralSummary> => {
  const response = await api.get(
    "/referrals/summary",
    {
      params:
        normaliseReferralFilters(
          params,
        ),
    },
  );

  return unwrap<ReferralSummary>(
    response.data,
  );
};

export const referralApi = {
  getReferrals,
  getReferralSummary,

  async getAllReferrals(): Promise<Referral[]> {
    const first = await getReferrals({
      page: 1,
      per_page: 100,
    });

    const referrals = [...first.data];
    const lastPage = Math.max(
      1,
      first.meta.last_page,
    );

    const perPage =
      first.meta.per_page || 100;

    const batchSize = 4;

    for (
      let firstPage = 2;
      firstPage <= lastPage;
      firstPage += batchSize
    ) {
      const pages = Array.from(
        {
          length: Math.min(
            batchSize,
            lastPage - firstPage + 1,
          ),
        },
        (_, index) =>
          firstPage + index,
      );

      const responses =
        await Promise.all(
          pages.map((page) =>
            getReferrals({
              page,
              per_page: perPage,
            }),
          ),
        );

      responses.forEach(
        (response) => {
          referrals.push(
            ...response.data,
          );
        },
      );
    }

    return referrals;
  },

  async getReferral(
    uuid: string,
  ): Promise<Referral> {
    const response = await api.get<
      Referral | Wrapped<Referral>
    >(`/referrals/${uuid}`);

    return unwrap(response.data);
  },

  async getLaboratories(
    params?: Record<string, unknown>,
  ): Promise<ReferralLaboratory[]> {
    const normalizedParams = {
      ...(params ?? {}),
      per_page:
        typeof params?.per_page === "number"
          ? Math.min(
              Math.max(
                Math.trunc(
                  params.per_page,
                ),
                1,
              ),
              100,
            )
          : params?.per_page,
    };

    const response = await api.get(
      "/referrals/laboratories",
      {
        params: normalizedParams,
      },
    );

    return arrayFrom<ReferralLaboratory>(
      response.data,
    );
  },

  async createLaboratory(
    payload: ReferralLaboratoryPayload,
  ): Promise<ReferralLaboratory> {
    const response = await api.post<
      ReferralLaboratory |
      Wrapped<ReferralLaboratory>
    >(
      "/referrals/laboratories",
      payload,
    );

    return unwrap(response.data);
  },

  async updateLaboratory(
    uuid: string,
    payload: Partial<
      ReferralLaboratoryPayload & {
        is_active: boolean;
      }
    >,
  ): Promise<ReferralLaboratory> {
    const response = await api.put<
      ReferralLaboratory |
      Wrapped<ReferralLaboratory>
    >(
      `/referrals/laboratories/${uuid}`,
      payload,
    );

    return unwrap(response.data);
  },

async getLaboratoryTestMappings(
  laboratoryUuid: string,
  params?: {
    page?: number;
    per_page?: number;
    search?: string;
    active?: boolean;
    lab_test_uuid?: string;
  },
): Promise<ReferralTestMapping[]> {
  const normalizedParams = {
    ...(params ?? {}),
    per_page:
      typeof params?.per_page === "number"
        ? Math.min(
            Math.max(
              Math.trunc(params.per_page),
              1,
            ),
            100,
          )
        : params?.per_page,
    active:
      typeof params?.active === "boolean"
        ? params.active
          ? 1
          : 0
        : undefined,
  };

  const response = await api.get(
    `/referrals/laboratories/${laboratoryUuid}/tests`,
    {
      params: normalizedParams,
    },
  );

  return arrayFrom<ReferralTestMapping>(
    response.data,
  );
},

async createLaboratoryTestMapping(
  laboratoryUuid: string,
  payload: ReferralTestMappingPayload,
): Promise<ReferralTestMapping> {
  const response = await api.post<
    ReferralTestMapping |
    Wrapped<ReferralTestMapping>
  >(
    `/referrals/laboratories/${laboratoryUuid}/tests`,
    payload,
  );

  return unwrap(response.data);
},

async updateLaboratoryTestMapping(
  mappingUuid: string,
  payload: ReferralTestMappingPayload,
): Promise<ReferralTestMapping> {
  const response = await api.put<
    ReferralTestMapping |
    Wrapped<ReferralTestMapping>
  >(
    `/referrals/laboratory-tests/${mappingUuid}`,
    payload,
  );

  return unwrap(response.data);
},

async getLabTests(
  search = "",
): Promise<ReferralLabTestOption[]> {
  const response = await api.get(
    "/lab-tests",
    {
      params: {
        page: 1,
        per_page: 100,
        search,
      },
    },
  );

  return arrayFrom<ReferralLabTestOption>(
    response.data,
  ).filter(
    (test) =>
      Boolean(test.uuid) &&
      test.is_active !== false,
  );
},

  async createReferral(
    payload: CreateReferralPayload,
  ): Promise<Referral> {
    const response = await api.post<
      Referral | Wrapped<Referral>
    >(
      "/referrals",
      payload,
    );

    return unwrap(response.data);
  },

  async dispatchReferral(
    uuid: string,
  ): Promise<Referral> {
    const response = await api.post<
      Referral | Wrapped<Referral>
    >(
      `/referrals/${uuid}/dispatch`,
    );

    return unwrap(response.data);
  },

  async acknowledgeReferral(
    uuid: string,
  ): Promise<Referral> {
    const response = await api.post<
      Referral | Wrapped<Referral>
    >(
      `/referrals/${uuid}/acknowledge`,
    );

    return unwrap(response.data);
  },

  async rejectReferral(
    uuid: string,
    payload: ReferralReasonPayload,
  ): Promise<Referral> {
    const response = await api.post<
      Referral | Wrapped<Referral>
    >(
      `/referrals/${uuid}/reject`,
      payload,
    );

    return unwrap(response.data);
  },

  async cancelReferral(
    uuid: string,
    payload: ReferralReasonPayload,
  ): Promise<Referral> {
    const response = await api.post<
      Referral | Wrapped<Referral>
    >(
      `/referrals/${uuid}/cancel`,
      payload,
    );

    return unwrap(response.data);
  },

  async captureExternalResult(
    uuid: string,
    payload: CaptureReferralResultPayload,
  ): Promise<Referral> {
    const response = await api.post<
      Referral | Wrapped<Referral>
    >(
      `/referrals/${uuid}/results`,
      payload,
    );

    return unwrap(response.data);
  },
};
