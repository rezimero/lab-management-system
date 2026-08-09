import {
  useCallback,
  useEffect,
  useState,
} from "react";

import {
  fetchLaboratoryOrder,
  fetchLaboratoryOrders,
} from "../../laboratory-orders/api/laboratoryOrderApi";

import {
  referralApi,
} from "../api/referralApi";

import type {
  CaptureReferralResultPayload,
  CreateReferralPayload,
  Referral,
  ReferralLaboratory,
  ReferralCreateMappingOption,
  ReferralLaboratoryPayload,
  ReferralOrderItemOption,
  ReferralReasonPayload,
  ReferralSummary,
} from "../types/referral.types";

const emptySummary: ReferralSummary = {
  total: 0,
  draft: 0,
  dispatched: 0,
  acknowledged: 0,
  awaiting_result: 0,
  result_received: 0,
  completed: 0,
  rejected: 0,
  cancelled: 0,
  overdue: 0,
};

const errorMessage = (
  error: unknown,
  fallback: string,
): string => {
  if (
    typeof error === "object" &&
    error !== null &&
    "response" in error
  ) {
    const response = (
      error as {
        response?: {
          data?: {
            message?: unknown;
          };
        };
      }
    ).response;

    const message =
      response?.data?.message;

    if (
      typeof message === "string" &&
      message.trim()
    ) {
      return message;
    }
  }

  if (
    error instanceof Error &&
    error.message.trim()
  ) {
    return error.message;
  }

  return fallback;
};

