@extends('layouts.app')

@section('title', 'Ringkasan')

@section('content')
    <section class="page-heading">
        <div>
            <p class="eyebrow">DASHBOARD</p>
            <h1>Ringkasan inventaris</h1>
            <p class="heading-copy">Pantau barang habis pakai dan aset puskesmas dalam satu tempat.</p>
        </div>
        <a class="button button-primary" href="{{ route('inventory.create') }}"><span>＋</span> Tambah barang</a>
    </section>

    <section class="stat-grid" aria-label="Ringkasan data">
        <article class="stat-card">
            <div class="stat-top"><span class="stat-icon icon-green">▤</span><span class="stat-caption">SEMUA JENIS</span></div>
            <div class="stat-value">{{ number_format($totalItems, 0, ',', '.') }}</div>
            <div class="stat-label">Data barang</div>
        </article>
        <article class="stat-card">
            <div class="stat-top"><span class="stat-icon icon-blue">◫</span><span class="stat-caption">BARANG HABIS PAKAI</span></div>
            <div class="stat-value">{{ number_format($consumableCount, 0, ',', '.') }}</div>
            <div class="stat-label">Jenis barang terdaftar</div>
        </article>
        <article class="stat-card">
            <div class="stat-top"><span class="stat-icon icon-orange">⌑</span><span class="stat-caption">BARANG ASET</span></div>
            <div class="stat-value">{{ number_format($assetCount, 0, ',', '.') }}</div>
            <div class="stat-label">Jenis aset terdaftar</div>
        </article>
        <article class="stat-card">
            <div class="stat-top"><span class="stat-icon icon-purple">▦</span><span class="stat-caption">TOTAL STOK</span></div>
            <div class="stat-value">{{ number_format($totalConsumables, 0, ',', '.') }}</div>
            <div class="stat-label">Unit barang habis pakai</div>
        </article>
    </section>

    <section class="content-card">
        <div class="section-heading">
            <div><h2>Barang terbaru</h2><p>Data inventaris yang terakhir ditambahkan atau diperbarui.</p></div>
            <a class="text-link" href="{{ route('inventory.index') }}">Lihat semua <span>→</span></a>
        </div>
        @if ($recentItems->isEmpty())
            <div class="empty-state">
                <div class="empty-icon">▤</div>
                <h3>Belum ada barang</h3>
                <p>Mulai dengan menambahkan data barang puskesmas.</p>
                <a class="button button-primary" href="{{ route('inventory.create') }}">Tambah barang pertama</a>
            </div>
        @else
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Foto</th><th>Nama barang</th><th>Kode</th><th>Jenis</th><th>Jumlah</th><th>Lokasi</th><th>Aksi</th></tr></thead>
                    <tbody>
                    @foreach ($recentItems as $item)
                        <tr>
                            <td>
                                @if ($item->photo_path)
                                    <img class="item-thumbnail" src="{{ asset('storage/'.$item->photo_path) }}" alt="Foto {{ $item->name }}" loading="lazy">
                                @else
                                    <span class="item-thumbnail-placeholder" aria-label="Tidak ada foto">▧</span>
                                @endif
                            </td>
                            <td><a class="item-name-link" href="{{ route('inventory.show', $item) }}"><strong>{{ $item->name }}</strong></a><small class="cell-subtitle">{{ $item->category }}</small></td>
                            <td class="code-cell">{{ $item->code }}</td>
                            <td><span class="badge {{ $item->type === 'asset' ? 'badge-orange' : 'badge-blue' }}">{{ $item->type_label }}</span></td>
                            <td>{{ number_format($item->quantity, 0, ',', '.') }} {{ $item->unit }}</td>
                            <td>{{ $item->location ?: '—' }}</td>
                            <td><a class="action-button action-view" href="{{ route('inventory.show', $item) }}">Lihat barang</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
