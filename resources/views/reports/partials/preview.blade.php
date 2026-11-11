@php
    $columns = $report['columns'] ?? [];
    $rows = $report['rows'] ?? [];
@endphp

<div class="report-preview">
    <div class="report-preview-heading">
        <h2>{{ $report['title'] ?? 'Laporan' }}</h2>
        @if (! empty($report['subtitle']))
            <p class="report-subtitle">{{ $report['subtitle'] }}</p>
        @endif
        @if (! empty($report['meta']))
            <div class="report-meta">
                @foreach ($report['meta'] as $label => $value)
                    <span><strong>{{ $label }}:</strong> {{ $value }}</span>
                @endforeach
            </div>
        @endif
    </div>

    @if ($rows === [])
        <div class="empty-state compact">
            <div class="empty-icon">⌕</div>
            <h3>Belum ada data</h3>
            <p>Tidak ada barang yang sesuai dengan filter yang dipilih.</p>
        </div>
    @else
        <div class="table-wrap">
            <table class="report-table">
                <thead>
                    <tr>
                        @foreach ($columns as $column)
                            <th class="{{ $column['align'] ?? 'left' }}">{{ $column['label'] ?? '' }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            @foreach ($columns as $index => $column)
                                <td class="{{ $column['align'] ?? 'left' }}">{{ $row[$index] ?? '' }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="table-foot">Menampilkan {{ number_format(count($rows), 0, ',', '.') }} barang</div>
    @endif
</div>
