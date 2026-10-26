@extends('layouts.app')

@section('title', $type === 'asset' ? 'Barang aset' : ($type === 'consumable' ? 'Barang habis pakai' : 'Data barang'))

@section('content')
    <section class="page-heading">
        <div>
            <p class="eyebrow">INVENTARIS</p>
            <h1>{{ $type === 'asset' ? 'Barang aset' : ($type === 'consumable' ? 'Barang habis pakai' : 'Data barang') }}</h1>
            <p class="heading-copy">{{ $type === 'asset' ? 'Kelola data aset puskesmas beserta kondisi dan lokasi.' : ($type === 'consumable' ? 'Kelola stok barang habis pakai puskesmas.' : 'Kelola stok barang habis pakai dan pencatatan aset puskesmas.') }}</p>
        </div>
        <a class="button button-primary" href="{{ route('inventory.create', $type ? ['type' => $type] : []) }}"><span>＋</span> Tambah barang</a>
    </section>

    <section class="content-card">
        <form class="filter-bar" method="GET" action="{{ route('inventory.index') }}">
            <label class="search-field">
                <span class="sr-only">Cari barang</span>
                <span class="search-icon">⌕</span>
                <input type="search" name="search" value="{{ $search }}" placeholder="Cari nama, kode, kategori, atau lokasi...">
            </label>
            <label class="filter-select">
                <span class="sr-only">Filter jenis barang</span>
                <select name="type">
                    <option value="">Semua jenis</option>
                    <option value="consumable" @selected($type === 'consumable')>Barang habis pakai</option>
                    <option value="asset" @selected($type === 'asset')>Barang aset</option>
                </select>
            </label>
            <button class="button button-secondary" type="submit">Terapkan</button>
            @if ($search !== '' || in_array($type, ['consumable', 'asset'], true))
                <a class="clear-link" href="{{ route('inventory.index') }}">Reset</a>
            @endif
        </form>

        <div class="table-wrap">
            <table>
                <thead><tr><th>Foto</th><th>Nama barang</th><th>Kode</th><th>Jenis</th><th>Jumlah</th><th>Lokasi</th><th class="actions-heading">Aksi</th></tr></thead>
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
                        <td><span class="badge {{ $item->type === 'asset' ? 'badge-orange' : 'badge-blue' }}">{{ $item->type_label }}</span></td>
                        <td><strong>{{ number_format($item->quantity, 0, ',', '.') }}</strong> {{ $item->unit }}</td>
                        <td>{{ $item->location ?: '—' }}</td>
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
                    <tr><td colspan="7">
                        <div class="empty-state compact">
                            <div class="empty-icon">⌕</div>
                            <h3>{{ $search !== '' || $type ? 'Barang tidak ditemukan' : 'Belum ada data barang' }}</h3>
                            <p>{{ $search !== '' || $type ? 'Coba ubah kata pencarian atau filter.' : 'Tambahkan barang pertama untuk mulai mengelola inventaris.' }}</p>
                            @if ($search === '' && ! $type)
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
