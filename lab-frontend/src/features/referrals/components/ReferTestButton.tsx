
import { Button } from "@mui/material";

type Props = {
  disabled?: boolean;
  onClick?: () => void;
};

export default function ReferTestButton({
  disabled,
  onClick,
}: Props) {
  return (
    <Button
      variant="outlined"
      disabled={disabled}
      onClick={onClick}
    >
      Refer Test
    </Button>
  );
}
