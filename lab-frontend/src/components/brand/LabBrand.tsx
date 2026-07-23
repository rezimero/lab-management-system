import {
  Box,
  Typography,
} from '@mui/material';

interface RezhelMarkProps {
  size?: number;
}

interface LabBrandProps {
  inverse?: boolean;
  compact?: boolean;
  showDescriptor?: boolean;
  tenantName?: string | null;
}

export const RezhelMark = ({
  size = 48,
}: RezhelMarkProps) => {
  return (
    <Box
      aria-hidden="true"
      sx={{
        position: 'relative',
        width: size,
        height: size,
        flexShrink: 0,
        display: 'grid',
        placeItems: 'center',
        overflow: 'hidden',
        borderRadius: size <= 42 ? 2.75 : 3.25,
        background:
          'linear-gradient(145deg, #3B82F6 0%, #2563EB 42%, #0E7490 100%)',
        boxShadow:
          '0 16px 38px rgba(36, 107, 253, 0.28)',
        isolation: 'isolate',

        '&::before': {
          content: '""',
          position: 'absolute',
          inset: 1,
          zIndex: -1,
          borderRadius: 'inherit',
          border:
            '1px solid rgba(255, 255, 255, 0.26)',
        },

        '&::after': {
          content: '""',
          position: 'absolute',
          top: '10%',
          left: '13%',
          width: '48%',
          height: '25%',
          borderRadius: 999,
          background:
            'rgba(255, 255, 255, 0.25)',
          filter: 'blur(6px)',
          transform: 'rotate(-18deg)',
        },
      }}
    >
      <svg
        width="78%"
        height="78%"
        viewBox="0 0 48 48"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
      >
        <path
          d="M13.5 11.5H25.1C31.8 11.5 35.8 15.1 35.8 20.8C35.8 24.6 33.9 27.5 30.5 29L36.2 36.5H28.8L24 29.9H20.1V36.5H13.5V11.5ZM20.1 17.2V24.4H24.7C27.7 24.4 29.2 23.2 29.2 20.8C29.2 18.4 27.7 17.2 24.7 17.2H20.1Z"
          fill="white"
        />

        <path
          d="M34.2 10.8L39.2 8.3"
          stroke="rgba(255,255,255,0.64)"
          strokeWidth="1.4"
          strokeLinecap="round"
        />

        <path
          d="M36.7 15.4L41.2 16.7"
          stroke="rgba(255,255,255,0.64)"
          strokeWidth="1.4"
          strokeLinecap="round"
        />

        <circle
          cx="40"
          cy="7.8"
          r="2.2"
          fill="#A5F3FC"
        />

        <circle
          cx="42"
          cy="17"
          r="1.8"
          fill="#BFDBFE"
        />

        <circle
          cx="34"
          cy="10.8"
          r="1.5"
          fill="white"
        />
      </svg>
    </Box>
  );
};

const LabBrand = ({
  inverse = false,
  compact = false,
  showDescriptor = true,
  tenantName = null,
}: LabBrandProps) => {
  const foreground = inverse
    ? '#FFFFFF'
    : '#0B1220';

  const secondary = inverse
    ? 'rgba(255, 255, 255, 0.60)'
    : '#667085';

  const descriptor = tenantName
    ? tenantName
    : 'Clinical Intelligence';

  return (
    <Box
      sx={{
        display: 'inline-flex',
        alignItems: 'center',
        gap: compact ? 1.25 : 1.5,
        minWidth: 0,
      }}
    >
      <RezhelMark
        size={compact ? 42 : 48}
      />

      <Box sx={{ minWidth: 0 }}>
        <Typography
          sx={{
            color: foreground,
            fontSize: compact
              ? '1.08rem'
              : '1.2rem',
            lineHeight: 1,
            fontWeight: 780,
            letterSpacing: '-0.035em',
            whiteSpace: 'nowrap',
          }}
        >
          Rezhel
        </Typography>

        {showDescriptor && (
          <Typography
            title={descriptor}
            sx={{
              maxWidth: compact
                ? 190
                : 240,
              mt: 0.5,
              overflow: 'hidden',
              color: secondary,
              fontSize: compact
                ? '0.66rem'
                : '0.7rem',
              lineHeight: 1.3,
              fontWeight: 620,
              letterSpacing: tenantName
                ? '0.012em'
                : '0.075em',
              textOverflow: 'ellipsis',
              textTransform: tenantName
                ? 'none'
                : 'uppercase',
              whiteSpace: 'nowrap',
            }}
          >
            {descriptor}
          </Typography>
        )}
      </Box>
    </Box>
  );
};

export default LabBrand;