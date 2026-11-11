@extends('layouts.app')

@section('title', 'Lokasi dan ruangan')

@section('content')
    <section class="page-heading">
        <div>
            <p class="eyebrow">PENGATURAN INVENTARIS</p>
            <h1>Lokasi / ruangan</h1>
            <p class="heading-copy">Kelola daftar ruangan yang digunakan sebagai lokasi barang aset serta sarana dan prasarana.</p>
        </div>
        <a class="button button-primary" href="{{ route('locations.create') }}"><span>＋</span> Tambah lokasi</a>
    </section>

    @if (session('error'))
        <div class="alert alert-error" role="alert">{{ session('error') }}</div>
    @endif

    <section class="content-card">
        <div class="section-heading">
            <div><h2>Daftar lokasi</h2><p>Lokasi akan menjadi pilihan dropdown pada formulir barang.</p></div>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Nama lokasi / ruangan</th><th>Jumlah barang</th><th class="actions-heading">Aksi</th></tr></thead>
                <tbody>
                @forelse ($locations as $location)
                    <tr>
                        <td><strong>{{ $location->name }}</strong></td>
                        <td>{{ number_format($location->inventory_items_count, 0, ',', '.') }} barang</td>
                        <td class="row-actions">
                            <a class="action-link" href="{{ route('locations.edit', $location) }}">Ubah</a>
                            <form method="POST" action="{{ route('locations.destroy', $location) }}" onsubmit="return confirm('Yakin ingin menghapus lokasi ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="action-link action-delete" type="submit" @disabled($location->inventory_items_count > 0)>Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3">
                        <div class="empty-state compact">
                            <div class="empty-icon">⌂</div>
                            <h3>Belum ada lokasi</h3>
                            <p>Tambahkan ruangan terlebih dahulu agar barang aset dan sarana dapat ditempatkan.</p>
                            <a class="button button-primary" href="{{ route('locations.create') }}">Tambah lokasi</a>
                        </div>
                    </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
