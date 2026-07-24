const ReportPrintStyles = () => (
  <style>
    {`
      @media print {
        .no-print {
          display: none !important;
        }

        .report-paper {
          box-shadow: none !important;
          border: none !important;
          padding: 12px !important;
        }

        body {
          background: white !important;
        }
      }
    `}
  </style>
);

export default ReportPrintStyles;
