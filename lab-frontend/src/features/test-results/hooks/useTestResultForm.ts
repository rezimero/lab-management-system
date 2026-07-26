import {
  useEffect,
  useState,
} from 'react';

import {
  loadCreateResultForm,
  loadEditResultForm,
} from '../services/testResultFormLoader';

import {
  saveTestResultForm,
} from '../services/saveTestResultForm';

import {
  apiErrorMessage,
} from '../utils/apiErrorMessage';

import {
  useResultFormFields,
} from './useResultFormFields';

interface Options {
  resultUuid?: string;
  orderUuid: string | null;
  orderItemUuid: string | null;
}

export const useTestResultForm = ({
  resultUuid,
  orderUuid,
  orderItemUuid,
}: Options) => {
  const isEdit = Boolean(resultUuid);
  const fields = useResultFormFields();

  const [
    fetching,
    setFetching,
  ] = useState(true);

  const [
    saving,
    setSaving,
  ] = useState(false);

  const [
    error,
    setError,
  ] = useState<string | null>(null);

  useEffect(() => {
    let active = true;

    const load = async () => {
      setFetching(true);
      setError(null);

      try {
        if (isEdit && resultUuid) {
          const loaded =
            await loadEditResultForm(
              resultUuid
            );

          if (active) {
            fields.replaceState(loaded);
          }

          return;
        }

        if (!orderUuid || !orderItemUuid) {
          if (active) {
            fields.resetState();
            setError(
              'Select a laboratory order item before entering a test result.'
            );
          }

          return;
        }

        const loaded =
          await loadCreateResultForm(
            orderUuid,
            orderItemUuid
          );

        if (active) {
          fields.replaceState(loaded);
        }
      } catch (requestError: unknown) {
        if (active) {
          setError(
            apiErrorMessage(
              requestError,
              isEdit
                ? 'Failed to load the test result.'
                : 'Failed to load the laboratory order.'
            )
          );
        }
      } finally {
        if (active) {
          setFetching(false);
        }
      }
    };

    void load();

    return () => {
      active = false;
    };
  }, [
    isEdit,
    orderItemUuid,
    orderUuid,
    resultUuid,
  ]);

  const save = async (): Promise<
    string | null
  > => {
    if (
      fields.state.blockingMessage ||
      saving
    ) {
      return null;
    }

    if (
      fields.state.rows.some(
        (row) =>
          row.name.trim() === ''
      )
    ) {
      setError(
        'Every result parameter must have a name.'
      );

      return null;
    }

    setSaving(true);
    setError(null);

    try {
      return await saveTestResultForm({
        resultUuid,
        orderItemUuid,
        rows: fields.state.rows,
        notes: fields.state.notes,
      });
    } catch (requestError: unknown) {
      setError(
        apiErrorMessage(
          requestError,
          'Failed to save the test result.'
        )
      );

      return null;
    } finally {
      setSaving(false);
    }
  };

  return {
    ...fields.state,
    isEdit,
    fetching,
    saving,
    error,
    canSave:
      !fetching &&
      !saving &&
      !fields.state.blockingMessage,
    setError,
    setNotes: fields.setNotes,
    updateRow: fields.updateRow,
    addRow: fields.addRow,
    removeRow: fields.removeRow,
    save,
  };
};
