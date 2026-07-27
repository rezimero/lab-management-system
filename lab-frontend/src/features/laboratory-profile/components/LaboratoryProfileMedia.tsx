import {
  Box,
  Button,
  CircularProgress,
  Paper,
  Typography,
} from '@mui/material';

import {
  ImageRounded,
} from '@mui/icons-material';

import type {
  LaboratoryProfile,
  ProfileMediaKind,
} from '../types/laboratoryProfile.types';

interface MediaItem {
  kind: ProfileMediaKind;
  label: string;
  description: string;
  url: string | null;
  uploadLabel: string;
}

interface Props {
  profile: LaboratoryProfile;
  uploading: ProfileMediaKind | null;
  onUpload: (
    kind: ProfileMediaKind,
    file: File
  ) => void;
}

const LaboratoryProfileMedia = ({
  profile,
  uploading,
  onUpload,
}: Props) => {
  const items: MediaItem[] = [
    {
      kind: 'logo',
      label: 'Laboratory logo',
      description:
        'Displayed in the laboratory profile and available to report templates.',
      url: profile.logo_url,
      uploadLabel: 'Upload laboratory logo',
    },
    {
      kind: 'director-signature',
      label: 'Director signature',
      description:
        'Printed on newly issued laboratory reports as part of the immutable snapshot.',
      url: profile.director_signature_url,
      uploadLabel: 'Upload director signature',
    },
  ];

  return (
    <Paper
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
        Report branding
      </Typography>

      <Typography
        variant="body2"
        color="text.secondary"
        sx={{ mt: 0.5, mb: 2.5 }}
      >
        JPG, PNG or WEBP images up to 2 MB.
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
        {items.map((item) => {
          const busy =
            uploading === item.kind;

          return (
            <Paper
              key={item.kind}
              variant="outlined"
              sx={{
                p: 2,
                borderRadius: 2.5,
                display: 'flex',
                flexDirection: 'column',
                minHeight: 260,
              }}
            >
              <Typography
                variant="subtitle1"
                sx={{ fontWeight: 700 }}
              >
                {item.label}
              </Typography>

              <Typography
                variant="body2"
                color="text.secondary"
                sx={{ mt: 0.5 }}
              >
                {item.description}
              </Typography>

              <Box
                sx={{
                  my: 2,
                  minHeight: 110,
                  borderRadius: 2,
                  bgcolor: 'action.hover',
                  display: 'grid',
                  placeItems: 'center',
                  overflow: 'hidden',
                }}
              >
                {item.url ? (
                  <Box
                    component="img"
                    src={item.url}
                    alt={item.label}
                    sx={{
                      maxWidth: '100%',
                      maxHeight: 110,
                      objectFit: 'contain',
                    }}
                  />
                ) : (
                  <ImageRounded
                    color="disabled"
                    sx={{ fontSize: 46 }}
                  />
                )}
              </Box>

              <Button
                component="label"
                variant="outlined"
                disabled={
                  uploading !== null
                }
                startIcon={
                  busy
                    ? (
                      <CircularProgress
                        size={16}
                      />
                    )
                    : undefined
                }
                sx={{ mt: 'auto' }}
              >
                {busy
                  ? 'Uploading...'
                  : item.uploadLabel}

                <input
                  hidden
                  type="file"
                  accept="image/jpeg,image/png,image/webp"
                  aria-label={item.uploadLabel}
                  onChange={(event) => {
                    const file =
                      event.target.files?.[0];

                    event.target.value = '';

                    if (file) {
                      onUpload(
                        item.kind,
                        file
                      );
                    }
                  }}
                />
              </Button>
            </Paper>
          );
        })}
      </Box>
    </Paper>
  );
};

export default LaboratoryProfileMedia;