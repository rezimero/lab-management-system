import {
  Alert,
  Box,
  Button,
  Chip,
  CircularProgress,
  List,
  ListItem,
  ListItemText,
  Paper,
  Stack,
  Typography,
} from '@mui/material';

import type {
  NotificationMessage,
} from '../types/notification.types';

interface Props {
  messages: NotificationMessage[];
  loading: boolean;
  onOpen: (message: NotificationMessage) => void;
  onMarkRead: (message: NotificationMessage) => void;
}

const displayDate = (
  value: string | null
): string => {
  if (!value) {
    return 'Unknown time';
  }

  const date = new Date(value);

  return Number.isNaN(date.getTime())
    ? 'Unknown time'
    : date.toLocaleString();
};

const NotificationList = ({
  messages,
  loading,
  onOpen,
  onMarkRead,
}: Props) => {
  if (loading) {
    return (
      <Box sx={{ display: 'grid', placeItems: 'center', py: 8 }}>
        <CircularProgress />
      </Box>
    );
  }

  if (messages.length === 0) {
    return (
      <Alert severity="info">
        There are no notifications in this view.
      </Alert>
    );
  }

  return (
    <Paper variant="outlined">
      <List disablePadding>
        {messages.map((message, index) => (
          <ListItem
            key={message.uuid}
            divider={index < messages.length - 1}
            sx={{
              alignItems: 'flex-start',
              gap: 2,
              py: 2,
              backgroundColor: message.read_at
                ? 'transparent'
                : 'action.hover',
            }}
          >
            <ListItemText
              primary={
                <Stack
                  direction="row"
                  spacing={1}
                  sx={{ alignItems: 'center', flexWrap: 'wrap' }}
                >
                  <Typography sx={{ fontWeight: 720 }}>
                    {message.title}
                  </Typography>
                  {message.priority === 'critical' && (
                    <Chip size="small" color="error" label="Critical" />
                  )}
                  {!message.read_at && (
                    <Chip size="small" color="primary" label="New" />
                  )}
                </Stack>
              }
              secondary={
                <Stack spacing={0.75} sx={{ mt: 0.75 }}>
                  <Typography variant="body2" color="text.secondary">
                    {message.body}
                  </Typography>
                  <Typography variant="caption" color="text.secondary">
                    {displayDate(message.created_at)}
                  </Typography>
                </Stack>
              }
            />

            <Stack spacing={1} sx={{ minWidth: 92 }}>
              {!message.read_at && (
                <Button
                  size="small"
                  onClick={() => onMarkRead(message)}
                >
                  Mark read
                </Button>
              )}
              {message.action_url && (
                <Button
                  size="small"
                  variant="outlined"
                  onClick={() => onOpen(message)}
                >
                  Open
                </Button>
              )}
            </Stack>
          </ListItem>
        ))}
      </List>
    </Paper>
  );
};

export default NotificationList;
