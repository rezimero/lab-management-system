import {
  Toaster,
} from 'react-hot-toast';

const AppToaster = () => (
  <Toaster
    position="top-right"
    gutter={10}
    toastOptions={{
      duration: 4500,
      style: {
        maxWidth: 420,
        padding: '14px 16px',
        borderRadius: 14,
        color: '#0B1220',
        background: '#FFFFFF',
        border:
          '1px solid rgba(11, 18, 32, 0.08)',
        boxShadow:
          '0 18px 48px rgba(11, 18, 32, 0.14)',
        fontSize: '0.875rem',
        fontWeight: 540,
      },
      success: {
        iconTheme: {
          primary: '#079455',
          secondary: '#FFFFFF',
        },
      },
      error: {
        duration: 6000,
        iconTheme: {
          primary: '#D92D20',
          secondary: '#FFFFFF',
        },
      },
    }}
  />
);

export default AppToaster;
