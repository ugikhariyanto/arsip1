@extends('layouts.app')

@php
    $typeLabel = match ($type) {
        'asset' => 'Barang aset',
        'facility' => 'Sarana dan prasarana',
        'consumable' => 'Barang habis pakai',
        default => 'Data barang',
    };
@endphp

@section('title', $typeLabel)

@section('content')
    <section class="page-heading">
        <div>
            <p class="eyebrow">INVENTARIS</p>
            <h1>{{ $typeLabel }}</h1>
            <p class="heading-copy">{{ $type === 'asset' ? 'Kelola data aset puskesmas beserta keadaan, kalibrasi, dan lokasinya.' : ($type === 'facility' ? 'Kelola sarana dan prasarana puskesmas beserta keadaan dan lokasinya.' : ($type === 'consumable' ? 'Kelola stok barang habis pakai puskesmas.' : 'Kelola stok barang habis pakai, aset, serta sarana dan prasarana puskesmas.')) }}</p>
        </div>
        <a class="button button-primary" href="{{ route('inventory.create', $type ? ['type' => $type] : []) }}"><span>＋</span> Tambah barang</a>
    </section>

    <section class="content-card">
        <form class="filter-bar" method="GET" action="{{ route('inventory.index') }}">
            <label class="search-field">
                <span class="sr-only">Cari barang</span>
                <span class="search-icon">⌕</span>
                <input type="search" name="search" value="{{ $search }}" placeholder="Cari nama, kode, kategori, lokasi, atau keterangan...">
            </label>
            <label class="filter-select">
                <span class="sr-only">Filter jenis barang</span>
                <select name="type">
                    <option value="">Semua jenis</option>
                    <option value="consumable" @selected($type === 'consumable')>Barang habis pakai</option>
                    <option value="asset" @selected($type === 'asset')>Barang aset</option>
                    <option value="facility" @selected($type === 'facility')>Sarana dan prasarana</option>
                </select>
            </label>
            <label class="filter-select">
                <span class="sr-only">Filter keadaan barang</span>
                <select name="condition">
                    <option value="">Semua keadaan</option>
                    @foreach (\App\Models\InventoryItem::CONDITIONS as $conditionOption)
                        <option value="{{ $conditionOption }}" @selected($condition === $conditionOption)>{{ $conditionOption }}</option>
                    @endforeach
                </select>
            </label>
            <label class="filter-select filter-select-wide">
                <span class="sr-only">Filter lokasi</span>
                <select name="location_id">
                    <option value="">Semua lokasi</option>
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}" @selected((int) $locationId === $location->id)>{{ $location->name }}</option>
                    @endforeach
                </select>
            </label>
            <button class="button button-secondary" type="submit">Terapkan</button>
            @if ($search !== '' || $type || $condition || $locationId)
                <a class="clear-link" href="{{ route('inventory.index') }}">Reset</a>
            @endif
        </form>

        <div class="table-wrap">
            <table>
                <thead><tr><th>Foto</th><th>Nama barang</th><th>Kode</th><th>Jenis</th><th>Keadaan</th><th>Jumlah</th><th>Lokasi</th><th class="actions-heading">Aksi</th></tr></thead>
                <tbody>
                @forelse ($items as $item)
                    <tr>
                        <td>
                            @if ($item->photo_path)
                                <img class="item-thumbnail" src="{{ asset('storage/'.$item->photo_path) }}" alt="Foto {{ $item->name }}" loading="lazy">
                            @else
                                <span class="item-thumbnail-placeholder" aria-label="Tidak ada foto">▧</span>
                            @endif
                        </td>
                        <td><strong>{{ $item->name }}</strong><small class="cell-subtitle">{{ $item->category }}</small></td>
                        <td class="code-cell">{{ $item->code }}</td>
                        <td><span class="badge {{ $item->type === 'asset' ? 'badge-orange' : ($item->type === 'facility' ? 'badge-green' : 'badge-blue') }}">{{ $item->type_label }}</span></td>
                        <td><span class="condition-pill {{ $item->condition === 'Baik' ? 'condition-good' : ($item->isLost() ? 'condition-lost' : 'condition-damaged') }}">{{ $item->condition ?: '—' }}</span></td>
                        <td><strong>{{ number_format($item->quantity, 0, ',', '.') }}</strong> {{ $item->unit }}</td>
                        <td>{{ $item->hasLocation() ? ($item->location_name ?: '—') : '—' }}</td>
                        <td class="row-actions">
                            <a class="action-button action-view" href="{{ route('inventory.show', $item) }}">Lihat</a>
                            <a class="action-button action-edit" href="{{ route('inventory.edit', $item) }}">Ubah</a>
                            <form method="POST" action="{{ route('inventory.destroy', $item) }}" onsubmit="return confirm('Yakin ingin menghapus barang ini dari inventaris?')">
                                @csrf
                                @method('DELETE')
                                <button class="action-button action-delete" type="submit">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8">
                        <div class="empty-state compact">
                            <div class="empty-icon">⌕</div>
                            <h3>{{ $search !== '' || $type || $condition || $locationId ? 'Barang tidak ditemukan' : 'Belum ada data barang' }}</h3>
                            <p>{{ $search !== '' || $type || $condition || $locationId ? 'Coba ubah kata pencarian atau filter.' : 'Tambahkan barang pertama untuk mulai mengelola inventaris.' }}</p>
                            @if ($search === '' && ! $type && ! $condition && ! $locationId)
                                <a class="button button-primary" href="{{ route('inventory.create', $type ? ['type' => $type] : []) }}">Tambah barang</a>
                            @endif
                        </div>
                    </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($items->hasPages())
            <div class="pagination-wrap">{{ $items->links() }}</div>
        @endif
        <div class="table-foot">Menampilkan {{ $items->firstItem() ?? 0 }}–{{ $items->lastItem() ?? 0 }} dari {{ $items->total() }} barang</div>
    </section>
@endsection
