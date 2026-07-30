<div class="section">
        <h2 class="section-title">Patient and order details</h2>

        <table class="details-table">
            <tr>
                <th>Patient</th>
                <td>{{ $patient['full_name'] ?? 'Not recorded' }}</td>
                <th>Medical record number</th>
                <td>{{ $patient['medical_record_number'] ?? 'Not recorded' }}</td>
            </tr>
            <tr>
                <th>Date of birth</th>
                <td>{{ $patient['date_of_birth'] ?? 'Not recorded' }}</td>
                <th>Gender</th>
                <td>{{ ucfirst((string) ($patient['gender'] ?? 'Not recorded')) }}</td>
            </tr>
            <tr>
                <th>Referring clinician</th>
                <td>{{ $order['referring_clinician'] ?? 'Not recorded' }}</td>
                <th>Sample collected</th>
                <td>{{ $order['sample_collected_at'] ?? 'Not recorded' }}</td>
            </tr>

            @if (! empty($order['clinical_notes']))
                <tr>
                    <th>Clinical notes</th>
                    <td colspan="3">{{ $order['clinical_notes'] }}</td>
                </tr>
            @endif
        </table>
    </div>