import React from 'react';
import { Routes, Route, Link, useNavigate } from 'react-router-dom';
import {
  Box,
  Typography,
  AppBar,
  Toolbar,
  Container,
  Chip,
  Button,
  Drawer,
  List,
  ListItem,
  ListItemIcon,
  ListItemText,
  Divider,
  IconButton,
  Avatar,
  Menu,
  MenuItem,
  Tooltip,
  Badge,
} from '@mui/material';
import {
  Dashboard as DashboardIcon,
  People as PeopleIcon,
  Business as BusinessIcon,
  MedicalServices as MedicalIcon,
  Science as ScienceIcon,
  Assignment as AssignmentIcon,
  Logout as LogoutIcon,
  AdminPanelSettings as AdminIcon,
  Menu as MenuIcon,
  Notifications as NotificationsIcon,
  Settings as SettingsIcon,
} from '@mui/icons-material';
import { useAuth } from '../contexts/AuthContext';
import DashboardStats from '../components/landlords/DashboardStats';
import AllUsers from '../components/landlords/AllUsers';
import AllTenants from '../components/landlords/AllTenants';
import ImpersonateButton from '../components/landlords/ImpersonateButton';

const drawerWidth = 240;

interface MenuItemType {
  text: string;
  icon: React.ReactNode;
  path: string;
}

