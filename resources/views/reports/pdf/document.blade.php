<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $report['title'] ?? 'Laporan' }}</title>
    <style>
        @page {
            size: A4 {{ ($report['orientation'] ?? 'landscape') }};
            margin: 14mm 10mm 14mm 10mm;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: #111827;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 8px;
            line-height: 1.35;
        }
        h1 {
            margin: 0 0 3px;
            font-size: 16px;
            letter-spacing: .4px;
            text-align: center;
            text-transform: uppercase;
        }
        h2 {
            margin: 0 0 8px;
            font-size: 12px;
            text-align: center;
            text-transform: uppercase;
        }
        .meta {
            width: 100%;
            margin: 0 0 9px;
            padding: 6px 8px;
            border: 1px solid #d1d5db;
            background: #f9fafb;
        }
        .meta span {
            display: inline-block;
            min-width: 48%;
            margin: 1px 0;
        }
        .meta strong { color: #374151; }
        table.data {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        table.data th,
        table.data td {
            padding: 2px 3px;
            border: 1px solid #9ca3af;
            vertical-align: top;
            overflow-wrap: break-word;
            word-wrap: break-word;
        }
        table.data th {
            background: #e5e7eb;
            font-size: 7px;
            text-align: center;
            text-transform: uppercase;
            word-break: keep-all;
            overflow-wrap: normal;
        }
        table.data td.center { text-align: center; }
        table.data td.right { text-align: right; }
        table.data tr { page-break-inside: avoid; }
        .empty {
            padding: 14px;
            border: 1px solid #9ca3af;
            text-align: center;
        }
        .footer {
            margin-top: 8px;
            color: #6b7280;
            font-size: 7px;
            text-align: right;
        }
    </style>
</head>
<body>
    <h1>{{ $report['title'] ?? 'Laporan' }}</h1>
    @if (! empty($report['subtitle']))
        <h2>{{ $report['subtitle'] }}</h2>
    @endif

    @if (! empty($report['meta']))
        <div class="meta">
            @foreach ($report['meta'] as $label => $value)
                <span><strong>{{ $label }}:</strong> {{ $value }}</span>
            @endforeach
        </div>
    @endif

    @php
        $columns = $report['columns'] ?? [];
        $rows = $report['rows'] ?? [];
        $widthTotal = array_sum(array_map(fn ($column) => max(4, (float) ($column['width'] ?? 12)), $columns));
    @endphp

    @if ($rows === [])
        <div class="empty">Tidak ada data yang sesuai dengan filter.</div>
    @else
        <table class="data">
            <thead>
                <tr>
                    @foreach ($columns as $column)
                        <th style="width: {{ $widthTotal > 0 ? round((max(4, (float) ($column['width'] ?? 12)) / $widthTotal) * 100, 3) : 0 }}%">{{ $column['label'] ?? '' }}</th>
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
    @endif

    <div class="footer">Dicetak dari Arsip Sehat · {{ $report['meta']['Tanggal ekspor'] ?? '' }}</div>
</body>
</html>
