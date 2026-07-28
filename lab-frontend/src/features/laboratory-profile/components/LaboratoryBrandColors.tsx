import {
  Box,
  Button,
  Chip,
  Paper,
  TextField,
  Typography,
} from '@mui/material';

import type {
  LaboratoryProfileFormValues,
} from '../types/laboratoryProfile.types';

import {
  DEFAULT_LABORATORY_BRAND_COLORS,
  laboratoryBrandColorError,
  readableTextColor,
  resolveLaboratoryBrandColors,
  type LaboratoryBrandColorKey,
} from '../utils/laboratoryBrandColors';

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

const COLOR_FIELDS: Array<{
  name: LaboratoryBrandColorKey;
  label: string;
  description: string;
}> = [
  {
    name: 'primary_color',
    label: 'Primary colour',
    description:
      'Used for the laboratory name and report identity.',
  },
  {
    name: 'secondary_color',
    label: 'Secondary colour',
    description:
      'Used for supporting brand text and details.',
  },
  {
    name: 'accent_color',
    label: 'Accent colour',
    description:
      'Used sparingly for issued status and highlights.',
  },
];

const LaboratoryBrandColors = ({
  values,
  disabled,
  onChange,
}: Props) => {
  const resolved =
    resolveLaboratoryBrandColors(values);

  const accentText = readableTextColor(
    resolved.accent_color
  );

  return (
    <Paper
      variant="outlined"
      sx={{
        mt: 2.5,
        p: {
          xs: 2,
          md: 3,
        },
        borderRadius: 3,
      }}
    >
      <Box
        sx={{
          display: 'flex',
          flexDirection: {
            xs: 'column',
            sm: 'row',
          },
          justifyContent: 'space-between',
          alignItems: {
            xs: 'flex-start',
            sm: 'center',
          },
          gap: 1.5,
          mb: 2.5,
        }}
      >
        <Box>
          <Typography
            variant="h6"
            sx={{ fontWeight: 750 }}
          >
            Laboratory brand colours
          </Typography>

          <Typography
            variant="body2"
            color="text.secondary"
            sx={{ mt: 0.5 }}
          >
            Configure this subscribing laboratory&apos;s
            identity. Colours are captured on newly
            issued reports.
          </Typography>
        </Box>

        <Button
          type="button"
          variant="text"
          disabled={disabled}
          onClick={() => {
            onChange(
              'primary_color',
              DEFAULT_LABORATORY_BRAND_COLORS
                .primary_color
            );
            onChange(
              'secondary_color',
              DEFAULT_LABORATORY_BRAND_COLORS
                .secondary_color
            );
            onChange(
              'accent_color',
              DEFAULT_LABORATORY_BRAND_COLORS
                .accent_color
            );
          }}
        >
          Use starter palette
        </Button>
      </Box>

      <Box
        sx={{
          display: 'grid',
          gridTemplateColumns: {
            xs: '1fr',
            md: 'repeat(3, minmax(0, 1fr))',
          },
          gap: 2,
        }}
      >
        {COLOR_FIELDS.map((field) => {
          const value = values[field.name];
          const error =
            laboratoryBrandColorError(
              field.name,
              value
            );

          return (
            <Box key={field.name}>
              <Box
                sx={{
                  display: 'flex',
                  alignItems: 'flex-start',
                  gap: 1.25,
                }}
              >
                <Box
                  component="input"
                  type="color"
                  aria-label={`${field.label} picker`}
                  value={resolved[field.name]}
                  disabled={disabled}
                  onChange={(event) => {
                    onChange(
                      field.name,
                      event.target.value
                        .toUpperCase()
                    );
                  }}
                  sx={{
                    width: 48,
                    height: 48,
                    p: 0.25,
                    border: '1px solid',
                    borderColor: 'divider',
                    borderRadius: 1.5,
                    backgroundColor: 'transparent',
                    cursor: disabled
                      ? 'not-allowed'
                      : 'pointer',
                  }}
                />

                <TextField
                  name={field.name}
                  label={field.label}
                  value={value}
                  disabled={disabled}
                  error={Boolean(error)}
                  helperText={
                    error ?? field.description
                  }
                  fullWidth
                  slotProps={{
                    htmlInput: {
                      maxLength: 7,
                    },
                  }}
                  onChange={(event) => {
                    onChange(
                      field.name,
                      event.target.value
                    );
                  }}
                  onBlur={() => {
                    if (
                      value.trim() !== '' &&
                      !error
                    ) {
                      onChange(
                        field.name,
                        value
                          .trim()
                          .toUpperCase()
                      );
                    }
                  }}
                />
              </Box>
            </Box>
          );
        })}
      </Box>

      <Box
        aria-label="Laboratory report brand preview"
        sx={{
          mt: 3,
          p: 2,
          border: '1px solid',
          borderColor: 'divider',
          borderTop: (
            `4px solid ${
              resolved.primary_color
            }`
          ),
          borderRadius: 2,
          backgroundColor: '#FFFFFF',
        }}
      >
        <Box
          sx={{
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'center',
            gap: 2,
          }}
        >
          <Box sx={{ minWidth: 0 }}>
            <Typography
              variant="subtitle1"
              sx={{
                fontWeight: 800,
                color: resolved.primary_color,
              }}
            >
              {values.lab_name.trim() ||
                'Your laboratory'}
            </Typography>

            <Typography
              variant="caption"
              sx={{
                color:
                  resolved.secondary_color,
              }}
            >
              {values.tagline.trim() ||
                'Laboratory report preview'}
            </Typography>
          </Box>

          <Chip
            label="Issued"
            size="small"
            sx={{
              flexShrink: 0,
              fontWeight: 700,
              backgroundColor:
                resolved.accent_color,
              color: accentText,
            }}
          />
        </Box>
      </Box>
    </Paper>
  );
};

export default LaboratoryBrandColors;