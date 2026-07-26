import type {
  LaboratoryOrderResource,
  LaboratoryOrderSpecimen,
} from '../../features/laboratory-orders/types/laboratoryOrder.types';
import {
  activeItemUuid,
  orderResource,
  pendingItemUuid,
  specimenUuid,
} from './orderFixtures';

export const collectedSpecimen: LaboratoryOrderSpecimen = {
  uuid: specimenUuid,
  accession_number: 'SP-20260724-A1B2C3D4',
  specimen_type: 'Whole Blood',
  container_type: 'EDTA Tube',
  collection_site: 'Left Arm',
  volume: '5.00',
  volume_unit: 'mL',
  status: 'collected',
  collected_at: '2026-07-24T08:30:00.000Z',
  received_at: null,
  rejected_at: null,
  rejection_reason: null,
  notes: null,
  order_item_uuids: [pendingItemUuid, activeItemUuid],
};

export const pendingSpecimenOrder: LaboratoryOrderResource = {
  ...orderResource,
  status: 'pending',
  sample_collected_at: null,
  completed_at: null,
  cancelled_at: null,
  specimens: [],
  items: orderResource.items.map((item) => ({
    ...item,
    status: 'pending',
    processing_started_at: null,
    completed_at: null,
    test_result: null,
  })),
};

export const sampleCollectedOrder: LaboratoryOrderResource = {
  ...pendingSpecimenOrder,
  status: 'sample_collected',
  sample_collected_at: '2026-07-24T08:30:00.000Z',
  specimens: [collectedSpecimen],
};

export const rejectedSpecimen: LaboratoryOrderSpecimen = {
  ...collectedSpecimen,
  status: 'rejected',
  rejected_at: '2026-07-24T08:45:00.000Z',
  rejection_reason: 'Incorrect container',
};

export const rejectedSpecimenOrder: LaboratoryOrderResource = {
  ...sampleCollectedOrder,
  specimens: [rejectedSpecimen],
};