export function useReferralWorkspace() {
  const [
    referrals,
    setReferrals,
  ] = useState<Referral[]>([]);

  const [
    summary,
    setSummary,
  ] = useState<ReferralSummary>({
    ...emptySummary,
  });

  const [
    selectedReferral,
    setSelectedReferral,
  ] = useState<Referral | null>(
    null,
  );

  const [
    laboratories,
    setLaboratories,
  ] = useState<
    ReferralLaboratory[]
  >([]);

  const [
    mappings,
    setMappings,
  ] = useState<any[]>([]);

  const [
    orderItemOptions,
    setOrderItemOptions,
  ] = useState<
    ReferralOrderItemOption[]
  >([]);

  const [
    createMappingOptions,
    setCreateMappingOptions,
  ] = useState<
    ReferralCreateMappingOption[]
  >([]);

  const [
    createMappingsLoading,
    setCreateMappingsLoading,
  ] = useState(false);

  const [
    loading,
    setLoading,
  ] = useState(true);

  const [
    actionLoading,
    setActionLoading,
  ] = useState(false);

  const [
    orderOptionsLoading,
    setOrderOptionsLoading,
  ] = useState(false);

  const [
    error,
    setError,
  ] = useState<string | null>(
    null,
  );

  const [
    actionError,
    setActionError,
  ] = useState<string | null>(
    null,
  );

  const [
    page,
    setPage,
  ] = useState(0);

  const [rowsPerPage] =
    useState(10);

  const refresh = useCallback(
    async () => {
      setLoading(true);
      setError(null);

      try {
        const [
          referralValues,
          laboratoryValues,
          serverSummary,
        ] = await Promise.all([
          referralApi.getAllReferrals(),
          referralApi.getLaboratories({
            page: 1,
            per_page: 100,
          }),
          referralApi.getReferralSummary(),
        ]);

        setReferrals(
          referralValues,
        );

        setLaboratories(
          laboratoryValues,
        );

        setSummary({
          ...serverSummary,
          awaiting_result:
            (serverSummary.dispatched ?? 0) +
            (serverSummary.acknowledged ?? 0),
        });

        setPage((current) => {
          const lastPage = Math.max(
            0,
            Math.ceil(
              referralValues.length /
                rowsPerPage,
            ) - 1,
          );

          return Math.min(
            current,
            lastPage,
          );
        });
      } catch (
        requestError: unknown
      ) {
        setReferrals([]);
        setLaboratories([]);
        setSummary({
          ...emptySummary,
        });

        setError(
          errorMessage(
            requestError,
            "Failed to load referral workspace data.",
          ),
        );
      } finally {
        setLoading(false);
      }
    },
    [rowsPerPage],
  );

  useEffect(() => {
    void refresh();
  }, [refresh]);

  const openReferral =
    useCallback(
      async (uuid: string) => {
        const local =
          referrals.find(
            (referral) =>
              referral.uuid === uuid,
          );

        if (local) {
          setSelectedReferral(
            local,
          );
        }

        setActionError(null);

        try {
          const referral =
            await referralApi.getReferral(
              uuid,
            );

          setSelectedReferral(
            referral,
          );
        } catch (
          requestError: unknown
        ) {
          if (!local) {
            setActionError(
              errorMessage(
                requestError,
                "Failed to open referral details.",
              ),
            );
          }
        }
      },
      [referrals],
    );

  const closeReferral = () => {
    setSelectedReferral(null);
    setActionError(null);
  };

  const loadMappings = async (
    laboratory?:
      ReferralLaboratory,
  ) => {
    if (!laboratory) {
      setMappings([]);
      return;
    }

    setMappings(
      Array.isArray(
        laboratory.test_mappings,
      )
        ? laboratory.test_mappings
        : [],
    );
  };

  const loadOrderItemOptions =
    useCallback(async () => {
      setOrderOptionsLoading(true);
      setActionError(null);
      setCreateMappingOptions([]);

      try {
        const firstPage =
          await fetchLaboratoryOrders({
            page: 1,
            per_page: 100,
          });

        const orders = [
          ...(firstPage.data ?? []),
        ];

        const lastPage =
          firstPage.meta?.last_page ??
          1;

        for (
          let nextPage = 2;
          nextPage <= lastPage;
          nextPage += 1
        ) {
          const response =
            await fetchLaboratoryOrders({
              page: nextPage,
              per_page: 100,
            });

          orders.push(
            ...(response.data ?? []),
          );
        }

        const options =
          orders.flatMap(
            (
              order,
            ): ReferralOrderItemOption[] => {
              if (
                order.status !==
                "in_progress"
              ) {
                return [];
              }

              return (
                order.items ?? []
              )
                .filter((item) => {
                  const hasOpenReferral =
                    referrals.some(
                      (referral) =>
                        referral.order_item
                          ?.uuid ===
                          item.uuid &&
                        ![
                          "completed",
                          "cancelled",
                          "rejected",
                        ].includes(
                          String(
                            referral.status ??
                              "",
                          ).toLowerCase(),
                        ),
                    );

                  return (
                    item.status ===
                      "in_progress" &&
                    !item.test_result &&
                    !hasOpenReferral
                  );
                })
                .map((item) => {
                  const specimens =
                    (
                      order.specimens ??
                      []
                    )
                      .filter(
                        (specimen) =>
                          specimen.status ===
                            "received" &&
                          (
                            specimen
                              .order_item_uuids ??
                            []
                          ).includes(
                            item.uuid,
                          ),
                      )
                      .map(
                        (specimen) => ({
                          uuid:
                            specimen.uuid,
                          accession_number:
                            specimen
                              .accession_number,
                          specimen_type:
                            specimen
                              .specimen_type,
                          container_type:
                            specimen
                              .container_type,
                          status:
                            specimen.status,
                        }),
                      );

                  return {
                    uuid: item.uuid,
                    order_uuid:
                      order.uuid,
                    order_number:
                      order.order_number,
                    order_status:
                      order.status,
                    patient_name:
                      order.patient
                        ?.full_name ??
                      (
                        [
                          order.patient
                            ?.first_name,
                          order.patient
                            ?.last_name,
                        ]
                          .filter(
                            Boolean,
                          )
                          .join(" ") ||
                        "Unknown patient"
                      ),
                    medical_record_number:
                      order.patient
                        ?.medical_record_number,
                    test_name:
                      item.test_name ??
                      "Laboratory test",
                    status:
                      item.status,
                    lab_test_id:
                      item.lab_test_id,
                    specimens,
                  };
                });
            },
          );

        setOrderItemOptions(
          options,
        );
      } catch (
        requestError: unknown
      ) {
        setOrderItemOptions([]);

        setActionError(
          errorMessage(
            requestError,
            "Failed to load referral-ready laboratory order items.",
          ),
        );
      } finally {
        setOrderOptionsLoading(
          false,
        );
      }
    }, [referrals]);

  const loadCreateReferralMappings =
    useCallback(
      async (
        orderItemUuid: string,
      ) => {
        setCreateMappingOptions([]);
        setActionError(null);

        if (!orderItemUuid) {
          return;
        }

        const option =
          orderItemOptions.find(
            (item) =>
              item.uuid ===
              orderItemUuid,
          );

        if (!option) {
          setActionError(
            "The selected laboratory order item is no longer available.",
          );
          return;
        }

        setCreateMappingsLoading(
          true,
        );

        try {
          const order =
            await fetchLaboratoryOrder(
              option.order_uuid,
            );

          const refreshedItem =
            order.items.find(
              (item) =>
                item.uuid ===
                orderItemUuid,
            );

          if (
            !refreshedItem ||
            order.status !==
              "in_progress" ||
            refreshedItem.status !==
              "in_progress" ||
            refreshedItem.test_result
          ) {
            throw new Error(
              "This order item is no longer eligible for referral. Refresh the referral workspace and try again.",
            );
          }

          const refreshedSpecimens =
            (
              order.specimens ?? []
            )
              .filter(
                (specimen) =>
                  specimen.status ===
                    "received" &&
                  (
                    specimen
                      .order_item_uuids ??
                    []
                  ).includes(
                    orderItemUuid,
                  ),
              )
              .map(
                (specimen) => ({
                  uuid:
                    specimen.uuid,
                  accession_number:
                    specimen
                      .accession_number,
                  specimen_type:
                    specimen
                      .specimen_type,
                  container_type:
                    specimen
                      .container_type,
                  status:
                    specimen.status,
                }),
              );

          setOrderItemOptions(
            (current) =>
              current.map(
                (item) =>
                  item.uuid ===
                  orderItemUuid
                    ? {
                        ...item,
                        specimens:
                          refreshedSpecimens,
                      }
                    : item,
              ),
          );

          if (
            refreshedSpecimens.length ===
            0
          ) {
            setActionError(
              "This order item has no received specimen available for referral.",
            );
            return;
          }

          const labTests =
            await referralApi
              .getLabTests(
                option.test_name,
              );

          const localTest =
            labTests.find(
              (test) =>
                option.lab_test_id !=
                  null &&
                test.id != null &&
                Number(test.id) ===
                  Number(
                    option.lab_test_id,
                  ),
            ) ??
            labTests.find(
              (test) =>
                test.name
                  .trim()
                  .toLowerCase() ===
                option.test_name
                  .trim()
                  .toLowerCase(),
            );

          if (!localTest?.uuid) {
            setActionError(
              "The local laboratory test for this order item could not be resolved to a referral mapping.",
            );
            return;
          }

          const activeLaboratories =
            laboratories.filter(
              (laboratory) =>
                laboratory.is_active,
            );

          const nextMappings:
            ReferralCreateMappingOption[] =
            [];

          for (
            const laboratory of
            activeLaboratories
          ) {
            const mappings =
              await referralApi
                .getLaboratoryTestMappings(
                  laboratory.uuid,
                  {
                    page: 1,
                    per_page: 100,
                    active: true,
                    lab_test_uuid:
                      localTest.uuid,
                  },
                );

            for (
              const mapping of
              mappings
            ) {
              if (
                mapping.is_active ===
                false
              ) {
                continue;
              }

              nextMappings.push({
                uuid:
                  mapping.uuid,
                laboratory_uuid:
                  laboratory.uuid,
                laboratory_name:
                  laboratory.name,
                laboratory_code:
                  laboratory.code,
                external_test_code:
                  mapping
                    .external_test_code,
                external_test_name:
                  mapping
                    .external_test_name,
                cost:
                  mapping.cost,
                currency:
                  mapping.currency,
                turnaround_hours:
                  mapping
                    .turnaround_hours,
                specimen_requirements:
                  mapping
                    .specimen_requirements,
              });
            }
          }

          setCreateMappingOptions(
            nextMappings,
          );

          if (
            nextMappings.length ===
            0
          ) {
            setActionError(
              "No active external laboratory mapping is configured for this test.",
            );
          }
        } catch (
          requestError: unknown
        ) {
          setCreateMappingOptions([]);

          setActionError(
            errorMessage(
              requestError,
              "Failed to load eligible referral test mappings.",
            ),
          );
        } finally {
          setCreateMappingsLoading(
            false,
          );
        }
      },
      [
        orderItemOptions,
        laboratories,
      ],
    );

  const createReferral = async (
    payload:
      CreateReferralPayload,
  ) => {
    setActionLoading(true);
    setActionError(null);

    try {
      const created =
        await referralApi.createReferral(
          payload,
        );

      await refresh();

      if (created?.uuid) {
        try {
          const detailed =
            await referralApi.getReferral(
              created.uuid,
            );

          setSelectedReferral(
            detailed,
          );

          return detailed;
        } catch {
          setSelectedReferral(
            created,
          );
        }
      }

      return created;
    } catch (
      requestError: unknown
    ) {
      setActionError(
        errorMessage(
          requestError,
          "Failed to create referral.",
        ),
      );

      throw requestError;
    } finally {
      setActionLoading(false);
    }
  };

  const createLaboratory =
    async (
      payload:
        ReferralLaboratoryPayload,
    ) => {
      setActionLoading(true);
      setActionError(null);

      try {
        await referralApi
          .createLaboratory(
            payload,
          );

        await refresh();
      } catch (
        requestError: unknown
      ) {
        setActionError(
          errorMessage(
            requestError,
            "Failed to create referral laboratory.",
          ),
        );

        throw requestError;
      } finally {
        setActionLoading(false);
      }
    };

  const updateLaboratory =
    async (
      laboratory:
        ReferralLaboratory,
      payload:
        ReferralLaboratoryPayload,
    ) => {
      setActionLoading(true);
      setActionError(null);

      try {
        await referralApi
          .updateLaboratory(
            laboratory.uuid,
            payload,
          );

        await refresh();
      } catch (
        requestError: unknown
      ) {
        setActionError(
          errorMessage(
            requestError,
            "Failed to update referral laboratory.",
          ),
        );

        throw requestError;
      } finally {
        setActionLoading(false);
      }
    };

  const toggleLaboratory =
    async (
      laboratory:
        ReferralLaboratory,
    ) => {
      setActionLoading(true);
      setActionError(null);

      try {
        await referralApi
          .updateLaboratory(
            laboratory.uuid,
            {
              is_active:
                !laboratory
                  .is_active,
            },
          );

        await refresh();
      } catch (
        requestError: unknown
      ) {
        setActionError(
          errorMessage(
            requestError,
            "Failed to change laboratory status.",
          ),
        );
      } finally {
        setActionLoading(false);
      }
    };

  const captureExternalResult =
    async (
      payload:
        CaptureReferralResultPayload,
    ) => {
      if (
        !selectedReferral?.uuid
      ) {
        throw new Error(
          "Select a referral before capturing an external result.",
        );
      }

      setActionLoading(true);
      setActionError(null);

      try {
        const updated =
          await referralApi
            .captureExternalResult(
              selectedReferral.uuid,
              payload,
            );

        await refresh();

        try {
          const detailed =
            await referralApi.getReferral(
              updated.uuid ??
                selectedReferral.uuid,
            );

          setSelectedReferral(
            detailed,
          );

          return detailed;
        } catch {
          setSelectedReferral(
            updated,
          );

          return updated;
        }
      } catch (
        requestError: unknown
      ) {
        setActionError(
          errorMessage(
            requestError,
            "Failed to capture the external referral result.",
          ),
        );

        throw requestError;
      } finally {
        setActionLoading(false);
      }
    };

  const hydrateSelectedReferral =
    async (
      updated: Referral,
      fallbackUuid: string,
    ) => {
      await refresh();

      try {
        const detailed =
          await referralApi.getReferral(
            updated.uuid ??
              fallbackUuid,
          );

        setSelectedReferral(
          detailed,
        );

        return detailed;
      } catch {
        setSelectedReferral(
          updated,
        );

        return updated;
      }
    };

  const runReferralAction =
    async (
      action:
        | "dispatch"
        | "acknowledge",
    ) => {
      if (
        !selectedReferral?.uuid
      ) {
        return;
      }

      const referralUuid =
        selectedReferral.uuid;

      setActionLoading(true);
      setActionError(null);

      try {
        const updated =
          action === "dispatch"
            ? await referralApi
                .dispatchReferral(
                  referralUuid,
                )
            : await referralApi
                .acknowledgeReferral(
                  referralUuid,
                );

        return await hydrateSelectedReferral(
          updated,
          referralUuid,
        );
      } catch (
        requestError: unknown
      ) {
        setActionError(
          errorMessage(
            requestError,
            `Failed to ${action} referral.`,
          ),
        );

        throw requestError;
      } finally {
        setActionLoading(false);
      }
    };

  const runReasonAction =
    async (
      action:
        | "reject"
        | "cancel",
      payload:
        ReferralReasonPayload,
    ) => {
      if (
        !selectedReferral?.uuid
      ) {
        throw new Error(
          "Select a referral before performing this action.",
        );
      }

      const referralUuid =
        selectedReferral.uuid;

      setActionLoading(true);
      setActionError(null);

      try {
        const updated =
          action === "reject"
            ? await referralApi
                .rejectReferral(
                  referralUuid,
                  payload,
                )
            : await referralApi
                .cancelReferral(
                  referralUuid,
                  payload,
                );

        return await hydrateSelectedReferral(
          updated,
          referralUuid,
        );
      } catch (
        requestError: unknown
      ) {
        setActionError(
          errorMessage(
            requestError,
            `Failed to ${action} referral.`,
          ),
        );

        throw requestError;
      } finally {
        setActionLoading(false);
      }
    };

  return {
    referrals,
    selectedReferral,
    summary,
    laboratories,
    mappings,
    orderItemOptions,
    createMappingOptions,
    loading,
    actionLoading,
    orderOptionsLoading,
    createMappingsLoading,
    error,
    actionError,
    page,
    rowsPerPage,
    total: referrals.length,
    setPage,
    openReferral,
    closeReferral,
    loadMappings,
    loadOrderItemOptions,
    loadCreateReferralMappings,
    refresh,
    createReferral,
    createLaboratory,
    updateLaboratory,
    toggleLaboratory,
    captureExternalResult,
    dispatchReferral: () =>
      runReferralAction(
        "dispatch",
      ),
    acknowledgeReferral: () =>
      runReferralAction(
        "acknowledge",
      ),
    rejectReferral: (
      reason: string,
    ) =>
      runReasonAction(
        "reject",
        { reason },
      ),
    cancelReferral: (
      reason: string,
    ) =>
      runReasonAction(
        "cancel",
        { reason },
      ),
  };
}
