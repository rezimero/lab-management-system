<div class="section">
        <h2 class="section-title">Laboratory results</h2>

        @forelse ($results as $result)
            <div class="test-block">
                <div class="test-heading">
                    {{ $result['test_name'] ?? 'Laboratory test' }}

                    @if (! empty($result['category']))
                        <span class="test-category">
                            {{ $result['category'] }}
                        </span>
                    @endif
                </div>

                <table class="details-table">
                    <thead>
                        <tr>
                            <th>Parameter</th>
                            <th>Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (($result['results_data'] ?? []) as $key => $value)
                            <tr>
                                <td>{{ str_replace('_', ' ', ucfirst((string) $key)) }}</td>
                                <td>
                                    @if (is_array($value))
                                        {{ json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}
                                    @elseif (is_bool($value))
                                        {{ $value ? 'Yes' : 'No' }}
                                    @elseif ($value === null || $value === '')
                                        Not recorded
                                    @else
                                        {{ $value }}
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2">No result values recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                @if (! empty($result['notes']))
                    <div class="notes">
                        <span class="label">Notes</span>
                        {{ $result['notes'] }}
                    </div>
                @endif
            </div>
        @empty
            <div class="empty">No result items were recorded.</div>
        @endforelse
    </div>