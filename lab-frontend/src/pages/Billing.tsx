import {
  useState,
} from 'react';

import AddRounded from '@mui/icons-material/AddRounded';
import PaymentsRounded from '@mui/icons-material/PaymentsRounded';
import ReceiptLongRounded from '@mui/icons-material/ReceiptLongRounded';
import SendRounded from '@mui/icons-material/SendRounded';

import {
  Alert,
  Box,
  Button,
  Card,
  CardContent,
  Chip,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  MenuItem,
  Stack,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  TextField,
  Typography,
} from '@mui/material';

import {
  useMutation,
  useQuery,
  useQueryClient,
} from '@tanstack/react-query';

import Layout from '../components/Layout/Layout';

import {
  createInvoice,
  fetchInvoices,
  issueInvoice,
  recordPayment,
} from '../features/commercial/api/commercialApi';

import type {
  InvoiceSummary,
  PaymentReceipt,
} from '../features/commercial/types/commercial.types';

import {
  apiErrorMessage,
} from '../features/test-results/utils/apiErrorMessage';

const money = (
  amount: string,
  currency = 'NGN'
): string => new Intl.NumberFormat('en-NG', {
  style: 'currency',
  currency,
}).format(Number(amount || 0));

const Billing = () => {
  const queryClient = useQueryClient();
  const [search, setSearch] = useState('');
  const [status, setStatus] = useState('');
  const [createOpen, setCreateOpen] = useState(false);
  const [paymentInvoice, setPaymentInvoice] = useState<InvoiceSummary | null>(null);
  const [orderUuid, setOrderUuid] = useState('');
  const [discount, setDiscount] = useState('0');
  const [tax, setTax] = useState('0');
  const [paymentAmount, setPaymentAmount] = useState('');
  const [paymentMethod, setPaymentMethod] = useState('cash');
  const [paymentReference, setPaymentReference] = useState('');
  const [receipt, setReceipt] = useState<PaymentReceipt | null>(null);
  const [error, setError] = useState<string | null>(null);

  const invoicesQuery = useQuery({
    queryKey: ['commercial-invoices', search, status],
    queryFn: () => fetchInvoices(search, status),
  });

  const refresh = async () => {
    await queryClient.invalidateQueries({
      queryKey: ['commercial-invoices'],
    });
  };

  const createMutation = useMutation({
    mutationFn: () => createInvoice(orderUuid, discount, tax),
    onSuccess: async () => {
      setCreateOpen(false);
      setOrderUuid('');
      setDiscount('0');
      setTax('0');
      setError(null);
      await refresh();
    },
    onError: (requestError: unknown) => {
      setError(apiErrorMessage(requestError, 'Failed to create the invoice.'));
    },
  });

  const issueMutation = useMutation({
    mutationFn: issueInvoice,
    onSuccess: refresh,
    onError: (requestError: unknown) => {
      setError(apiErrorMessage(requestError, 'Failed to issue the invoice.'));
    },
  });

  const paymentMutation = useMutation({
    mutationFn: () => {
      if (!paymentInvoice) {
        throw new Error('Select an invoice before recording a payment.');
      }

      return recordPayment(
        paymentInvoice.uuid,
        paymentAmount,
        paymentMethod,
        paymentReference
      );
    },
    onSuccess: async (nextReceipt) => {
      setReceipt(nextReceipt);
      setPaymentInvoice(null);
      setPaymentAmount('');
      setPaymentReference('');
      setError(null);
      await refresh();
    },
    onError: (requestError: unknown) => {
      setError(apiErrorMessage(requestError, 'Failed to record the payment.'));
    },
  });

  const invoices = invoicesQuery.data?.data ?? [];
  const outstanding = invoices.reduce(
    (total, invoice) => total + Number(invoice.balance_due || 0),
    0
  );
  const collected = invoices.reduce(
    (total, invoice) => total + Number(invoice.amount_paid || 0),
    0
  );

  return (
    <Layout title="Billing and payments">
      <Stack spacing={2.5}>
        <Box
          sx={{
            display: 'flex',
            flexDirection: { xs: 'column', md: 'row' },
            justifyContent: 'space-between',
            alignItems: { xs: 'flex-start', md: 'center' },
            gap: 2,
          }}
        >
          <Box>
            <Typography variant="h5" sx={{ fontWeight: 750 }}>
              Billing workspace
            </Typography>
            <Typography color="text.secondary" sx={{ mt: 0.5 }}>
              Generate invoices, collect payments and issue traceable receipts.
            </Typography>
          </Box>
          <Button
            variant="contained"
            startIcon={<AddRounded />}
            onClick={() => {
              setError(null);
              setCreateOpen(true);
            }}
          >
            Create invoice
          </Button>
        </Box>

        {error && <Alert severity="error">{error}</Alert>}

        <Box
          sx={{
            display: 'grid',
            gridTemplateColumns: {
              xs: '1fr',
              md: 'repeat(3, minmax(0, 1fr))',
            },
            gap: 2,
          }}
        >
          <Card variant="outlined">
            <CardContent>
              <ReceiptLongRounded color="primary" />
              <Typography variant="h4" sx={{ fontWeight: 750, mt: 1 }}>
                {invoices.length}
              </Typography>
              <Typography color="text.secondary">Invoices in view</Typography>
            </CardContent>
          </Card>
          <Card variant="outlined">
            <CardContent>
              <PaymentsRounded color="success" />
              <Typography variant="h5" sx={{ fontWeight: 750, mt: 1 }}>
                {money(String(collected))}
              </Typography>
              <Typography color="text.secondary">Payments collected</Typography>
            </CardContent>
          </Card>
          <Card variant="outlined">
            <CardContent>
              <Typography variant="overline" color="text.secondary">
                Outstanding
              </Typography>
              <Typography variant="h5" sx={{ fontWeight: 750, mt: 1 }}>
                {money(String(outstanding))}
              </Typography>
              <Typography color="text.secondary">Current invoice balances</Typography>
            </CardContent>
          </Card>
        </Box>

        <Stack direction={{ xs: 'column', md: 'row' }} spacing={1.5}>
          <TextField
            label="Search invoice, order or patient"
            value={search}
            onChange={(event) => setSearch(event.target.value)}
            fullWidth
          />
          <TextField
            select
            label="Status"
            value={status}
            onChange={(event) => setStatus(event.target.value)}
            sx={{ minWidth: 220 }}
          >
            <MenuItem value="">All statuses</MenuItem>
            <MenuItem value="draft">Draft</MenuItem>
            <MenuItem value="issued">Issued</MenuItem>
            <MenuItem value="partially_paid">Partially paid</MenuItem>
            <MenuItem value="paid">Paid</MenuItem>
            <MenuItem value="void">Void</MenuItem>
          </TextField>
        </Stack>

        {invoicesQuery.isError && (
          <Alert severity="error">
            {apiErrorMessage(invoicesQuery.error, 'Failed to load invoices.')}
          </Alert>
        )}

        <TableContainer component={Card} variant="outlined">
          <Table>
            <TableHead>
              <TableRow>
                <TableCell>Invoice</TableCell>
                <TableCell>Patient / order</TableCell>
                <TableCell>Branch</TableCell>
                <TableCell align="right">Total</TableCell>
                <TableCell align="right">Balance</TableCell>
                <TableCell>Status</TableCell>
                <TableCell align="right">Actions</TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {invoices.map((invoice) => (
                <TableRow key={invoice.uuid} hover>
                  <TableCell>
                    <Typography sx={{ fontWeight: 650 }}>{invoice.invoice_number}</Typography>
                    <Typography variant="caption" color="text.secondary">
                      {invoice.due_at
                        ? `Due ${new Date(invoice.due_at).toLocaleDateString()}`
                        : 'No due date'}
                    </Typography>
                  </TableCell>
                  <TableCell>
                    <Typography>{invoice.patient?.full_name ?? 'Walk-in account'}</Typography>
                    <Typography variant="caption" color="text.secondary">
                      {invoice.order?.order_number ?? 'Manual invoice'}
                    </Typography>
                  </TableCell>
                  <TableCell>{invoice.branch?.name ?? '—'}</TableCell>
                  <TableCell align="right">
                    {money(invoice.total_amount, invoice.currency)}
                  </TableCell>
                  <TableCell align="right">
                    {money(invoice.balance_due, invoice.currency)}
                  </TableCell>
                  <TableCell>
                    <Chip size="small" label={invoice.status.replace('_', ' ')} />
                  </TableCell>
                  <TableCell align="right">
                    <Stack direction="row" spacing={0.5} sx={{ justifyContent: "flex-end" }}>
                      {invoice.status === 'draft' && (
                        <Button
                          size="small"
                          startIcon={<SendRounded />}
                          disabled={issueMutation.isPending}
                          onClick={() => issueMutation.mutate(invoice.uuid)}
                        >
                          Issue
                        </Button>
                      )}
                      {['issued', 'partially_paid'].includes(invoice.status) && (
                        <Button
                          size="small"
                          startIcon={<PaymentsRounded />}
                          onClick={() => {
                            setPaymentInvoice(invoice);
                            setPaymentAmount(invoice.balance_due);
                            setError(null);
                          }}
                        >
                          Payment
                        </Button>
                      )}
                    </Stack>
                  </TableCell>
                </TableRow>
              ))}

              {!invoicesQuery.isPending && invoices.length === 0 && (
                <TableRow>
                  <TableCell colSpan={7} align="center">
                    No invoices match the current filters.
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
        </TableContainer>
      </Stack>

      <Dialog open={createOpen} onClose={() => setCreateOpen(false)} fullWidth maxWidth="sm">
        <DialogTitle>Create invoice from laboratory order</DialogTitle>
        <DialogContent>
          <Stack spacing={2} sx={{ mt: 1 }}>
            {error && <Alert severity="error">{error}</Alert>}
            <TextField
              label="Laboratory order UUID"
              value={orderUuid}
              onChange={(event) => setOrderUuid(event.target.value)}
              required
              helperText="Copy the UUID from the laboratory order URL or order API record."
            />
            <Stack direction={{ xs: 'column', sm: 'row' }} spacing={2}>
              <TextField
                fullWidth
                label="Discount amount"
                type="number"
                value={discount}
                onChange={(event) => setDiscount(event.target.value)}
              />
              <TextField
                fullWidth
                label="Tax amount"
                type="number"
                value={tax}
                onChange={(event) => setTax(event.target.value)}
              />
            </Stack>
          </Stack>
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setCreateOpen(false)}>Cancel</Button>
          <Button
            variant="contained"
            disabled={!orderUuid.trim() || createMutation.isPending}
            onClick={() => createMutation.mutate()}
          >
            {createMutation.isPending ? 'Creating…' : 'Create invoice'}
          </Button>
        </DialogActions>
      </Dialog>

      <Dialog
        open={Boolean(paymentInvoice)}
        onClose={() => setPaymentInvoice(null)}
        fullWidth
        maxWidth="sm"
      >
        <DialogTitle>Record payment</DialogTitle>
        <DialogContent>
          <Stack spacing={2} sx={{ mt: 1 }}>
            {error && <Alert severity="error">{error}</Alert>}
            <Typography color="text.secondary">
              {paymentInvoice?.invoice_number} · Balance {paymentInvoice
                ? money(paymentInvoice.balance_due, paymentInvoice.currency)
                : ''}
            </Typography>
            <TextField
              label="Amount"
              type="number"
              value={paymentAmount}
              onChange={(event) => setPaymentAmount(event.target.value)}
              required
            />
            <TextField
              select
              label="Payment method"
              value={paymentMethod}
              onChange={(event) => setPaymentMethod(event.target.value)}
            >
              <MenuItem value="cash">Cash</MenuItem>
              <MenuItem value="card">Card</MenuItem>
              <MenuItem value="bank_transfer">Bank transfer</MenuItem>
              <MenuItem value="mobile_money">Mobile money</MenuItem>
              <MenuItem value="insurance">Insurance</MenuItem>
              <MenuItem value="other">Other</MenuItem>
            </TextField>
            <TextField
              label="Reference"
              value={paymentReference}
              onChange={(event) => setPaymentReference(event.target.value)}
            />
          </Stack>
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setPaymentInvoice(null)}>Cancel</Button>
          <Button
            variant="contained"
            disabled={!paymentAmount || paymentMutation.isPending}
            onClick={() => paymentMutation.mutate()}
          >
            {paymentMutation.isPending ? 'Recording…' : 'Record payment'}
          </Button>
        </DialogActions>
      </Dialog>

      <Dialog open={Boolean(receipt)} onClose={() => setReceipt(null)} fullWidth maxWidth="sm">
        <DialogTitle>Payment receipt</DialogTitle>
        <DialogContent>
          {receipt && (
            <Stack spacing={1.25} sx={{ mt: 1 }}>
              <Typography variant="h6" sx={{ fontWeight: 750 }}>{receipt.receipt_number}</Typography>
              <Typography>Invoice: {receipt.invoice.invoice_number}</Typography>
              <Typography>Payment: {receipt.payment_number}</Typography>
              <Typography>Method: {receipt.method.replace('_', ' ')}</Typography>
              <Typography>
                Amount: {money(receipt.amount, receipt.invoice.currency)}
              </Typography>
              <Typography color="text.secondary">
                Balance: {money(receipt.invoice.balance_due, receipt.invoice.currency)}
              </Typography>
            </Stack>
          )}
        </DialogContent>
        <DialogActions>
          <Button onClick={() => window.print()}>Print</Button>
          <Button variant="contained" onClick={() => setReceipt(null)}>Done</Button>
        </DialogActions>
      </Dialog>
    </Layout>
  );
};

export default Billing;
