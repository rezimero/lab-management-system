import type {
  OrderLabTestOption,
  OrderPatientOption,
  OrderReferringClinicianOption,
} from '../../features/laboratory-orders/types/laboratoryOrderCreation.types';
import { orderResource } from './orderFixtures';

export const orderPatient: OrderPatientOption = {
  id: 4,
  first_name: 'Ada',
  last_name: 'Okafor',
  full_name: 'Ada Okafor',
  medical_record_number: 'MRN-ADA-004',
  is_active: true,
};

export const bloodCountTest: OrderLabTestOption = {
  id: 11,
  name: 'Full Blood Count',
  category: 'Haematology',
  price: '12500.50',
  turnaround_time_hours: 4,
  is_active: true,
};

export const glucoseTest: OrderLabTestOption = {
  id: 12,
  name: 'Fasting Blood Glucose',
  category: 'Chemistry',
  price: '2500.00',
  turnaround_time_hours: 2,
  is_active: true,
};

export const referringClinician: OrderReferringClinicianOption = {
  uuid: '1fceceef-5b23-49c7-9f79-1d42c3413f11',
  name: 'Dr Musa Bello',
  email: 'musa.bello@example.test',
  phone: '+2348000000001',
  organisation: 'City Medical Centre',
  registration_number: 'MDCN-1001',
  specialty: 'General Practice',
  is_active: true,
};

export const patientPage = {
  data: [orderPatient],
  meta: { total: 1 },
};

export const labTestPage = {
  data: [bloodCountTest, glucoseTest],
  meta: { total: 2 },
};

export const referringClinicianPage = {
  data: [referringClinician],
  meta: { total: 1 },
};

export const createdOrderResponse = {
  message: 'Laboratory order created successfully.',
  data: {
    ...orderResource,
    status: 'pending' as const,
    priority: 'urgent' as const,
    items: orderResource.items.slice(0, 2),
  },
};
