@extends('layouts.app')

@section('title', 'Laporan barang')

@section('content')
    <section class="page-heading">
        <div>
            <p class="eyebrow">LAPORAN</p>
            <h1>Laporan aset / sarana dan prasarana</h1>
            <p class="heading-copy">Filter barang aset, sarana dan prasarana, maupun barang habis pakai, lalu ekspor ke Excel, Word, atau PDF. Tanggal ekspor dan informasi filter disertakan otomatis.</p>
        </div>
    </section>

    <section class="content-card report-card">
        <form class="filter-bar report-filter-bar" method="GET" action="{{ route('reports.items') }}">
            <label class="search-field">
                <span class="sr-only">Cari barang</span>
                <span class="search-icon">⌕</span>
                <input type="search" name="search" value="{{ $filters['search'] }}" placeholder="Cari nama, kode, kategori, lokasi, atau keterangan...">
            </label>
            <label class="filter-select">
                <span class="sr-only">Jenis barang</span>
                <select name="type">
                    <option value="">Semua jenis</option>
                    <option value="consumable" @selected($filters['type'] === 'consumable')>Barang habis pakai</option>
                    <option value="asset" @selected($filters['type'] === 'asset')>Barang aset</option>
                    <option value="facility" @selected($filters['type'] === 'facility')>Sarana dan prasarana</option>
                </select>
            </label>
            <label class="filter-select">
                <span class="sr-only">Kategori</span>
                <select name="category_id">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) $filters['category_id'] === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="filter-select filter-select-wide">
                <span class="sr-only">Lokasi</span>
                <select name="location_id">
                    <option value="">Semua lokasi</option>
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}" @selected((string) $filters['location_id'] === (string) $location->id)>{{ $location->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="filter-select">
                <span class="sr-only">Keadaan</span>
                <select name="condition">
                    <option value="">Semua keadaan</option>
                    @foreach (\App\Models\InventoryItem::CONDITIONS as $condition)
                        <option value="{{ $condition }}" @selected($filters['condition'] === $condition)>{{ $condition }}</option>
                    @endforeach
                </select>
            </label>
            <button class="button button-secondary" type="submit">Terapkan</button>
            <a class="clear-link" href="{{ route('reports.items') }}">Reset</a>
        </form>

        @php
            $exportQuery = array_filter(request()->query(), fn ($value) => $value !== null && $value !== '');
        @endphp

        <div class="report-toolbar">
            <div>
                <h2>Siap diekspor</h2>
                <p>{{ number_format(count($report['rows']), 0, ',', '.') }} barang sesuai filter saat ini.</p>
            </div>
            <div class="export-actions">
                <a class="button button-secondary export-excel" href="{{ route('reports.items.export', array_merge($exportQuery, ['format' => 'xlsx'])) }}">Excel</a>
                <a class="button button-secondary export-word" href="{{ route('reports.items.export', array_merge($exportQuery, ['format' => 'docx'])) }}">Word</a>
                <a class="button button-secondary export-pdf" href="{{ route('reports.items.export', array_merge($exportQuery, ['format' => 'pdf'])) }}">PDF</a>
            </div>
        </div>

        @include('reports.partials.preview', ['report' => $report])
    </section>
@endsection
