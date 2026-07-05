import React, { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import {
  Container,
  Paper,
  TextField,
  Button,
  Typography,
  Box,
  Alert,
  InputAdornment,
  IconButton,
  Checkbox,
  FormControlLabel,
} from '@mui/material';
import { Visibility, VisibilityOff, Login as LoginIcon, AdminPanelSettings } from '@mui/icons-material';
import { useAuth } from '../contexts/AuthContext';
import { AuthResponse } from '../types';

interface LoginFormData {
  email: string;
  password: string;
  domain: string;
}

const Login: React.FC = () => {
  const navigate = useNavigate();
  const { login } = useAuth();
  const [formData, setFormData] = useState<LoginFormData>({
    email: '',
    password: '',
    domain: '',
  });
  const [isSuperadmin, setIsSuperadmin] = useState<boolean>(false);
  const [showPassword, setShowPassword] = useState<boolean>(false);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState<boolean>(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    setLoading(true);

    try {
      const payload: any = {
        email: formData.email,
        password: formData.password,
      };

      // Only add domain if NOT superadmin
      if (!isSuperadmin) {
        payload.domain = formData.domain;
      }

      const response = await login(payload) as AuthResponse;

      // Navigate based on user type
      if (response?.is_superadmin) {
        navigate('/landlord');
      } else {
        navigate('/dashboard');
      }
    } catch (err: any) {
      setError(err.response?.data?.message || 'Login failed. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <Box
      sx={{
        minHeight: '100vh',
        display: 'flex',
        alignItems: 'center',
        background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
      }}
    >
      <Container maxWidth="sm">
        <Paper sx={{ p: 4, borderRadius: 2 }}>
          <Box sx={{ textAlign: 'center', mb: 3 }}>
            <Typography variant="h4" component="h1" gutterBottom>
              🧪 Lab Manager
            </Typography>
            <Typography variant="body2" color="text.secondary">
              {isSuperadmin ? '👑 Superadmin Login (God Mode)' : 'Sign in to your lab account'}
            </Typography>
          </Box>

          {error && (
            <Alert severity="error" sx={{ mb: 2 }}>
              {error}
            </Alert>
          )}

          <form onSubmit={handleSubmit}>
            <TextField
              fullWidth
              label="Email"
              type="email"
              margin="normal"
              required
              value={formData.email}
              onChange={(e) => setFormData({ ...formData, email: e.target.value })}
              autoComplete="email"
            />

            <TextField
              fullWidth
              label="Password"
              type={showPassword ? 'text' : 'password'}
              margin="normal"
              required
              value={formData.password}
              onChange={(e) => setFormData({ ...formData, password: e.target.value })}
              autoComplete="current-password"
              slotProps={{
                input: {
                  endAdornment: (
                    <InputAdornment position="end">
                      <IconButton
                        onClick={() => setShowPassword(!showPassword)}
                        edge="end"
                        aria-label="toggle password visibility"
                      >
                        {showPassword ? <VisibilityOff /> : <Visibility />}
                      </IconButton>
                    </InputAdornment>
                  ),
                },
              }}
            />

            {/* Superadmin Toggle */}
            <Box sx={{ mt: 2 }}>
              <FormControlLabel
                control={
                  <Checkbox
                    checked={isSuperadmin}
                    onChange={(e) => {
                      setIsSuperadmin(e.target.checked);
                      if (e.target.checked) {
                        setFormData({ ...formData, domain: '' });
                      }
                    }}
                    color="primary"
                    icon={<AdminPanelSettings />}
                    checkedIcon={<AdminPanelSettings sx={{ color: '#764ba2' }} />}
                  />
                }
                label={
                  <Typography variant="body2" sx={{ fontWeight: isSuperadmin ? 'bold' : 'normal' }}>
                    Login as Superadmin <span style={{ color: '#764ba2', fontSize: '0.8rem' }}>(God Mode)</span>
                  </Typography>
                }
              />
            </Box>

            {/* Domain Field - Only shown for tenant login */}
            {!isSuperadmin && (
              <TextField
                fullWidth
                label="Lab Domain"
                margin="normal"
                required
                placeholder="e.g., fego, rezi, keno"
                helperText="Enter your lab's unique domain"
                value={formData.domain}
                onChange={(e) => setFormData({ ...formData, domain: e.target.value.toLowerCase() })}
              />
            )}

            {isSuperadmin && (
              <Alert severity="info" sx={{ mt: 2 }}>
                <Typography variant="body2">
                  <strong>Superadmin Mode:</strong> No domain required. You will have access to all labs.
                </Typography>
              </Alert>
            )}

            <Button
              type="submit"
              fullWidth
              variant="contained"
              size="large"
              sx={{ mt: 3 }}
              disabled={loading}
              startIcon={<LoginIcon />}
            >
              {loading ? 'Signing in...' : isSuperadmin ? 'Sign in as Superadmin' : 'Sign In'}
            </Button>
          </form>

          <Box sx={{ mt: 2, textAlign: 'center' }}>
            <Typography variant="body2">
              Don't have an account?{' '}
              <Button component={Link} to="/register" color="primary">
                Register Now
              </Button>
            </Typography>
          </Box>
        </Paper>
      </Container>
    </Box>
  );
};

export default Login;