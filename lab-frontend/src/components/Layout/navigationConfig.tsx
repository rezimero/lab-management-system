import type { ReactNode } from 'react';

import AssignmentRounded from '@mui/icons-material/AssignmentRounded';
import AccountBalanceWalletRounded from '@mui/icons-material/AccountBalanceWalletRounded';
import BusinessRounded from '@mui/icons-material/BusinessRounded';
import DashboardRounded from '@mui/icons-material/DashboardRounded';
import DescriptionRounded from '@mui/icons-material/DescriptionRounded';
import FactCheckRounded from '@mui/icons-material/FactCheckRounded';
import GroupsRounded from '@mui/icons-material/GroupsRounded';
import HubRounded from '@mui/icons-material/HubRounded';
import PeopleAltRounded from '@mui/icons-material/PeopleAltRounded';
import ScienceRounded from '@mui/icons-material/ScienceRounded';
import Inventory2Rounded from '@mui/icons-material/Inventory2Rounded';
import MedicalServicesRounded from '@mui/icons-material/MedicalServicesRounded';
import VpnKeyRounded from '@mui/icons-material/VpnKeyRounded';
import NotificationsActiveRounded from '@mui/icons-material/NotificationsActiveRounded';
import PrecisionManufacturingRounded from '@mui/icons-material/PrecisionManufacturingRounded';
import RuleRounded from '@mui/icons-material/RuleRounded';

export interface NavigationItem {
  label: string;
  path: string;
  icon: ReactNode;
}

export interface NavigationGroup {
  label: string;
  items: NavigationItem[];
}

export const buildNavigationGroups = (
  canManageUsers: boolean,
  role?: string,
  inventoryEnabled = false,
  analyzerEnabled = false
): NavigationGroup[] => {
  const canViewInventory = inventoryEnabled && [
    'superadmin',
    'admin',
    'lab_technician',
  ].includes(role ?? '');

  const canViewBilling = [
    'superadmin',
    'admin',
    'receptionist',
    'doctor',
  ].includes(role ?? '');

  const canViewAnalyzers = analyzerEnabled && [
    'superadmin',
    'admin',
    'lab_technician',
    'doctor',
  ].includes(role ?? '');

  const groups: NavigationGroup[] = [
    {
      label: 'Workspace',
      items: [
        {
          label: 'Overview',
          path: '/dashboard',
          icon: <DashboardRounded />,
        },
        {
          label: 'Notifications',
          path: '/notifications',
          icon: <NotificationsActiveRounded />,
        },
      ],
    },
    {
      label: 'Laboratory operations',
      items: [
        {
          label: 'Patients',
          path: '/patients',
          icon: <PeopleAltRounded />,
        },
        {
          label: 'Laboratory orders',
          path: '/laboratory-orders',
          icon: <AssignmentRounded />,
        },
        {
          label: 'Test catalogue',
          path: '/lab-tests',
          icon: <ScienceRounded />,
        },
        {
          label: 'Test results',
          path: '/test-results',
          icon: <FactCheckRounded />,
        },
        {
          label: 'Laboratory reports',
          path: '/laboratory-reports',
          icon: <DescriptionRounded />,
        },
        {
          label: 'Referrals',
          path: '/referrals',
          icon: <AssignmentRounded />,
        },
        ...(canViewAnalyzers
          ? [
              {
                label: 'Analyzer instruments',
                path: '/analyzers',
                icon: <PrecisionManufacturingRounded />,
              },
              {
                label: 'Analyzer validation',
                path: '/analyzer-imports',
                icon: <RuleRounded />,
              },
            ]
          : []),
      ],
    },
  ];
  groups.push({
    label: 'Commercial operations',
    items: [
      {
        label: 'Branch network',
        path: '/branches',
        icon: <HubRounded />,
      },
      ...(canViewInventory
        ? [
            {
              label: 'Inventory and reagents',
              path: '/inventory',
              icon: <Inventory2Rounded />,
            },
          ]
        : []),
      ...(canViewBilling
        ? [
            {
              label: 'Billing and payments',
              path: '/billing',
              icon: <AccountBalanceWalletRounded />,
            },
          ]
        : []),
    ],
  });

  if (canManageUsers) {
    groups.push({
      label: 'Administration',
      items: [
        {
          label: 'Laboratory profile',
          path: '/settings/laboratory-profile',
          icon: <BusinessRounded />,
        },
        {
          label: 'Referring clinicians',
          path: '/settings/referring-clinicians',
          icon: <MedicalServicesRounded />,
        },
        {
          label: 'Portal access',
          path: '/settings/portal-access',
          icon: <VpnKeyRounded />,
        },
        {
          label: 'Users and access',
          path: '/users',
          icon: <GroupsRounded />,
        },
      ],
    });
  }

  return groups;
};

export const pathIsActive = (
  pathname: string,
  path: string
): boolean => (
  path === '/dashboard'
    ? pathname === path
    : pathname.startsWith(path)
);
