const TestResultPrintStyles = () => (
  <style>
    {`
      @media print {
        .no-print {
          display: none !important;
        }

        .print-paper {
          box-shadow: none !important;
          border: none !important;
          padding: 20px !important;
        }

        body {
          background: white !important;
        }
      }
    `}
  </style>
);

export default TestResultPrintStyles;
