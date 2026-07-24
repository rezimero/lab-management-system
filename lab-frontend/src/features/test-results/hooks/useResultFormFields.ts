import {
  useState,
} from 'react';

import {
  createInitialResultForm,
} from '../state/testResultFormInitialState';

import type {
  LoadedResultForm,
  ResultFormRow,
} from '../types/testResultForm.types';

import {
  createEmptyRow,
} from '../utils/resultFormMapper';

export const useResultFormFields = () => {
  const [
    state,
    setState,
  ] = useState<LoadedResultForm>(
    createInitialResultForm
  );

  const replaceState = (
    loaded: LoadedResultForm
  ) => {
    setState(loaded);
  };

  const resetState = () => {
    setState(
      createInitialResultForm()
    );
  };

  const updateRow = (
    index: number,
    field: keyof ResultFormRow,
    value: string
  ) => {
    setState((current) => ({
      ...current,
      rows: current.rows.map(
        (row, rowIndex) =>
          rowIndex === index
            ? {
                ...row,
                [field]: value,
              }
            : row
      ),
    }));
  };

  const addRow = () => {
    setState((current) => ({
      ...current,
      rows: [
        ...current.rows,
        createEmptyRow(),
      ],
    }));
  };

  const removeRow = (
    index: number
  ) => {
    setState((current) => ({
      ...current,
      rows: current.rows.filter(
        (_row, rowIndex) =>
          rowIndex !== index
      ),
    }));
  };

  const setNotes = (
    notes: string
  ) => {
    setState((current) => ({
      ...current,
      notes,
    }));
  };

  return {
    state,
    replaceState,
    resetState,
    updateRow,
    addRow,
    removeRow,
    setNotes,
  };
};
