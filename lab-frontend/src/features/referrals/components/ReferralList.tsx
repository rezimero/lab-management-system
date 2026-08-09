import { useEffect, useMemo, useState } from "react";
import {
  Box,
  Button,
  CircularProgress,
  IconButton,
  MenuItem,
  Paper,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TablePagination,
  TableRow,
  TextField,
  Tooltip,
  Typography,
} from "@mui/material";
import SearchRoundedIcon from "@mui/icons-material/SearchRounded";
import AddRoundedIcon from "@mui/icons-material/AddRounded";
import AssignmentOutlinedIcon from "@mui/icons-material/AssignmentOutlined";
import CalendarMonthRoundedIcon from "@mui/icons-material/CalendarMonthRounded";
import MoreHorizRoundedIcon from "@mui/icons-material/MoreHorizRounded";
import ReferralStatusChip from "./ReferralStatusChip";
import ReferralDueDateBadge from "./ReferralDueDateBadge";

import type {
  Referral,
  ReferralLaboratory,
} from "../types/referral.types";

type Props = {
  referrals?: Referral[];
  laboratories?: ReferralLaboratory[];
  loading?: boolean;
  page?: number;
  rowsPerPage?: number;
  total?: number;
  onPageChange?: (page: number) => void;
  onSelect?: (uuid: string) => void;
  onNewReferral?: () => void;
};

type DatePreset = "all" | "today" | "last7" | "last30" | "last90" | "custom";

type StoredFilters = {
  datePreset: DatePreset;
  startDate: string;
  endDate: string;
  status: string;
  laboratory: string;
  search: string;
};

const FILTER_STORAGE_KEY =
  "rezhel.referrals.filters.v1";

const defaultFilters: StoredFilters = {
  datePreset: "all",
  startDate: "",
  endDate: "",
  status: "all",
  laboratory: "all",
  search: "",
};

function loadStoredFilters(): StoredFilters {
  if (typeof window === "undefined") {
    return defaultFilters;
  }

  try {
    const raw =
      window.sessionStorage.getItem(
        FILTER_STORAGE_KEY,
      );

    if (!raw) {
      return defaultFilters;
    }

    const parsed =
      JSON.parse(raw) as Partial<StoredFilters>;

    const validPresets: DatePreset[] = [
      "all",
      "today",
      "last7",
      "last30",
      "last90",
      "custom",
    ];

    const validStatuses = [
      "all",
      "draft",
      "dispatched",
      "acknowledged",
      "result_received",
      "completed",
      "rejected",
      "cancelled",
      "overdue",
    ];

    return {
      datePreset:
        validPresets.includes(
          parsed.datePreset as DatePreset,
        )
          ? parsed.datePreset as DatePreset
          : "all",
      startDate:
        typeof parsed.startDate === "string"
          ? parsed.startDate
          : "",
      endDate:
        typeof parsed.endDate === "string"
          ? parsed.endDate
          : "",
      status:
        typeof parsed.status === "string" &&
        validStatuses.includes(
          parsed.status,
        )
          ? parsed.status
          : "all",
      laboratory:
        typeof parsed.laboratory === "string"
          ? parsed.laboratory
          : "all",
      search:
        typeof parsed.search === "string"
          ? parsed.search
          : "",
    };
  } catch {
    return defaultFilters;
  }
}

function normalise(value: unknown) {
  return String(value ?? "").trim().toLowerCase();
}

function isoDate(date: Date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
}

function presetRange(preset: DatePreset) {
  const today = new Date();

  if (preset === "today") {
    const value = isoDate(today);
    return { start: value, end: value };
  }

  const days =
    preset === "last7"
      ? 6
      : preset === "last30"
        ? 29
        : preset === "last90"
          ? 89
          : null;

  if (days === null) {
    return { start: "", end: "" };
  }

  const start = new Date(today);
  start.setDate(today.getDate() - days);

  return { start: isoDate(start), end: isoDate(today) };
}

