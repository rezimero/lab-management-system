import { useState } from 'react';
import { createLaboratoryOrder } from '../api/laboratoryOrderCreationApi';
import type {
  OrderCreationState,
  OrderLabTestOption,
  OrderPatientOption,
} from '../types/laboratoryOrderCreation.types';
import type {
  LaboratoryOrderPriority,
} from '../types/laboratoryOrder.types';
import { orderErrorMessage } from '../utils/orderDisplay';

const initialState: OrderCreationState = {
  patient: null,
  tests: [],
  priority: 'routine',
  referringClinician: '',
  clinicalNotes: '',
};

export const useOrderCreation = (
  onCreated: (uuid: string) => void
) => {
  const [state, setState] = useState(initialState);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const patch = (values: Partial<OrderCreationState>) => {
    setState((current) => ({ ...current, ...values }));
    setError(null);
  };

  const submit = async (): Promise<void> => {
    if (!state.patient) {
      setError('Select an active patient before creating the order.');
      return;
    }
    if (state.tests.length === 0) {
      setError('Select at least one active laboratory test.');
      return;
    }

    setSubmitting(true);
    setError(null);
    try {
      const result = await createLaboratoryOrder({
        patient_id: state.patient.id,
        priority: state.priority,
        referring_clinician: state.referringClinician.trim() || undefined,
        clinical_notes: state.clinicalNotes.trim() || undefined,
        items: state.tests.map((test) => ({ lab_test_id: test.id })),
      });
      onCreated(result.order.uuid);
    } catch (requestError: unknown) {
      setError(orderErrorMessage(
        requestError,
        'Failed to create the laboratory order.'
      ));
    } finally {
      setSubmitting(false);
    }
  };

  return {
    state,
    submitting,
    error,
    setPatient: (patient: OrderPatientOption | null) => patch({ patient }),
    setTests: (tests: OrderLabTestOption[]) => patch({ tests }),
    setPriority: (priority: LaboratoryOrderPriority) => patch({ priority }),
    setReferringClinician: (value: string) => patch({ referringClinician: value }),
    setClinicalNotes: (value: string) => patch({ clinicalNotes: value }),
    removeTest: (id: number) => patch({
      tests: state.tests.filter((test) => test.id !== id),
    }),
    submit,
  };
};
