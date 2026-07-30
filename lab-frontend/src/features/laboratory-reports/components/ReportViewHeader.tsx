import {
  Box,
  Button,
  CircularProgress,
  IconButton,
  Typography,
} from '@mui/material';

import ArrowBackRounded from
  '@mui/icons-material/ArrowBackRounded';

import DownloadRounded from
  '@mui/icons-material/DownloadRounded';

import PrintRounded from
  '@mui/icons-material/PrintRounded';

interface Props {
  downloadingPdf: boolean;
  onBack: () => void;
  onDownloadPdf: () => void;
  onPrint: () => void;
}

const ReportViewHeader = ({
  downloadingPdf,
  onBack,
  onDownloadPdf,
  onPrint,
}: Props) => (
  <Box
    className="no-print"
    sx={{
      mb: 3,
      display: 'flex',
      flexDirection: {
        xs: 'column',
        sm: 'row',
      },
      justifyContent: 'space-between',
      alignItems: {
        xs: 'stretch',
        sm: 'center',
      },
      gap: 2,
    }}
  >
    <Box
      sx={{
        display: 'flex',
        alignItems: 'center',
        gap: 1.25,
      }}
    >
      <IconButton
        aria-label="Back to laboratory reports"
        onClick={onBack}
      >
        <ArrowBackRounded />
      </IconButton>

      <Box>
        <Typography
          component="h2"
          variant="h5"
          sx={{ fontWeight: 760 }}
        >
          Laboratory report
        </Typography>

        <Typography
          variant="body2"
          color="text.secondary"
        >
          Immutable issued report snapshot
        </Typography>
      </Box>
    </Box>

    <Box
      sx={{
        display: 'flex',
        flexWrap: 'wrap',
        gap: 1,
      }}
    >
      <Button
        variant="contained"
        disabled={downloadingPdf}
        startIcon={
          downloadingPdf
            ? (
              <CircularProgress
                color="inherit"
                size={16}
              />
            )
            : <DownloadRounded />
        }
        onClick={onDownloadPdf}
      >
        {downloadingPdf
          ? 'Downloading PDF...'
          : 'Download PDF'}
      </Button>

      <Button
        variant="outlined"
        startIcon={<PrintRounded />}
        onClick={onPrint}
      >
        Print report
      </Button>
    </Box>
  </Box>
);

export default ReportViewHeader;