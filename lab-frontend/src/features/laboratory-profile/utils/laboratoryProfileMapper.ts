import type {
  LaboratoryProfile,
  LaboratoryProfileFormValues,
} from '../types/laboratoryProfile.types';

import {
  DEFAULT_LABORATORY_BRAND_COLORS,
  normaliseLaboratoryBrandColor,
} from './laboratoryBrandColors';

export const EMPTY_PROFILE_FORM:
  LaboratoryProfileFormValues = {
    lab_name: '',
    tagline: '',
    primary_color:
      DEFAULT_LABORATORY_BRAND_COLORS
        .primary_color,
    secondary_color:
      DEFAULT_LABORATORY_BRAND_COLORS
        .secondary_color,
    accent_color:
      DEFAULT_LABORATORY_BRAND_COLORS
        .accent_color,
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
    director_qualification: '',
    director_registration_number: '',
    signatory_designation: '',
    accreditation_details: '',
  };

const textValue = (
  value?: string | null
): string => value ?? '';

export const profileToFormValues = (
  profile: LaboratoryProfile
): LaboratoryProfileFormValues => ({
  lab_name: profile.lab_name,
  tagline: textValue(profile.tagline),
  primary_color:
    normaliseLaboratoryBrandColor(
      profile.primary_color,
      DEFAULT_LABORATORY_BRAND_COLORS
        .primary_color
    ),
  secondary_color:
    normaliseLaboratoryBrandColor(
      profile.secondary_color,
      DEFAULT_LABORATORY_BRAND_COLORS
        .secondary_color
    ),
  accent_color:
    normaliseLaboratoryBrandColor(
      profile.accent_color,
      DEFAULT_LABORATORY_BRAND_COLORS
        .accent_color
    ),
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
  director_qualification:
    textValue(profile.director_qualification),
  director_registration_number:
    textValue(
      profile.director_registration_number
    ),
  signatory_designation:
    textValue(profile.signatory_designation),
  accreditation_details:
    textValue(profile.accreditation_details),
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