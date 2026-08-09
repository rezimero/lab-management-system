
import { Alert, Box, CircularProgress } from "@mui/material";

type Props = {
 loading?: boolean;
 error?: string | null;
 children?: React.ReactNode;
};

export default function ReferralProductionGuard({
 loading,
 error,
 children,
}: Props) {
 if (loading) {
  return (
   <Box sx={{display:"flex",justifyContent:"center",p:3}}>
    <CircularProgress />
   </Box>
  );
 }

 if (error) {
  return <Alert severity="error">{error}</Alert>;
 }

 return <>{children}</>;
}
