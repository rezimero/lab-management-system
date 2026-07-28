import {
  Alert,
  Box,
  Button,
  CircularProgress,
  Typography,
} from '@mui/material';

import {
  SaveRounded,
} from '@mui/icons-material';

import Layout from '../components/Layout/Layout';

import LaboratoryProfileForm from
  '../features/laboratory-profile/components/LaboratoryProfileForm';

import LaboratoryBrandColors from
  '../features/laboratory-profile/components/LaboratoryBrandColors';

import LaboratoryProfileMedia from
  '../features/laboratory-profile/components/LaboratoryProfileMedia';

import {
  useLaboratoryProfile,
} from '../features/laboratory-profile/hooks/useLaboratoryProfile';

const LaboratoryProfile = () => {
  const profileState =
    useLaboratoryProfile();

  if (profileState.loading) {
    return (
      <Layout title="Laboratory Profile">
        <Box
          sx={{
            minHeight: 320,
            display: 'grid',
            placeItems: 'center',
          }}
        >
          <CircularProgress
            aria-label="Loading laboratory profile"
          />
        </Box>
      </Layout>
    );
  }

  if (!profileState.profile) {
    return (
      <Layout title="Laboratory Profile">
        <Alert severity="error">
          {profileState.error ??
            'The laboratory profile could not be loaded.'}
        </Alert>
      </Layout>
    );
  }

  return (
    <Layout title="Laboratory Profile">
      <Box
        sx={{
          width: '100%',
          maxWidth: 1120,
          mx: 'auto',
          pb: 4,
        }}
      >
        <Typography
          variant="body1"
          color="text.secondary"
          sx={{ mb: 3 }}
        >
          Manage the identity, tenant branding
          and signatory details captured on newly
          issued laboratory reports.
        </Typography>

        {profileState.error && (
          <Alert
            severity="error"
            onClose={profileState.clearError}
            sx={{ mb: 2.5 }}
          >
            {profileState.error}
          </Alert>
        )}

        <Box
          component="form"
          onSubmit={(event) => {
            event.preventDefault();
            void profileState.save();
          }}
        >
          <LaboratoryProfileForm
            values={profileState.form}
            disabled={profileState.saving}
            onChange={profileState.setField}
          />

          <LaboratoryBrandColors
            values={profileState.form}
            disabled={profileState.saving}
            onChange={profileState.setField}
          />

          <Box
            sx={{
              mt: 2.5,
              display: 'flex',
              justifyContent: 'flex-end',
            }}
          >
            <Button
              type="submit"
              variant="contained"
              disabled={
                profileState.saving ||
                !profileState.form.lab_name.trim()
              }
              startIcon={
                profileState.saving
                  ? (
                    <CircularProgress
                      size={16}
                      color="inherit"
                    />
                  )
                  : <SaveRounded />
              }
            >
              {profileState.saving
                ? 'Saving...'
                : 'Save changes'}
            </Button>
          </Box>
        </Box>

        <Box sx={{ mt: 3 }}>
          <LaboratoryProfileMedia
            profile={profileState.profile}
            uploading={profileState.uploading}
            onUpload={(kind, file) => {
              void profileState.upload(
                kind,
                file
              );
            }}
          />
        </Box>
      </Box>
    </Layout>
  );
};

export default LaboratoryProfile;