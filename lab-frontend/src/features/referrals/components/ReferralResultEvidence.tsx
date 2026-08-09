
import { List, ListItem, ListItemText } from "@mui/material";

type Props = {
 evidence?: Array<{
  filename?: string;
  uploaded_at?: string;
  uploaded_by?: string;
 }>;
};

export default function ReferralResultEvidence({
 evidence = [],
}: Props) {
 return (
  <List>
   {evidence.map((item, index)=>(
    <ListItem key={index}>
     <ListItemText
      primary={item.filename ?? "Evidence"}
      secondary={`${item.uploaded_by ?? "Unknown"} ${item.uploaded_at ?? ""}`}
     />
    </ListItem>
   ))}
  </List>
 );
}