const LandlordDashboard: React.FC = () => {
  const { logout, user, isImpersonating } = useAuth();
  const navigate = useNavigate();
  const [mobileOpen, setMobileOpen] = React.useState(false);
  const [anchorEl, setAnchorEl] = React.useState<null | HTMLElement>(null);

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  const handleDrawerToggle = () => {
    setMobileOpen(!mobileOpen);
  };

  const handleMenuOpen = (event: React.MouseEvent<HTMLElement>) => {
    setAnchorEl(event.currentTarget);
  };

  const handleMenuClose = () => {
    setAnchorEl(null);
  };

  const menuItems: MenuItemType[] = [
    { text: 'Overview', icon: <DashboardIcon />, path: '/landlord' },
    { text: 'All Users', icon: <PeopleIcon />, path: '/landlord/users' },
    { text: 'All Tenants', icon: <BusinessIcon />, path: '/landlord/tenants' },
    { text: 'All Patients', icon: <MedicalIcon />, path: '/landlord/patients' },
    { text: 'All Lab Tests', icon: <ScienceIcon />, path: '/landlord/lab-tests' },
    { text: 'All Test Results', icon: <AssignmentIcon />, path: '/landlord/test-results' },
  ];

  const drawerContent = (
    <Box>
      <Box sx={{ p: 2, display: 'flex', alignItems: 'center', gap: 1 }}>
        <AdminIcon sx={{ color: '#764ba2' }} />
        <Typography variant="h6" color="primary">
          God Mode
        </Typography>
      </Box>
      <Divider />
      <List>
        {menuItems.map((item) => (
          <ListItem
            key={item.text}
            component={Link}
            to={item.path}
            sx={{
              '&:hover': { backgroundColor: 'rgba(67, 97, 238, 0.08)' },
              borderRadius: 1,
              mx: 1,
              mb: 0.5,
            }}
          >
            <ListItemIcon>{item.icon}</ListItemIcon>
            <ListItemText primary={item.text} />
          </ListItem>
        ))}
      </List>
    </Box>
  );

  return (
    <Box sx={{ display: 'flex' }}>
      <AppBar position="fixed" sx={{ zIndex: (theme) => theme.zIndex.drawer + 1, bgcolor: '#1a1a2e' }}>
        <Toolbar>
          <IconButton
            color="inherit"
            edge="start"
            onClick={handleDrawerToggle}
            sx={{ mr: 2, display: { sm: 'none' } }}
          >
            <MenuIcon />
          </IconButton>
          <AdminIcon sx={{ mr: 2 }} />
          <Typography variant="h6" sx={{ flexGrow: 1 }}>
            🏢 Landlord Dashboard
            {isImpersonating && (
              <Chip
                label="IMPERSONATING"
                color="warning"
                size="small"
                sx={{ ml: 2 }}
              />
            )}
          </Typography>

          <ImpersonateButton />

          <Tooltip title="Notifications">
            <IconButton color="inherit" sx={{ ml: 2 }}>
              <Badge badgeContent={3} color="error">
                <NotificationsIcon />
              </Badge>
            </IconButton>
          </Tooltip>

          <Tooltip title="Account settings">
            <IconButton onClick={handleMenuOpen} color="inherit" sx={{ ml: 1 }}>
              <Avatar sx={{ width: 32, height: 32, bgcolor: '#764ba2' }}>
                {user?.name?.charAt(0) || 'S'}
              </Avatar>
            </IconButton>
          </Tooltip>

          <Menu
            anchorEl={anchorEl}
            open={Boolean(anchorEl)}
            onClose={handleMenuClose}
            transformOrigin={{ horizontal: 'right', vertical: 'top' }}
            anchorOrigin={{ horizontal: 'right', vertical: 'bottom' }}
          >
            <MenuItem>
              <Typography variant="body2">{user?.email}</Typography>
            </MenuItem>
            <Divider />
            <MenuItem onClick={handleMenuClose}>
              <ListItemIcon><SettingsIcon fontSize="small" /></ListItemIcon>
              <ListItemText>Settings</ListItemText>
            </MenuItem>
            <MenuItem onClick={handleLogout}>
              <ListItemIcon><LogoutIcon fontSize="small" /></ListItemIcon>
              <ListItemText>Logout</ListItemText>
            </MenuItem>
          </Menu>
        </Toolbar>
      </AppBar>

      <Box
        component="nav"
        sx={{ width: { sm: drawerWidth }, flexShrink: { sm: 0 } }}
      >
        <Drawer
          variant="temporary"
          open={mobileOpen}
          onClose={handleDrawerToggle}
          ModalProps={{ keepMounted: true }}
          sx={{
            display: { xs: 'block', sm: 'none' },
            '& .MuiDrawer-paper': { boxSizing: 'border-box', width: drawerWidth },
          }}
        >
          {drawerContent}
        </Drawer>
        <Drawer
          variant="permanent"
          sx={{
            display: { xs: 'none', sm: 'block' },
            '& .MuiDrawer-paper': { boxSizing: 'border-box', width: drawerWidth },
          }}
          open
        >
          {drawerContent}
        </Drawer>
      </Box>

      <Box
        component="main"
        sx={{
          flexGrow: 1,
          p: 3,
          width: { sm: `calc(100% - ${drawerWidth}px)` },
          mt: 8,
        }}
      >
        <Container maxWidth="xl">
          <Routes>
            <Route index element={<DashboardStats />} />
            <Route path="users" element={<AllUsers />} />
            <Route path="tenants" element={<AllTenants />} />
            <Route
              path="patients"
              element={
                <Box>
                  <Typography variant="h5" gutterBottom>🩺 All Patients</Typography>
                  <Typography variant="body2" color="textSecondary">
                    Patient management across all tenants coming soon...
                  </Typography>
                </Box>
              }
            />
            <Route
              path="lab-tests"
              element={
                <Box>
                  <Typography variant="h5" gutterBottom>🧪 All Lab Tests</Typography>
                  <Typography variant="body2" color="textSecondary">
                    Lab test management across all tenants coming soon...
                  </Typography>
                </Box>
              }
            />
            <Route
              path="test-results"
              element={
                <Box>
                  <Typography variant="h5" gutterBottom>📄 All Test Results</Typography>
                  <Typography variant="body2" color="textSecondary">
                    Test result management across all tenants coming soon...
                  </Typography>
                </Box>
              }
            />
          </Routes>
        </Container>
      </Box>
    </Box>
  );
};

export default LandlordDashboard;