import { Alert, CircularProgress, Paper } from '@mui/material';

interface Props {
  loading: boolean;
  error: string | null;
}

const OrderViewState = ({ loading, error }: Props) => {
  if (loading) {
    return (
      <Paper variant="outlined" sx={{ py: 10, display: 'grid', placeItems: 'center' }}>
        <CircularProgress />
      </Paper>
    );
  }
  return <Alert severity="error">{error ?? 'Laboratory order was not found.'}</Alert>;
};

export default OrderViewState;
