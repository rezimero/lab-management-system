const ReportPrintStyles = () => (
  <style data-testid="report-print-styles">
    {`
      @page {
        margin: 10mm;
      }

      @media print {
        html,
        body,
        #root {
          width: 100% !important;
          min-width: 0 !important;
          min-height: 0 !important;
          margin: 0 !important;
          padding: 0 !important;
          overflow: visible !important;
          background: #FFFFFF !important;
        }

        body {
          print-color-adjust: exact;
          -webkit-print-color-adjust: exact;
        }

        #root > .MuiBox-root,
        main,
        main > .MuiBox-root {
          width: 100% !important;
          max-width: none !important;
          min-height: 0 !important;
          margin: 0 !important;
          padding: 0 !important;
          overflow: visible !important;
        }

        .MuiDrawer-root,
        .MuiAppBar-root,
        .no-print {
          display: none !important;
        }

        body * {
          visibility: hidden !important;
        }

        .report-print-root,
        .report-print-root * {
          visibility: visible !important;
        }

        .report-print-root {
          position: static !important;
          display: block !important;
          width: 100% !important;
          max-width: none !important;
          margin: 0 !important;
          padding: 0 !important;
        }

        .report-paper {
          width: 100% !important;
          max-width: none !important;
          margin: 0 !important;
          padding: 0 !important;
          border: none !important;
          border-radius: 0 !important;
          box-shadow: none !important;
          font-size: 9pt !important;
          line-height: 1.25 !important;
        }

        .report-paper .MuiTypography-root {
          line-height: 1.25 !important;
          orphans: 3;
          widows: 3;
        }

        .report-paper .MuiTypography-h4 {
          font-size: 15pt !important;
          line-height: 1.15 !important;
        }

        .report-laboratory-logo {
          width: auto !important;
          max-width: 24mm !important;
          max-height: 14mm !important;
          object-fit: contain !important;
        }

        .report-paper .MuiTypography-h6 {
          font-size: 10.5pt !important;
        }

        .report-paper .MuiTypography-subtitle1 {
          font-size: 9.5pt !important;
        }

        .report-paper .MuiTypography-subtitle2 {
          font-size: 8.5pt !important;
        }

        .report-paper .MuiTypography-body2 {
          font-size: 8pt !important;
        }

        .report-paper .MuiTypography-caption {
          font-size: 7pt !important;
        }

        .report-paper .MuiDivider-root {
          margin-top: 7px !important;
          margin-bottom: 7px !important;
        }

        .report-summary {
          grid-template-columns:
            repeat(
              auto-fit,
              minmax(min(42mm, 100%), 1fr)
            ) !important;
          gap: 8px !important;
          padding: 8px !important;
          margin-bottom: 8px !important;
          break-inside: avoid-page;
          page-break-inside: avoid;
        }

        .report-patient-order {
          display: grid !important;
          grid-template-columns:
            repeat(
              auto-fit,
              minmax(min(70mm, 100%), 1fr)
            ) !important;
          gap: 8px !important;
          margin-bottom: 8px !important;
        }

        .report-patient-order > .MuiGrid-root {
          width: auto !important;
          min-width: 0 !important;
          max-width: none !important;
          flex-basis: auto !important;
        }

        .report-detail-card {
          height: auto !important;
          padding: 8px !important;
          break-inside: avoid-page;
          page-break-inside: avoid;
        }

        .report-detail-card .MuiTypography-subtitle1 {
          margin-bottom: 5px !important;
        }

        .report-detail-card .MuiTypography-body2 {
          margin-bottom: 3px !important;
        }

        .report-results-section {
          margin-bottom: 8px !important;
        }

        .report-results-section > .MuiTypography-h6 {
          margin-bottom: 5px !important;
        }

        .report-result-card {
          margin-bottom: 8px !important;
          overflow: visible !important;
        }

        .report-result-card >
        .MuiTypography-subtitle1 {
          padding: 6px 8px 0 !important;
        }

        .report-result-card >
        .MuiTypography-caption {
          padding: 0 8px !important;
          margin-bottom: 4px !important;
        }

        .report-result-card >
        .MuiTypography-body2 {
          padding: 6px 8px !important;
        }

        .report-paper .MuiTableContainer-root {
          overflow: visible !important;
        }

        .report-paper .MuiTableHead-root {
          display: table-header-group;
        }

        .report-paper .MuiTableRow-root {
          break-inside: avoid;
          page-break-inside: avoid;
        }

        .report-paper .MuiTableCell-root {
          padding: 2px 4px !important;
          font-size: 7.5pt !important;
          line-height: 1.2 !important;
        }

        .report-paper .MuiChip-root {
          height: 16px !important;
        }

        .report-paper .MuiChip-label {
          padding-left: 5px !important;
          padding-right: 5px !important;
          font-size: 7pt !important;
        }

        .report-signature-footer {
          margin-top: 8px !important;
          break-inside: avoid-page;
          page-break-inside: avoid;
        }
      }
    `}
  </style>
);

export default ReportPrintStyles;