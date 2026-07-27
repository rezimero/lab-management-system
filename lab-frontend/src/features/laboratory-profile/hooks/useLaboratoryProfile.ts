import {
  useEffect,
  useState,
} from 'react';

import toast from 'react-hot-toast';

import {
  fetchLaboratoryProfile,
  updateLaboratoryProfile,
  uploadLaboratoryProfileMedia,
} from '../api/laboratoryProfileApi';

import type {
  LaboratoryProfile,
  LaboratoryProfileFormValues,
  ProfileMediaKind,
} from '../types/laboratoryProfile.types';

import {
  EMPTY_PROFILE_FORM,
  profileImageValidationMessage,
  profileToFormValues,
} from '../utils/laboratoryProfileMapper';

import {
  profileErrorMessage,
} from '../utils/profileErrorMessage';

export const useLaboratoryProfile = () => {
  const [
    profile,
    setProfile,
  ] = useState<LaboratoryProfile | null>(
    null
  );

  const [
    form,
    setForm,
  ] = useState<LaboratoryProfileFormValues>(
    EMPTY_PROFILE_FORM
  );

  const [
    loading,
    setLoading,
  ] = useState(true);

  const [
    saving,
    setSaving,
  ] = useState(false);

  const [
    uploading,
    setUploading,
  ] = useState<ProfileMediaKind | null>(
    null
  );

  const [
    error,
    setError,
  ] = useState<string | null>(null);

  useEffect(() => {
    let active = true;

    const load = async () => {
      setLoading(true);
      setError(null);

      try {
        const loaded =
          await fetchLaboratoryProfile();

        if (active) {
          setProfile(loaded);
          setForm(
            profileToFormValues(loaded)
          );
        }
      } catch (requestError: unknown) {
        if (active) {
          setError(
            profileErrorMessage(
              requestError,
              'Failed to load the laboratory profile.'
            )
          );
        }
      } finally {
        if (active) {
          setLoading(false);
        }
      }
    };

    void load();

    return () => {
      active = false;
    };
  }, []);

  const setField = <
    Key extends keyof LaboratoryProfileFormValues,
  >(
    field: Key,
    value: LaboratoryProfileFormValues[Key]
  ) => {
    setForm((current) => ({
      ...current,
      [field]: value,
    }));
  };

  const applyProfile = (
    updated: LaboratoryProfile
  ) => {
    setProfile(updated);
    setForm(
      profileToFormValues(updated)
    );
  };

  const save = async (): Promise<void> => {
    setSaving(true);
    setError(null);

    try {
      const response =
        await updateLaboratoryProfile(form);

      applyProfile(response.data);

      toast.success(
        response.message ??
          'Laboratory profile updated.'
      );
    } catch (requestError: unknown) {
      setError(
        profileErrorMessage(
          requestError,
          'Failed to update the laboratory profile.'
        )
      );
    } finally {
      setSaving(false);
    }
  };

  const upload = async (
    kind: ProfileMediaKind,
    file: File
  ): Promise<void> => {
    const validationError =
      profileImageValidationMessage(file);

    if (validationError) {
      setError(validationError);
      return;
    }

    setUploading(kind);
    setError(null);

    try {
      const response =
        await uploadLaboratoryProfileMedia(
          kind,
          file
        );

      applyProfile(response.data);

      toast.success(
        response.message ??
          'Profile image uploaded.'
      );
    } catch (requestError: unknown) {
      setError(
        profileErrorMessage(
          requestError,
          'Failed to upload the profile image.'
        )
      );
    } finally {
      setUploading(null);
    }
  };

  return {
    profile,
    form,
    loading,
    saving,
    uploading,
    error,
    setField,
    save,
    upload,
    clearError: () => setError(null),
  };
};