export default function ReferralList({
  referrals = [],
  laboratories = [],
  loading = false,
  page = 0,
  rowsPerPage = 10,
  total = 0,
  onPageChange,
  onSelect,
  onNewReferral,
}: Props) {
  const [
    initialFilters,
  ] = useState<StoredFilters>(
    loadStoredFilters,
  );

  const [
    datePreset,
    setDatePreset,
  ] = useState<DatePreset>(
    initialFilters.datePreset,
  );

  const [
    startDate,
    setStartDate,
  ] = useState(
    initialFilters.startDate,
  );

  const [
    endDate,
    setEndDate,
  ] = useState(
    initialFilters.endDate,
  );

  const [
    status,
    setStatus,
  ] = useState(
    initialFilters.status,
  );

  const [
    laboratory,
    setLaboratory,
  ] = useState(
    initialFilters.laboratory,
  );

  const [
    search,
    setSearch,
  ] = useState(
    initialFilters.search,
  );

  const hasFilters =
    datePreset !== "all" ||
    Boolean(startDate) ||
    Boolean(endDate) ||
    status !== "all" ||
    laboratory !== "all" ||
    Boolean(search.trim());

  const effectiveRange = useMemo(() => {
    if (datePreset === "custom") {
      return { start: startDate, end: endDate };
    }
    return presetRange(datePreset);
  }, [datePreset, startDate, endDate]);


  const invalidDateRange =
    datePreset === "custom" &&
    Boolean(startDate) &&
    Boolean(endDate) &&
    startDate > endDate;

  useEffect(() => {
    if (typeof window === "undefined") {
      return;
    }

    try {
      const filters: StoredFilters = {
        datePreset,
        startDate,
        endDate,
        status,
        laboratory,
        search,
      };

      window.sessionStorage.setItem(
        FILTER_STORAGE_KEY,
        JSON.stringify(filters),
      );
    } catch {
      // Filter persistence is a convenience only.
    }
  }, [
    datePreset,
    startDate,
    endDate,
    status,
    laboratory,
    search,
    invalidDateRange,
  ]);

  useEffect(() => {
    if (
      laboratory === "all" ||
      laboratories.length === 0
    ) {
      return;
    }

    const exists =
      laboratories.some(
        (lab) =>
          lab.uuid === laboratory ||
          lab.code === laboratory,
      );

    if (!exists) {
      setLaboratory("all");
    }
  }, [
    laboratories,
    laboratory,
  ]);

  useEffect(() => {
    onPageChange?.(0);
  }, [
    datePreset,
    startDate,
    endDate,
    status,
    laboratory,
    search,
    onPageChange,
  ]);

  const filteredReferrals = useMemo(() => {
    return referrals.filter((referral) => {
      if (invalidDateRange) {
        return false;
      }

      const patient = referral?.order_item?.order?.patient;
      const lab =
        referral?.laboratory ??
        referral?.referral_laboratory ??
        referral?.laboratory_snapshot;
      const createdAt =
        referral?.timestamps?.created_at ??
        referral?.created_at;

      const referralDate = createdAt
        ? String(createdAt).slice(0, 10)
        : "";

      const searchHaystack = [
        referral?.referral_number,
        referral?.external_reference,
        referral?.order_item?.order?.order_number,
        patient?.first_name,
        patient?.last_name,
        patient?.medical_record_number,
        lab?.name,
        lab?.code,
      ]
        .map(normalise)
        .join(" ");

      const matchesStart =
        !effectiveRange.start || referralDate >= effectiveRange.start;
      const matchesEnd =
        !effectiveRange.end || referralDate <= effectiveRange.end;
      const matchesStatus =
        status === "all" ||
        (
          status === "overdue"
            ? referral?.is_overdue === true
            : referral?.status === status
        );
      const matchesLaboratory =
        laboratory === "all" ||
        lab?.uuid === laboratory ||
        lab?.code === laboratory;
      const matchesSearch =
        !search.trim() || searchHaystack.includes(normalise(search));

      return (
        matchesStart &&
        matchesEnd &&
        matchesStatus &&
        matchesLaboratory &&
        matchesSearch
      );
    });
  }, [
    referrals,
    effectiveRange.start,
    effectiveRange.end,
    status,
    laboratory,
    search,
  ]);

  const clearFilters = () => {
    setDatePreset("all");
    setStartDate("");
    setEndDate("");
    setStatus("all");
    setLaboratory("all");
    setSearch("");
  };

  const visibleCount = hasFilters
    ? filteredReferrals.length
    : total || filteredReferrals.length;

  const safePage = Math.min(
    page,
    Math.max(0, Math.ceil(visibleCount / rowsPerPage) - 1),
  );

  const pagedReferrals = filteredReferrals.slice(
    safePage * rowsPerPage,
    safePage * rowsPerPage + rowsPerPage,
  );

  const labelSx = {
    display: "block",
    mb: 0.45,
    fontWeight: 730,
    color: "#344054",
    fontSize: "0.77rem",
  };

  const fieldSx = {
    "& .MuiInputBase-input": {
      color: "#344054",
      fontSize: "0.85rem",
    },
    "& .MuiInputBase-input::placeholder": {
      color: "#59677a",
      opacity: 1,
    },
  };

  return (
    <Paper
      component="section"
      aria-label="Referral list and filters"
      variant="outlined"
      sx={{
        borderRadius: 2.5,
        overflow: "hidden",
        bgcolor: "background.paper",
      }}
    >
      <Box
        sx={{
          px: { xs: 1.25, sm: 1.75 },
          py: 1.5,
          display: "flex",
          flexDirection: { xs: "column", xl: "row" },
          alignItems: { xs: "stretch", xl: "flex-end" },
          justifyContent: "space-between",
          gap: 1.5,
          borderBottom: "1px solid",
          borderColor: "divider",
        }}
      >
        <Box
          sx={{
            flex: 1,
            display: "grid",
            gridTemplateColumns: {
              xs: "1fr",
              sm: "repeat(2, minmax(0, 1fr))",
              lg: "minmax(170px, .85fr) minmax(150px, .72fr) minmax(170px, .8fr) minmax(220px, 1fr)",
            },
            gap: 1.1,
            alignItems: "end",
          }}
        >
          <Box>
            <Typography sx={labelSx}>Date range</Typography>

            <TextField
              select
              size="small"
              fullWidth
              value={datePreset}
              onChange={(event) =>
                setDatePreset(event.target.value as DatePreset)
              }
              sx={fieldSx}
            >
              <MenuItem value="all">All time</MenuItem>
              <MenuItem value="today">Today</MenuItem>
              <MenuItem value="last7">Last 7 days</MenuItem>
              <MenuItem value="last30">Last 30 days</MenuItem>
              <MenuItem value="last90">Last 90 days</MenuItem>
              <MenuItem value="custom">Custom range</MenuItem>
            </TextField>

            {datePreset === "custom" ? (
              <Box
                sx={{
                  mt: 0.75,
                  p: 1,
                  borderRadius: 1.75,
                  bgcolor: "#f8fafc",
                  border: "1px solid",
                  borderColor: "divider",
                  display: "grid",
                  gridTemplateColumns: {
                    xs: "1fr",
                    sm: "1fr auto 1fr",
                  },
                  gap: 0.65,
                  alignItems: "end",
                }}
              >
                <Box>
                  <Typography
                    sx={{
                      mb: 0.3,
                      color: "#526175",
                      fontSize: "0.7rem",
                      fontWeight: 650,
                    }}
                  >
                    Start date
                  </Typography>
                  <TextField
                    type="date"
                    size="small"
                    fullWidth
                    value={startDate}
                    onChange={(event) => setStartDate(event.target.value)}
                    sx={fieldSx}
                  />
                </Box>

                <Typography
                  sx={{
                    display: { xs: "none", sm: "block" },
                    mb: 1.15,
                    color: "#667085",
                  }}
                >
                  –
                </Typography>

                <Box>
                  <Typography
                    sx={{
                      mb: 0.3,
                      color: "#526175",
                      fontSize: "0.7rem",
                      fontWeight: 650,
                    }}
                  >
                    End date
                  </Typography>
                  <TextField
                    type="date"
                    size="small"
                    fullWidth
                    value={endDate}
                    onChange={(event) => setEndDate(event.target.value)}
                    sx={fieldSx}
                  />
                </Box>

                {invalidDateRange ? (
                  <Typography
                    role="alert"
                    sx={{
                      gridColumn: "1 / -1",
                      color: "error.main",
                      fontSize: "0.72rem",
                      fontWeight: 650,
                    }}
                  >
                    End date must be on or after the start date.
                  </Typography>
                ) : null}
              </Box>
            ) : null}
          </Box>

          <Box>
            <Typography sx={labelSx}>Status</Typography>
            <TextField
              select
              size="small"
              fullWidth
              value={status}
              onChange={(event) => setStatus(event.target.value)}
              sx={fieldSx}
            >
              <MenuItem value="all">All statuses</MenuItem>
              <MenuItem value="draft">Draft</MenuItem>
              <MenuItem value="dispatched">Dispatched</MenuItem>
              <MenuItem value="acknowledged">Acknowledged</MenuItem>
              <MenuItem value="result_received">Result received</MenuItem>
              <MenuItem value="completed">Completed</MenuItem>
              <MenuItem value="rejected">Rejected</MenuItem>
              <MenuItem value="cancelled">Cancelled</MenuItem>
              <MenuItem value="overdue">Overdue</MenuItem>
            </TextField>
          </Box>

          <Box>
            <Typography sx={labelSx}>Laboratory</Typography>
            <TextField
              select
              size="small"
              fullWidth
              value={laboratory}
              onChange={(event) => setLaboratory(event.target.value)}
              sx={fieldSx}
            >
              <MenuItem value="all">All laboratories</MenuItem>
              {laboratories.map((lab) => (
                <MenuItem
                  key={lab.uuid ?? lab.code ?? lab.name}
                  value={lab.uuid ?? lab.code ?? lab.name}
                >
                  {lab.name ?? lab.code ?? "Laboratory"}
                </MenuItem>
              ))}
            </TextField>
          </Box>

          <Box>
            <Typography sx={labelSx}>Search</Typography>
            <Box sx={{ position: "relative" }}>
              <SearchRoundedIcon
                sx={{
                  position: "absolute",
                  left: 12,
                  top: "50%",
                  transform: "translateY(-50%)",
                  fontSize: 19,
                  color: "#59677a",
                  pointerEvents: "none",
                  zIndex: 1,
                }}
              />

              <TextField
                size="small"
                fullWidth
                value={search}
                onChange={(event) => setSearch(event.target.value)}
                placeholder="Referral, patient or laboratory"
                sx={{
                  ...fieldSx,
                  "& .MuiInputBase-input": {
                    pl: 5,
                    color: "#344054",
                    fontSize: "0.85rem",
                  },
                }}
              />
            </Box>
          </Box>
        </Box>

        <Box
          sx={{
            display: "flex",
            flexDirection: { xs: "column-reverse", sm: "row" },
            alignItems: { xs: "stretch", sm: "center" },
            justifyContent: "flex-end",
            gap: 0.75,
            flexShrink: 0,
          }}
        >
          <Button
            size="small"
            variant="text"
            onClick={clearFilters}
            disabled={!hasFilters}
            sx={{
              minHeight: 40,
              whiteSpace: "nowrap",
              color: "#536176",
              px: 1,
            }}
          >
            Clear filters
          </Button>

          {onNewReferral ? (
            <Button
              size="small"
              variant="contained"
              startIcon={<AddRoundedIcon />}
              aria-label="Create new referral"
              onClick={onNewReferral}
              sx={{
                minHeight: 40,
                whiteSpace: "nowrap",
                boxShadow: "none",
                px: 1.75,
              }}
            >
              New Referral
            </Button>
          ) : null}
        </Box>
      </Box>

      <TableContainer
        sx={{
          minHeight: 126,
          overflowX: "auto",
        }}
      >
        <Table
          size="small"
          aria-label="Referrals"
          sx={{
            minWidth: {
              xs: 900,
              lg: "auto",
            },
          }}
        >
          <TableHead>
            <TableRow sx={{ bgcolor: "#f7f9fc" }}>
              {[
                "Referral",
                "Patient",
                "Laboratory",
                "Status",
                "Due Date",
                "Created By",
                "Actions",
              ].map((label) => (
                <TableCell
                  key={label}
                  sx={{
                    py: 1.15,
                    fontWeight: 730,
                    color: "#344054",
                    borderBottomColor: "divider",
                    fontSize: "0.79rem",
                  }}
                  align={label === "Actions" ? "right" : "left"}
                >
                  {label}
                </TableCell>
              ))}
            </TableRow>
          </TableHead>

          <TableBody>
            {loading ? (
              <TableRow>
                <TableCell colSpan={7}>
                  <Box
                    sx={{
                      minHeight: 92,
                      display: "grid",
                      placeItems: "center",
                    }}
                  >
                    <Box
                      role="status"
                      aria-live="polite"
                      sx={{
                        display: "flex",
                        alignItems: "center",
                        gap: 1,
                      }}
                    >
                      <CircularProgress
                        size={18}
                        thickness={5}
                      />
                      <Typography
                        sx={{
                          color: "#344054",
                          fontSize: "0.83rem",
                          fontWeight: 650,
                        }}
                      >
                        Loading referrals...
                      </Typography>
                    </Box>
                  </Box>
                </TableCell>
              </TableRow>
            ) : filteredReferrals.length === 0 ? (
              <TableRow>
                <TableCell colSpan={7} sx={{ borderBottom: 0 }}>
                  <Box
                    sx={{
                      minHeight: 88,
                      px: 2,
                      py: 1.3,
                      display: "flex",
                      alignItems: "center",
                      justifyContent: "center",
                    }}
                  >
                    <Box
                      sx={{
                        width: "100%",
                        maxWidth: 560,
                        display: "flex",
                        alignItems: "center",
                        gap: 1.25,
                        px: 1.5,
                        py: 1.15,
                        borderRadius: 2,
                        bgcolor: "#f8fbff",
                        border: "1px dashed #cfdae8",
                      }}
                    >
                      <Box
                        sx={{
                          width: 42,
                          height: 42,
                          borderRadius: "50%",
                          bgcolor: "#e9f2fb",
                          color: "#7893ad",
                          display: "grid",
                          placeItems: "center",
                          flexShrink: 0,
                        }}
                      >
                        {hasFilters ? (
                          <CalendarMonthRoundedIcon sx={{ fontSize: 22 }} />
                        ) : (
                          <AssignmentOutlinedIcon sx={{ fontSize: 22 }} />
                        )}
                      </Box>

                      <Box>
                        <Typography
                          sx={{
                            fontWeight: 730,
                            color: "#202939",
                            fontSize: "0.85rem",
                          }}
                        >
                          {hasFilters
                            ? "No referrals match these filters"
                            : "No referrals yet"}
                        </Typography>

                        <Typography
                          sx={{
                            color: "#526175",
                            mt: 0.22,
                            fontSize: "0.75rem",
                            lineHeight: 1.35,
                          }}
                        >
                          {hasFilters
                            ? invalidDateRange
                              ? "Correct the custom date range or clear the filters."
                              : "Adjust the date, status, laboratory or search criteria."
                            : onNewReferral
                              ? "Use New Referral above when a test needs to be sent to an external laboratory."
                              : "No referrals are currently available."}
                        </Typography>

                        {hasFilters ? (
                          <Button
                            size="small"
                            variant="text"
                            onClick={clearFilters}
                            sx={{
                              mt: 0.35,
                              minWidth: 0,
                              px: 0,
                            }}
                          >
                            Clear filters
                          </Button>
                        ) : null}
                      </Box>
                    </Box>
                  </Box>
                </TableCell>
              </TableRow>
            ) : (
              pagedReferrals.map((referral) => {
                const patient = referral?.order_item?.order?.patient;
                const creator =
                  referral?.created_by?.name ??
                  referral?.creator?.name ??
                  referral?.created_by_name ??
                  "-";

                return (
                  <TableRow
                    hover
                    key={referral.uuid}
                    role="button"
                    tabIndex={0}
                    aria-label={`Open referral ${referral.referral_number ?? ""}`}
                    onClick={() => onSelect?.(referral.uuid)}
                    onKeyDown={(event) => {
                      if (
                        event.key === "Enter" ||
                        event.key === " "
                      ) {
                        event.preventDefault();
                        onSelect?.(referral.uuid);
                      }
                    }}
                    sx={{
                      cursor: "pointer",
                      "&:focus-visible": {
                        outline: "2px solid",
                        outlineColor: "primary.main",
                        outlineOffset: -2,
                      },
                    }}
                  >
                    <TableCell>
                      <Typography variant="body2" sx={{ fontWeight: 700 }}>
                        {referral.referral_number ?? "-"}
                      </Typography>
                    </TableCell>
                    <TableCell>
                      <Typography variant="body2">
                        {[patient?.first_name, patient?.last_name]
                          .filter(Boolean)
                          .join(" ") || "-"}
                      </Typography>
                      {patient?.medical_record_number ? (
                        <Typography sx={{ color: "#526175", fontSize: "0.72rem" }}>
                          {patient.medical_record_number}
                        </Typography>
                      ) : null}
                    </TableCell>
                    <TableCell>{referral?.laboratory?.name ??
                        referral?.referral_laboratory?.name ??
                        referral?.laboratory_snapshot?.name ??
                        "-"}</TableCell>
                    <TableCell>
                      <ReferralStatusChip status={referral.status} />
                    </TableCell>
                    <TableCell>
                      <ReferralDueDateBadge
                        dueDate={referral.turnaround_due_at}
                        status={referral.status}
                        isOverdue={referral.is_overdue}
                      />
                    </TableCell>
                    <TableCell>{creator}</TableCell>
                    <TableCell align="right">
                      <Tooltip title="Open referral">
                        <IconButton
                          size="small"
                          onClick={(event) => {
                            event.stopPropagation();
                            onSelect?.(referral.uuid);
                          }}
                        >
                          <MoreHorizRoundedIcon fontSize="small" />
                        </IconButton>
                      </Tooltip>
                    </TableCell>
                  </TableRow>
                );
              })
            )}
          </TableBody>
        </Table>
      </TableContainer>

      <Box sx={{ borderTop: "1px solid", borderColor: "divider" }}>
        <TablePagination
          component="div"
          count={visibleCount}
          page={safePage}
          rowsPerPage={rowsPerPage}
          rowsPerPageOptions={[rowsPerPage]}
          onPageChange={(_, newPage) => onPageChange?.(newPage)}
          sx={{
            "& .MuiTablePagination-toolbar": { minHeight: 40 },
            "& .MuiTablePagination-displayedRows": {
              color: "#526175",
              fontSize: "0.77rem",
            },
          }}
        />
      </Box>
    </Paper>
  );
}
