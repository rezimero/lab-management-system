import {
  Box,
  List,
  ListItemButton,
  ListItemIcon,
  ListItemText,
  Typography,
} from '@mui/material';

import {
  pathIsActive,
  type NavigationGroup,
} from './navigationConfig';

interface Props {
  groups: NavigationGroup[];
  pathname: string;
  onNavigate: (path: string) => void;
}

const SidebarNavigation = ({
  groups,
  pathname,
  onNavigate,
}: Props) => (
  <Box
    component="nav"
    aria-label="Primary navigation"
    sx={{
      flex: 1,
      overflowY: 'auto',
      px: 1.5,
      pb: 2,
    }}
  >
    {groups.map(
      (group, groupIndex) => (
        <Box
          key={group.label}
          sx={{
            mt: groupIndex === 0
              ? 0
              : 2.4,
          }}
        >
          <Typography
            sx={{
              px: 1.5,
              mb: 0.9,
              color:
                'rgba(255,255,255,0.31)',
              fontSize: '0.63rem',
              fontWeight: 720,
              letterSpacing: '0.095em',
              textTransform: 'uppercase',
            }}
          >
            {group.label}
          </Typography>

          <List
            disablePadding
            sx={{
              display: 'grid',
              gap: 0.4,
            }}
          >
            {group.items.map((item) => {
              const selected =
                pathIsActive(
                  pathname,
                  item.path
                );

              return (
                <ListItemButton
                  key={item.path}
                  selected={selected}
                  onClick={() => {
                    onNavigate(item.path);
                  }}
                  sx={{
                    minHeight: 47,
                    px: 1.35,
                    borderRadius: 2.7,
                    color: selected
                      ? '#FFFFFF'
                      : 'rgba(255,255,255,0.57)',
                    '&.Mui-selected': {
                      background:
                        'linear-gradient(135deg, rgba(59,130,246,.21), rgba(8,145,178,.09))',
                    },
                    '&:hover': {
                      color: '#FFFFFF',
                      transform:
                        'translateX(2px)',
                      backgroundColor:
                        'rgba(255,255,255,.055)',
                    },
                  }}
                >
                  <ListItemIcon
                    sx={{
                      minWidth: 39,
                      color: selected
                        ? '#8EDDF0'
                        : 'rgba(255,255,255,.42)',
                      '& svg': {
                        fontSize: 20,
                      },
                    }}
                  >
                    {item.icon}
                  </ListItemIcon>

                  <ListItemText
                    primary={item.label}
                    slotProps={{
                      primary: {
                        sx: {
                          fontSize: '0.8rem',
                          fontWeight: selected
                            ? 650
                            : 540,
                        },
                      },
                    }}
                  />
                </ListItemButton>
              );
            })}
          </List>
        </Box>
      )
    )}
  </Box>
);

export default SidebarNavigation;
