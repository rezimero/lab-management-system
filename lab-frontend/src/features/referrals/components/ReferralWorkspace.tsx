import {
  useState,
} from "react";

import {
  Alert,
  Box,
} from "@mui/material";

import {
  useAuth,
} from "../../../contexts/AuthContext";

import {
  useReferralWorkspace,
} from "../hooks/useReferralWorkspace";

import {
  roleAllowsReferralAction,
} from "../permissions/referralPermissions";

import type {
  ReferralLaboratory,
} from "../types/referral.types";

import CreateReferralDialog from "./CreateReferralDialog";
import ReferralConfigurationPanel from "./ReferralConfigurationPanel";
import ReferralDetailDialog from "./ReferralDetailDialog";
import ReferralLaboratoryFormDialog from "./ReferralLaboratoryFormDialog";
import ReferralTestMappingManagerDialog from "./ReferralTestMappingManagerDialog";
import ReferralList from "./ReferralList";
import ReferralSummaryCards from "./ReferralSummaryCards";

export default function ReferralWorkspace() {
  const data =
    useReferralWorkspace();

  const {
    user,
  } = useAuth();

  const canCreate =
    roleAllowsReferralAction(
      user?.role,
      "create",
    );

  const canDispatchWorkflow =
    roleAllowsReferralAction(
      user?.role,
      "dispatch",
    );

  const canCaptureResults =
    roleAllowsReferralAction(
      user?.role,
      "capture_results",
    );

  const canManageDirectory =
    roleAllowsReferralAction(
      user?.role,
      "manage_directory",
    );

  const [
    createOpen,
    setCreateOpen,
  ] = useState(false);

  const [
    laboratoryDialogOpen,
    setLaboratoryDialogOpen,
  ] = useState(false);

  const [
    editingLaboratory,
    setEditingLaboratory,
  ] = useState<
    ReferralLaboratory | null
  >(null);

  const [
    mappingManagerOpen,
    setMappingManagerOpen,
  ] = useState(false);

  const openCreateReferral =
    () => {
      if (!canCreate) {
        return;
      }

      setCreateOpen(true);

      void data
        .loadOrderItemOptions();
    };

  const closeLaboratoryDialog =
    () => {
      setLaboratoryDialogOpen(
        false,
      );

      setEditingLaboratory(null);
    };

  return (
    <Box
      sx={{
        display: "grid",
        gap: 1.5,
      }}
    >
      {data.error ? (
        <Alert severity="error">
          {data.error}
        </Alert>
      ) : null}

      <ReferralSummaryCards
        summary={data.summary}
      />

      <ReferralList
        referrals={
          data.referrals
        }
        laboratories={
          data.laboratories
        }
        loading={data.loading}
        page={data.page}
        rowsPerPage={
          data.rowsPerPage
        }
        total={data.total}
        onPageChange={
          data.setPage
        }
        onSelect={
          data.openReferral
        }
        onNewReferral={
          canCreate
            ? openCreateReferral
            : undefined
        }
      />

      <ReferralConfigurationPanel
        laboratories={
          data.laboratories
        }
        mappings={data.mappings}
        canManage={
          canManageDirectory
        }
        onSelectLaboratory={
          data.loadMappings
        }
        onCreateLaboratory={
          canManageDirectory
            ? () => {
                setEditingLaboratory(
                  null,
                );

                setLaboratoryDialogOpen(
                  true,
                );
              }
            : undefined
        }
        onEditLaboratory={
          canManageDirectory
            ? (
                laboratory,
              ) => {
                setEditingLaboratory(
                  laboratory,
                );

                setLaboratoryDialogOpen(
                  true,
                );
              }
            : undefined
        }
        onToggleLaboratory={
          canManageDirectory
            ? data.toggleLaboratory
            : undefined
        }
        onManageMappings={
          canManageDirectory
            ? () =>
                setMappingManagerOpen(
                  true,
                )
            : undefined
        }
      />

      {canManageDirectory ? (
        <ReferralTestMappingManagerDialog
          open={mappingManagerOpen}
          laboratories={
            data.laboratories
          }
          onClose={() =>
            setMappingManagerOpen(
              false,
            )
          }
        />
      ) : null}

      {canCreate ? (
        <CreateReferralDialog
          open={createOpen}
          orderItems={
            data.orderItemOptions
          }
          mappings={
            data.createMappingOptions
          }
          loading={
            data.actionLoading
          }
          optionsLoading={
            data.orderOptionsLoading
          }
          mappingsLoading={
            data.createMappingsLoading
          }
          error={
            data.actionError
          }
          onOrderItemChange={
            data.loadCreateReferralMappings
          }
          onClose={() =>
            setCreateOpen(false)
          }
          onSubmit={
            data.createReferral
          }
        />
      ) : null}

      {canManageDirectory ? (
        <ReferralLaboratoryFormDialog
          open={
            laboratoryDialogOpen
          }
          loading={
            data.actionLoading
          }
          error={
            data.actionError
          }
          initial={
            editingLaboratory
              ? {
                  name:
                    editingLaboratory
                      .name,
                  code:
                    editingLaboratory
                      .code,
                  contact_person:
                    editingLaboratory
                      .contact_person,
                  email:
                    editingLaboratory
                      .email,
                  phone:
                    editingLaboratory
                      .phone,
                }
              : {}
          }
          onClose={
            closeLaboratoryDialog
          }
          onSubmit={async (
            payload,
          ) => {
            if (
              editingLaboratory
            ) {
              await data
                .updateLaboratory(
                  editingLaboratory,
                  payload,
                );
            } else {
              await data
                .createLaboratory(
                  payload,
                );
            }
          }}
        />
      ) : null}

      <ReferralDetailDialog
        open={Boolean(
          data.selectedReferral,
        )}
        referral={
          data.selectedReferral
        }
        loading={
          data.actionLoading
        }
        error={
          data.actionError
        }
        canDispatchWorkflow={
          canDispatchWorkflow
        }
        canCaptureResults={
          canCaptureResults
        }
        onClose={
          data.closeReferral
        }
        onDispatch={
          data.dispatchReferral
        }
        onAcknowledge={
          data.acknowledgeReferral
        }
        onReject={
          data.rejectReferral
        }
        onCaptureResult={
          data.captureExternalResult
        }
        onCancel={
          data.cancelReferral
        }
      />
    </Box>
  );
}
