import {
  RESULT_FLAGS,
  type ResultFlag,
  type ResultFormRow,
} from '../types/testResultForm.types';

export const createEmptyRow = (): ResultFormRow => ({
  name: '',
  value: '',
  unit: '',
  reference_range: '',
  flag: '',
  notes: '',
});

const asRecord = (
  value: unknown
): Record<string, unknown> | null => (
  typeof value === 'object' &&
  value !== null &&
  !Array.isArray(value)
    ? value as Record<string, unknown>
    : null
);

const toInputString = (
  value: unknown
): string => (
  typeof value === 'string' ||
  typeof value === 'number'
    ? String(value)
    : ''
);

const firstValue = (
  record: Record<string, unknown>,
  keys: string[]
): unknown => {
  for (const key of keys) {
    if (
      Object.prototype.hasOwnProperty.call(
        record,
        key
      )
    ) {
      return record[key];
    }
  }

  return undefined;
};

const normalizeFlag = (
  value: unknown
): ResultFlag => (
  typeof value === 'string' &&
  RESULT_FLAGS.includes(
    value as Exclude<ResultFlag, ''>
  )
    ? value as Exclude<ResultFlag, ''>
    : ''
);

const normalizeRow = (
  value: unknown,
  index: number
): ResultFormRow => {
  if (typeof value === 'string') {
    return {
      ...createEmptyRow(),
      name: value,
    };
  }

  const record = asRecord(value);

  if (!record) {
    return {
      ...createEmptyRow(),
      name: `Parameter ${index + 1}`,
    };
  }

  return {
    name: toInputString(
      firstValue(
        record,
        ['name', 'parameter_name', 'label']
      )
    ),
    value: toInputString(record.value),
    unit: toInputString(record.unit),
    reference_range: toInputString(
      firstValue(
        record,
        [
          'reference_range',
          'normal_range',
          'range',
        ]
      )
    ),
    flag: normalizeFlag(record.flag),
    notes: toInputString(record.notes),
  };
};

export const normalizeRows = (
  values: unknown
): ResultFormRow[] => {
  if (!Array.isArray(values)) {
    return [createEmptyRow()];
  }

  const rows = values.map(normalizeRow);

  return rows.length > 0
    ? rows
    : [createEmptyRow()];
};
