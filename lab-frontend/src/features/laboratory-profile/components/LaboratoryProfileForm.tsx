import {
  Box,
  Paper,
  TextField,
  Typography,
} from '@mui/material';

import type {
  LaboratoryProfileFormValues,
} from '../types/laboratoryProfile.types';

interface FieldDefinition {
  name: keyof LaboratoryProfileFormValues;
  label: string;
  type?: 'email' | 'tel' | 'url';
  required?: boolean;
  multiline?: boolean;
  fullWidth?: boolean;
  helperText?: string;
}

interface FieldGroup {
  title: string;
  description: string;
  fields: FieldDefinition[];
}

const GROUPS: FieldGroup[] = [
  {
    title: 'Laboratory identity',
    description:
      'The name and summary shown across official laboratory records.',
    fields: [
      {
        name: 'lab_name',
        label: 'Laboratory name',
        required: true,
      },
      {
        name: 'tagline',
        label: 'Tagline',
      },
    ],
  },
  {
    title: 'Contact and location',
    description:
      'Contact details printed on laboratory reports.',
    fields: [
      {
        name: 'email',
        label: 'Email address',
        type: 'email',
      },
      {
        name: 'phone',
        label: 'Phone number',
        type: 'tel',
      },
      {
        name: 'website',
        label: 'Website',
        type: 'url',
      },
      {
        name: 'address',
        label: 'Street address',
        multiline: true,
        fullWidth: true,
      },
      {
        name: 'city',
        label: 'City',
      },
      {
        name: 'state',
        label: 'State',
      },
      {
        name: 'country',
        label: 'Country',
      },
    ],
  },
  {
    title: 'Report signatory',
    description:
      'Director and registration details captured when a report is issued.',
    fields: [
      {
        name: 'director_name',
        label: 'Laboratory director',
      },
      {
        name: 'license_number',
        label: 'Laboratory licence number',
      },
      {
        name: 'registration_number',
        label: 'Registration number',
      },
      {
        name: 'report_footer',
        label: 'Report footer',
        multiline: true,
        fullWidth: true,
        helperText:
          'Use this for accreditation, interpretation or legal notices.',
      },
    ],
  },
];

interface Props {
  values: LaboratoryProfileFormValues;
  disabled: boolean;
  onChange: <
    Key extends keyof LaboratoryProfileFormValues,
  >(
    field: Key,
    value: LaboratoryProfileFormValues[Key]
  ) => void;
}

const LaboratoryProfileForm = ({
  values,
  disabled,
  onChange,
}: Props) => (
  <Box
    sx={{
      display: 'grid',
      gap: 2.5,
    }}
  >
    {GROUPS.map((group) => (
      <Paper
        key={group.title}
        variant="outlined"
        sx={{
          p: {
            xs: 2,
            md: 3,
          },
          borderRadius: 3,
        }}
      >
        <Typography
          variant="h6"
          sx={{ fontWeight: 750 }}
        >
          {group.title}
        </Typography>

        <Typography
          variant="body2"
          color="text.secondary"
          sx={{ mt: 0.5, mb: 2.5 }}
        >
          {group.description}
        </Typography>

        <Box
          sx={{
            display: 'grid',
            gridTemplateColumns: {
              xs: '1fr',
              md: 'repeat(2, minmax(0, 1fr))',
            },
            gap: 2,
          }}
        >
          {group.fields.map((field) => (
            <TextField
              key={field.name}
              name={field.name}
              label={field.label}
              value={values[field.name]}
              type={field.type}
              required={field.required}
              multiline={field.multiline}
              minRows={
                field.multiline
                  ? 3
                  : undefined
              }
              helperText={field.helperText}
              disabled={disabled}
              fullWidth
              onChange={(event) => {
                onChange(
                  field.name,
                  event.target.value
                );
              }}
              sx={
                field.fullWidth
                  ? {
                      gridColumn: {
                        md: '1 / -1',
                      },
                    }
                  : undefined
              }
            />
          ))}
        </Box>
      </Paper>
    ))}
  </Box>
);

export default LaboratoryProfileForm;