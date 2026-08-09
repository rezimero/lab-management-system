
import { Paper, Stack, Typography } from "@mui/material";

type Props = {
 title: string;
 children?: React.ReactNode;
};

export default function ReferralDetailSection({
 title,
 children,
}: Props) {
 return (
  <Paper sx={{p:2}}>
   <Stack spacing={1}>
    <Typography variant="subtitle1">
     {title}
    </Typography>
    {children}
   </Stack>
  </Paper>
 );
}
