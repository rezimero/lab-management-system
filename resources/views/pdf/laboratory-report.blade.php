<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $reportNumber }}</title>

    @include('pdf.laboratory-report.styles')
</head>
<body>
    @include('pdf.laboratory-report.header')
    @include('pdf.laboratory-report.patient-order')
    @include('pdf.laboratory-report.results')
    @include('pdf.laboratory-report.footer')
</body>
</html>