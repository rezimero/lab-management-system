<style>
        @page {
            margin: 28px 34px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #1f2937;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 10px;
            line-height: 1.45;
        }

        h1,
        h2,
        h3,
        p {
            margin: 0;
        }

        .header {
            border-bottom: 3px solid {{ $primaryColor }};
            padding-bottom: 14px;
        }

        .header-table,
        .summary-table,
        .details-table,
        .footer-table {
            border-collapse: collapse;
            width: 100%;
        }

        .logo-cell {
            width: 76px;
            vertical-align: middle;
        }

        .logo {
            display: block;
            max-height: 58px;
            max-width: 68px;
        }

        .identity-cell {
            vertical-align: middle;
        }

        .report-cell {
            text-align: right;
            vertical-align: middle;
            width: 175px;
        }

        .laboratory-name {
            color: {{ $primaryColor }};
            font-size: 21px;
            font-weight: 700;
        }

        .tagline {
            color: {{ $secondaryColor }};
            font-size: 10px;
            margin-top: 3px;
        }

        .report-number {
            font-size: 15px;
            font-weight: 700;
        }

        .issued {
            background: {{ $accentColor }};
            border-radius: 8px;
            color: #ffffff;
            display: inline-block;
            font-size: 8px;
            font-weight: 700;
            margin-top: 5px;
            padding: 3px 8px;
            text-transform: uppercase;
        }

        .section {
            margin-top: 16px;
        }

        .section-title {
            border-bottom: 1px solid {{ $primaryColor }};
            color: {{ $primaryColor }};
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 7px;
            padding-bottom: 4px;
            text-transform: uppercase;
        }

        .summary-table,
        .details-table {
            border: 1px solid #d1d5db;
        }

        .summary-table td,
        .details-table td,
        .details-table th {
            border: 1px solid #d1d5db;
            padding: 6px 7px;
            vertical-align: top;
        }

        .summary-table td {
            width: 33.333%;
        }

        .label {
            color: #6b7280;
            display: block;
            font-size: 8px;
            font-weight: 700;
            margin-bottom: 2px;
            text-transform: uppercase;
        }

        .details-table th {
            background: #f3f4f6;
            color: {{ $secondaryColor }};
            font-size: 8px;
            text-align: left;
            text-transform: uppercase;
        }

        .test-block {
            border: 1px solid #d1d5db;
            margin-bottom: 10px;
            page-break-inside: avoid;
        }

        .test-heading {
            background: {{ $primaryColor }};
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            padding: 6px 8px;
        }

        .test-category {
            font-size: 8px;
            font-weight: 400;
            margin-left: 5px;
        }

        .empty {
            border: 1px solid #d1d5db;
            color: #6b7280;
            padding: 12px;
            text-align: center;
        }

        .notes {
            background: #f9fafb;
            border-top: 1px solid #d1d5db;
            padding: 7px 8px;
        }

        .footer {
            border-top: 1px solid #9ca3af;
            margin-top: 18px;
            padding-top: 10px;
            page-break-inside: avoid;
        }

        .signature {
            display: block;
            max-height: 48px;
            max-width: 150px;
            margin-bottom: 4px;
        }

        .footer-right {
            text-align: right;
            vertical-align: bottom;
        }

        .footer-left {
            vertical-align: bottom;
        }

        .credential {
            color: #6b7280;
            font-size: 8px;
            margin-top: 2px;
        }

        .centred-footer {
            color: #4b5563;
            font-size: 8px;
            margin-top: 8px;
            text-align: center;
        }
    </style>