import type { ReactNode } from 'react';
import AssignmentRounded from '@mui/icons-material/AssignmentRounded';
import DashboardRounded from '@mui/icons-material/DashboardRounded';
import DescriptionRounded from '@mui/icons-material/DescriptionRounded';
import FactCheckRounded from '@mui/icons-material/FactCheckRounded';
import GroupsRounded from '@mui/icons-material/GroupsRounded';
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
  canManageUsers: boolean
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

  if (canManageUsers) {
    groups.push({
      label: 'Administration',
      items: [
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
