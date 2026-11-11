@extends('layouts.app')

@section('title', 'KIR / Kartu Inventaris Ruangan')

@section('content')
    <section class="page-heading">
        <div>
            <p class="eyebrow">LAPORAN</p>
            <h1>KIR / Kartu Inventaris Ruangan</h1>
            <p class="heading-copy">Pilih ruangan untuk melihat dan mengekspor Kartu Inventaris Ruangan dalam bentuk tabel.</p>
        </div>
    </section>

    <section class="content-card report-card">
        <form class="filter-bar report-filter-bar" method="GET" action="{{ route('reports.kir') }}">
            <label class="filter-select filter-select-wide">
                <span class="sr-only">Pilih ruangan</span>
                <select name="location_id" required>
                    <option value="">Pilih lokasi / ruangan</option>
                    @foreach ($locations as $locationOption)
                        <option value="{{ $locationOption->id }}" @selected((string) $filters['location_id'] === (string) $locationOption->id)>{{ $locationOption->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="filter-select">
                <span class="sr-only">Jenis barang</span>
                <select name="type">
                    <option value="">Aset &amp; sarana</option>
                    <option value="asset" @selected($filters['type'] === 'asset')>Barang aset</option>
                    <option value="facility" @selected($filters['type'] === 'facility')>Sarana dan prasarana</option>
                </select>
            </label>
            <label class="search-field">
                <span class="sr-only">Cari barang</span>
                <span class="search-icon">⌕</span>
                <input type="search" name="search" value="{{ $filters['search'] }}" placeholder="Cari nama, kode, kategori, atau keterangan...">
            </label>
            <button class="button button-secondary" type="submit">Terapkan</button>
            <a class="clear-link" href="{{ route('reports.kir') }}">Reset</a>
        </form>

        @if (! $location)
            <div class="report-placeholder">
                <div class="empty-icon">⌂</div>
                <h3>Pilih ruangan terlebih dahulu</h3>
                <p>Kartu Inventaris Ruangan akan ditampilkan dan dapat diekspor setelah lokasi / ruangan dipilih.</p>
            </div>
        @else
            @php
                $exportQuery = array_filter(request()->query(), fn ($value) => $value !== null && $value !== '');
            @endphp

            <div class="report-toolbar">
                <div>
                    <h2>{{ $location->name }}</h2>
                    <p>{{ number_format(count($report['rows']), 0, ',', '.') }} barang di ruangan ini.</p>
                </div>
                <div class="export-actions">
                    <a class="button button-secondary export-excel" href="{{ route('reports.kir.export', array_merge($exportQuery, ['format' => 'xlsx'])) }}">Excel</a>
                    <a class="button button-secondary export-word" href="{{ route('reports.kir.export', array_merge($exportQuery, ['format' => 'docx'])) }}">Word</a>
                    <a class="button button-secondary export-pdf" href="{{ route('reports.kir.export', array_merge($exportQuery, ['format' => 'pdf'])) }}">PDF</a>
                </div>
            </div>

            @include('reports.partials.preview', ['report' => $report])
        @endif
    </section>
@endsection
