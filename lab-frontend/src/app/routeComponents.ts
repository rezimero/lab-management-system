import { lazy } from 'react';

export const Dashboard = lazy(
  () => import('../pages/Dashboard')
);

export const Patients = lazy(
  () => import('../pages/Patients')
);

export const PatientForm = lazy(
  () => import('../pages/PatientForm')
);

export const LabTests = lazy(
  () => import('../pages/LabTests')
);

export const LabTestForm = lazy(
  () => import('../pages/LabTestForm')
);

export const LaboratoryOrders = lazy(
  () => import('../pages/LaboratoryOrders')
);

export const LaboratoryOrderCreate = lazy(
  () => import('../pages/LaboratoryOrderCreate')
);

export const LaboratoryOrderView = lazy(
  () => import('../pages/LaboratoryOrderView')
);

export const TestResults = lazy(
  () => import('../pages/TestResults')
);

export const TestResultForm = lazy(
  () => import('../pages/TestResultForm')
);

export const TestResultView = lazy(
  () => import('../pages/TestResultView')
);

export const LaboratoryReports = lazy(
  () => import('../pages/LaboratoryReports')
);

export const LaboratoryReportView = lazy(
  () => import('../pages/LaboratoryReportView')
);

export const LaboratoryProfile = lazy(
  () => import('../pages/LaboratoryProfile')
);

export const Users = lazy(
  () => import('../pages/Users')
);

export const UserForm = lazy(
  () => import('../pages/UserForm')
);

export const LandlordDashboard = lazy(
  () => import('../pages/LandlordDashboard')
);