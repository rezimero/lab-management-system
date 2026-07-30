<div class="header">
        <table class="header-table">
            <tr>
                @if ($logoDataUri)
                    <td class="logo-cell">
                        <img
                            class="logo"
                            src="{{ $logoDataUri }}"
                            alt="Laboratory logo"
                        >
                    </td>
                @endif

                <td class="identity-cell">
                    <h1 class="laboratory-name">
                        {{ $laboratory['lab_name'] ?? 'Laboratory Report' }}
                    </h1>

                    @if (! empty($laboratory['tagline']))
                        <p class="tagline">
                            {{ $laboratory['tagline'] }}
                        </p>
                    @endif
                </td>

                <td class="report-cell">
                    <div class="report-number">
                        {{ $reportNumber }}
                    </div>
                    <span class="issued">Issued</span>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <table class="summary-table">
            <tr>
                <td>
                    <span class="label">Generated</span>
                    {{ $generatedAt }}
                </td>
                <td>
                    <span class="label">Order number</span>
                    {{ $order['order_number'] ?? 'Not recorded' }}
                </td>
                <td>
                    <span class="label">Priority</span>
                    {{ ucfirst((string) ($order['priority'] ?? 'Not recorded')) }}
                </td>
            </tr>
        </table>
    </div>