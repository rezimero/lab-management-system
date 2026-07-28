export interface LaboratoryProfile {
  lab_name: string;
  tagline: string | null;

  primary_color: string | null;
  secondary_color: string | null;
  accent_color: string | null;

  email: string | null;
  phone: string | null;
  website: string | null;

  address: string | null;
  city: string | null;
  state: string | null;
  country: string | null;
  full_address: string | null;

  license_number: string | null;
  registration_number: string | null;
  tin: string | null;

  report_footer: string | null;
  director_name: string | null;
  director_qualification: string | null;
  director_registration_number: string | null;
  signatory_designation: string | null;
  accreditation_details: string | null;

  invoice_footer: string | null;
  bank_name: string | null;
  account_name: string | null;
  account_number: string | null;

  logo_url: string | null;
  favicon_url: string | null;
  director_signature_url: string | null;

  created_at: string | null;
  updated_at: string | null;
}

export interface LaboratoryProfileFormValues {
  lab_name: string;
  tagline: string;

  primary_color: string;
  secondary_color: string;
  accent_color: string;

  email: string;
  phone: string;
  website: string;

  address: string;
  city: string;
  state: string;
  country: string;

  license_number: string;
  registration_number: string;

  report_footer: string;
  director_name: string;
  director_qualification: string;
  director_registration_number: string;
  signatory_designation: string;
  accreditation_details: string;
}

export interface LaboratoryProfileEnvelope {
  success: boolean;
  message?: string;
  data: LaboratoryProfile;
}

export type ProfileMediaKind =
  | 'logo'
  | 'director-signature';