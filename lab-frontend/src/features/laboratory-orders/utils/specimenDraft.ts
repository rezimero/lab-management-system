import type {
  CollectSpecimensPayload,
  SpecimenCollectionInput,
} from '../types/laboratoryOrder.types';

export interface SpecimenDraft {
  key: string;
  specimen_type: string;
  container_type: string;
  collection_site: string;
  volume: string;
  volume_unit: string;
  notes: string;
  order_item_uuids: string[];
}

export const createSpecimenDraft = (
  itemUuids: string[] = []
): SpecimenDraft => ({
  key: `${Date.now()}-${Math.random()}`,
  specimen_type: '',
  container_type: '',
  collection_site: '',
  volume: '',
  volume_unit: '',
  notes: '',
  order_item_uuids: [...itemUuids],
});

const optional = (
  value: string
): string | undefined => {
  const trimmed = value.trim();
  return trimmed || undefined;
};

const toInput = (
  draft: SpecimenDraft
): SpecimenCollectionInput => ({
  specimen_type: draft.specimen_type.trim(),
  ...(optional(draft.container_type) ? {
    container_type: optional(draft.container_type),
  } : {}),
  ...(optional(draft.collection_site) ? {
    collection_site: optional(draft.collection_site),
  } : {}),
  ...(draft.volume.trim() ? {
    volume: Number(draft.volume),
  } : {}),
  ...(optional(draft.volume_unit) ? {
    volume_unit: optional(draft.volume_unit),
  } : {}),
  ...(optional(draft.notes) ? {
    notes: optional(draft.notes),
  } : {}),
  order_item_uuids: [...new Set(draft.order_item_uuids)],
});

export const buildCollectionPayload = (
  drafts: SpecimenDraft[],
  activeItemUuids: string[]
): { payload?: CollectSpecimensPayload; error?: string } => {
  if (drafts.length === 0) {
    return { error: 'At least one specimen is required.' };
  }

  for (const [index, draft] of drafts.entries()) {
    if (!draft.specimen_type.trim()) {
      return { error: `Specimen ${index + 1} requires a specimen type.` };
    }
    if (draft.order_item_uuids.length === 0) {
      return { error: `Specimen ${index + 1} must cover at least one test.` };
    }
    if (draft.volume.trim()) {
      const volume = Number(draft.volume);
      if (!Number.isFinite(volume) || volume <= 0) {
        return { error: `Specimen ${index + 1} volume must be greater than zero.` };
      }
      if (!draft.volume_unit.trim()) {
        return { error: `Specimen ${index + 1} requires a volume unit.` };
      }
    }
  }

  const covered = new Set(
    drafts.flatMap((draft) => draft.order_item_uuids)
  );
  if (activeItemUuids.some((uuid) => !covered.has(uuid))) {
    return { error: 'Every active order item must be assigned to a specimen.' };
  }

  return {
    payload: {
      specimens: drafts.map(toInput),
    },
  };
};
