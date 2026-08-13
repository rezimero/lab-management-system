import {
  Box,
  Card,
  CardContent,
  Skeleton,
  Stack,
} from '@mui/material';

interface PatientPortalLoadingStateProps {
  rows?: number;
}

const PatientPortalLoadingState = ({
  rows = 4,
}: PatientPortalLoadingStateProps) => (
  <Stack
    spacing={2}
    aria-label="Loading portal content"
  >
    {Array.from({ length: rows }).map(
      (_, index) => (
        <Card key={index}>
          <CardContent>
            <Box
              sx={{
                display: 'grid',
                gap: 1.15,
              }}
            >
              <Skeleton
                width="34%"
                height={24}
              />
              <Skeleton
                width="72%"
                height={18}
              />
              <Skeleton
                width="52%"
                height={18}
              />
            </Box>
          </CardContent>
        </Card>
      )
    )}
  </Stack>
);

export default PatientPortalLoadingState;
