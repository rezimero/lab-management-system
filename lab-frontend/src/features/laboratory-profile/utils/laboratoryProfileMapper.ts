import type {
  LaboratoryProfile,
  LaboratoryProfileFormValues,
} from '../types/laboratoryProfile.types';

export const EMPTY_PROFILE_FORM:
  LaboratoryProfileFormValues = {
    lab_name: '',
    tagline: '',
    email: '',
    phone: '',
    website: '',
    address: '',
    city: '',
    state: '',
    country: '',
    license_number: '',
    registration_number: '',
    report_footer: '',
    director_name: '',
  };

const textValue = (
  value?: string | null
): string => value ?? '';

export const profileToFormValues = (
  profile: LaboratoryProfile
): LaboratoryProfileFormValues => ({
  lab_name: profile.lab_name,
  tagline: textValue(profile.tagline),
  email: textValue(profile.email),
  phone: textValue(profile.phone),
  website: textValue(profile.website),
  address: textValue(profile.address),
  city: textValue(profile.city),
  state: textValue(profile.state),
  country: textValue(profile.country),
  license_number:
    textValue(profile.license_number),
  registration_number:
    textValue(profile.registration_number),
  report_footer:
    textValue(profile.report_footer),
  director_name:
    textValue(profile.director_name),
});

export const profileImageValidationMessage = (
  file: File
): string | null => {
  const allowedTypes = new Set([
    'image/jpeg',
    'image/png',
    'image/webp',
  ]);

  if (!allowedTypes.has(file.type)) {
    return (
      'Only JPG, PNG and WEBP images are allowed.'
    );
  }

  if (file.size > 2 * 1024 * 1024) {
    return (
      'The image may not be larger than 2 MB.'
    );
  }

  return null;
};