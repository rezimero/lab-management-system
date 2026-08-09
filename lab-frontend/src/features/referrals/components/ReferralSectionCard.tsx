
import { Paper, Typography } from "@mui/material";

export default function ReferralSectionCard({
 title,
 children,
}: {title:string; children?:React.ReactNode}) {
 return (
  <Paper sx={{p:3,borderRadius:3}}>
   <Typography variant="h6" sx={{mb:2}}>{title}</Typography>
   {children}
  </Paper>
 );
}
