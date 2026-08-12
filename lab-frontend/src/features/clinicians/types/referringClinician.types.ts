export interface ReferringClinicianResource {
  uuid: string;
  name: string;
  email: string | null;
  phone: string | null;
  organisation: string | null;
  registration_number: string | null;
  specialty: string | null;
  is_active: boolean;
  created_at: string | null;
  updated_at: string | null;
}

export interface ReferringClinicianPage {
  data: ReferringClinicianResource[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from?: number | null;
    to?: number | null;
  };
}

export interface ReferringClinicianPayload {
  name: string;
  email?: string | null;
  phone?: string | null;
  organisation?: string | null;
  registration_number?: string | null;
  specialty?: string | null;
  is_active?: boolean;
}
