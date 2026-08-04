import {
  Card,
  CardContent,
  Grid,
  Stack,
  Typography,
} from '@mui/material';

import Inventory2Rounded from '@mui/icons-material/Inventory2Rounded';
import NotificationImportantRounded from '@mui/icons-material/NotificationImportantRounded';
import ReportProblemRounded from '@mui/icons-material/ReportProblemRounded';
import ScheduleRounded from '@mui/icons-material/ScheduleRounded';

import type {
  InventoryAlerts,
} from '../types/inventory.types';

interface Props {
  stockedLots: number;
  alerts?: InventoryAlerts;
}

const InventorySummaryCards = ({
  stockedLots,
  alerts,
}: Props) => {
  const cards = [
    {
      label: 'Stocked lots',
      value: stockedLots,
      icon: <Inventory2Rounded color="primary" />,
    },
    {
      label: 'Low stock',
      value: alerts?.summary.low_stock ?? 0,
      icon: <NotificationImportantRounded color="warning" />,
    },
    {
      label: 'Out of stock',
      value: alerts?.summary.out_of_stock ?? 0,
      icon: <ReportProblemRounded color="error" />,
    },
    {
      label: 'Expiring or expired',
      value: (alerts?.summary.expiring_soon ?? 0)
        + (alerts?.summary.expired ?? 0),
      icon: <ScheduleRounded color="warning" />,
    },
  ];

  return (
    <Grid container spacing={2}>
      {cards.map((card) => (
        <Grid key={card.label} size={{ xs: 12, sm: 6, lg: 3 }}>
          <Card variant="outlined" sx={{ height: '100%' }}>
            <CardContent>
              <Stack direction="row" sx={{ justifyContent: "space-between", alignItems: "center" }}>
                <Stack spacing={0.5}>
                  <Typography variant="body2" color="text.secondary">
                    {card.label}
                  </Typography>
                  <Typography variant="h4" sx={{ fontWeight: 760 }}>
                    {card.value}
                  </Typography>
                </Stack>
                {card.icon}
              </Stack>
            </CardContent>
          </Card>
        </Grid>
      ))}
    </Grid>
  );
};

export default InventorySummaryCards;
