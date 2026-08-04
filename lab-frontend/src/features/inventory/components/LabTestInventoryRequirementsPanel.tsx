import {
  useEffect,
  useMemo,
  useState,
} from 'react';

import {
  Alert,
  Button,
  Card,
  CardContent,
  Divider,
  MenuItem,
  Stack,
  TextField,
  Typography,
} from '@mui/material';

import {
  useMutation,
  useQuery,
  useQueryClient,
} from '@tanstack/react-query';

import {
  fetchLabTestInventoryRequirements,
  fetchLabTestsForInventory,
  saveLabTestInventoryRequirements,
} from '../api/inventoryApi';

import type {
  InventoryItem,
} from '../types/inventory.types';

import {
  apiErrorMessage,
} from '../../test-results/utils/apiErrorMessage';

interface Props {
  items: InventoryItem[];
  canManage: boolean;
}

interface RequirementDraft {
  item_uuid: string;
  quantity: string;
  notes: string;
}

const LabTestInventoryRequirementsPanel = ({
  items,
  canManage,
}: Props) => {
  const queryClient = useQueryClient();
  const [testUuid, setTestUuid] = useState('');
  const [drafts, setDrafts] = useState<RequirementDraft[]>([]);
  const [error, setError] = useState<string | null>(null);

  const tests = useQuery({
    queryKey: ['inventory', 'lab-tests'],
    queryFn: fetchLabTestsForInventory,
  });

  const requirements = useQuery({
    queryKey: ['inventory', 'lab-test-requirements', testUuid],
    queryFn: () => fetchLabTestInventoryRequirements(testUuid),
    enabled: Boolean(testUuid),
    retry: false,
  });

  useEffect(() => {
    if (!testUuid && tests.data?.[0]?.uuid) {
      setTestUuid(tests.data[0].uuid);
    }
  }, [testUuid, tests.data]);

  useEffect(() => {
    if (!requirements.data) {
      setDrafts([]);
      return;
    }

    setDrafts(requirements.data.requirements
      .filter((entry) => entry.is_active)
      .map((entry) => ({
        item_uuid: entry.item.uuid,
        quantity: entry.quantity,
        notes: entry.notes ?? '',
      })));
  }, [requirements.data]);

  const availableItems = useMemo(
    () => items.filter((item) => item.is_active),
    [items]
  );

  const nextAvailableItem = useMemo(
    () => availableItems.find(
      (item) => !drafts.some((draft) => draft.item_uuid === item.uuid)
    ),
    [availableItems, drafts]
  );

  const mutation = useMutation({
    mutationFn: () => saveLabTestInventoryRequirements(
      testUuid,
      drafts.map((draft) => ({
        item_uuid: draft.item_uuid,
        quantity: draft.quantity,
        is_active: true,
        notes: draft.notes || undefined,
      }))
    ),
    onSuccess: async () => {
      setError(null);
      await queryClient.invalidateQueries({
        queryKey: ['inventory', 'lab-test-requirements', testUuid],
      });
    },
    onError: (requestError: unknown) => {
      setError(apiErrorMessage(requestError, 'Test usage rules could not be saved.'));
    },
  });

  const updateDraft = (
    index: number,
    patch: Partial<RequirementDraft>
  ): void => {
    setDrafts((current) => current.map(
      (draft, draftIndex) => draftIndex === index
        ? { ...draft, ...patch }
        : draft
    ));
  };

  return (
    <Stack spacing={2}>
      <div>
        <Typography variant="h6" sx={{ fontWeight: 720 }}>Suggested test consumption</Typography>
        <Typography color="text.secondary">
          Configure expected reagent quantities as operator guidance. These rules never post stock automatically.
        </Typography>
      </div>

      {error && <Alert severity="error" onClose={() => setError(null)}>{error}</Alert>}
      {requirements.isError && (
        <Alert severity="error">
          {apiErrorMessage(requirements.error, 'Test consumption rules could not be loaded.')}
        </Alert>
      )}

      <TextField
        select
        label="Laboratory test"
        value={testUuid}
        onChange={(event) => setTestUuid(event.target.value)}
      >
        {(tests.data ?? [])
          .filter((test) => Boolean(test.uuid))
          .map((test) => (
            <MenuItem key={test.uuid} value={test.uuid}>
              {test.name} · {test.category}
            </MenuItem>
          ))}
      </TextField>

      <Card variant="outlined">
        <CardContent>
          <Stack spacing={2} divider={<Divider flexItem />}>
            {drafts.map((draft, index) => (
              <Stack
                key={`${draft.item_uuid}-${index}`}
                direction={{ xs: 'column', md: 'row' }}
               sx={{ alignItems: { md: 'flex-start' }, gap: 2 }}>
                <TextField
                  select
                  label="Inventory item"
                  value={draft.item_uuid}
                  onChange={(event) => updateDraft(index, { item_uuid: event.target.value })}
                  sx={{ flex: 1, minWidth: 260 }}
                  disabled={!canManage}
                >
                  {availableItems.map((item) => (
                    <MenuItem key={item.uuid} value={item.uuid}>
                      {item.sku} · {item.name}
                    </MenuItem>
                  ))}
                </TextField>
                <TextField
                  label="Expected quantity"
                  value={draft.quantity}
                  onChange={(event) => updateDraft(index, { quantity: event.target.value })}
                  sx={{ width: { md: 190 } }}
                  disabled={!canManage}
                />
                <TextField
                  label="Operator note"
                  value={draft.notes}
                  onChange={(event) => updateDraft(index, { notes: event.target.value })}
                  sx={{ flex: 1, minWidth: 220 }}
                  disabled={!canManage}
                />
                {canManage && (
                  <Button color="error" onClick={() => setDrafts((current) => current.filter((_, draftIndex) => draftIndex !== index))}>
                    Remove
                  </Button>
                )}
              </Stack>
            ))}

            {drafts.length === 0 && (
              <Typography color="text.secondary">
                No suggested inventory consumption is configured for this test.
              </Typography>
            )}
          </Stack>
        </CardContent>
      </Card>

      {canManage && (
        <Stack direction="row" sx={{ justifyContent: "flex-end", gap: 1 }}>
          <Button
            variant="outlined"
            disabled={!testUuid || !nextAvailableItem}
            onClick={() => setDrafts((current) => [
              ...current,
              {
                item_uuid: nextAvailableItem?.uuid ?? '',
                quantity: '1',
                notes: '',
              },
            ])}
          >
            Add requirement
          </Button>
          <Button
            variant="contained"
            disabled={!testUuid || mutation.isPending || drafts.some((draft) => !draft.item_uuid || !draft.quantity)}
            onClick={() => mutation.mutate()}
          >
            Save requirements
          </Button>
        </Stack>
      )}
    </Stack>
  );
};

export default LabTestInventoryRequirementsPanel;
