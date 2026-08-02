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
  role?: string
): NavigationGroup[] => {
  const groups: NavigationGroup[] = [
    {
      label: 'Workspace',
      items: [
        {
          label: 'Overview',
          path: '/dashboard',
          icon: <DashboardRounded />,
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
      ],
    },
  ];

  const canViewBilling = [
    'superadmin',
    'admin',
    'receptionist',
    'doctor',
  ].includes(role ?? '');

  groups.push({
    label: 'Commercial operations',
    items: [
      {
        label: 'Branch network',
        path: '/branches',
        icon: <HubRounded />,
      },
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
