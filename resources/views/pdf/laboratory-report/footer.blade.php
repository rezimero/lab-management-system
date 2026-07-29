<div class="footer">
        <table class="footer-table">
            <tr>
                <td class="footer-left">
                    @if ($signatureDataUri)
                        <img
                            class="signature"
                            src="{{ $signatureDataUri }}"
                            alt="Director signature"
                        >
                    @endif

                    <strong>
                        {{ $laboratory['signatory_designation'] ?? 'Laboratory director' }}
                    </strong>

                    <div>
                        {{ $laboratory['director_name'] ?? 'Not recorded' }}
                    </div>

                    @if (! empty($laboratory['director_qualification']))
                        <div class="credential">
                            {{ $laboratory['director_qualification'] }}
                        </div>
                    @endif

                    @if (! empty($laboratory['director_registration_number']))
                        <div class="credential">
                            Professional registration:
                            {{ $laboratory['director_registration_number'] }}
                        </div>
                    @endif
                </td>

                <td class="footer-right">
                    <strong>
                        {{ $laboratory['lab_name'] ?? 'Laboratory' }}
                    </strong>

                    <div class="credential">
                        {{ $laboratory['full_address'] ?? ($laboratory['address'] ?? 'Address not recorded') }}
                    </div>

                    @if (! empty($laboratory['license_number']))
                        <div class="credential">
                            Licence: {{ $laboratory['license_number'] }}
                        </div>
                    @endif

                    @if (! empty($laboratory['registration_number']))
                        <div class="credential">
                            Registration: {{ $laboratory['registration_number'] }}
                        </div>
                    @endif
                </td>
            </tr>
        </table>

        @if (! empty($laboratory['accreditation_details']))
            <div class="centred-footer">
                <strong>{{ $laboratory['accreditation_details'] }}</strong>
            </div>
        @endif

        @if (! empty($laboratory['report_footer']))
            <div class="centred-footer">
                {{ $laboratory['report_footer'] }}
            </div>
        @endif
    </div>