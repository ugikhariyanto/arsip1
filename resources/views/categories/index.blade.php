@extends('layouts.app')

@section('title', 'Kategori')

@section('content')
    <section class="page-heading">
        <div>
            <p class="eyebrow">PENGATURAN INVENTARIS</p>
            <h1>Kategori barang</h1>
            <p class="heading-copy">Kelola kategori dan kode awalan yang digunakan untuk kode barang.</p>
        </div>
        <a class="button button-primary" href="{{ route('categories.create') }}"><span>＋</span> Tambah kategori</a>
    </section>

    @if (session('error'))
        <div class="alert alert-error" role="alert">{{ session('error') }}</div>
    @endif

    <section class="content-card">
        <div class="section-heading">
            <div><h2>Daftar kategori</h2><p>Kode kategori akan menjadi awalan kode barang, misalnya ATK-001.</p></div>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Nama kategori</th><th>Kode kategori</th><th>Jumlah barang</th><th class="actions-heading">Aksi</th></tr></thead>
                <tbody>
                @forelse ($categories as $category)
                    <tr>
                        <td><strong>{{ $category->name }}</strong></td>
                        <td><span class="badge">{{ $category->code }}</span></td>
                        <td>{{ number_format($category->inventory_items_count, 0, ',', '.') }} barang</td>
                        <td class="row-actions">
                            <a class="action-link" href="{{ route('categories.edit', $category) }}">Ubah</a>
                            <form method="POST" action="{{ route('categories.destroy', $category) }}" onsubmit="return confirm('Yakin ingin menghapus kategori ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="action-link action-delete" type="submit" @disabled($category->inventory_items_count > 0)>Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4">
                        <div class="empty-state compact">
                            <div class="empty-icon">◇</div>
                            <h3>Belum ada kategori</h3>
                            <p>Buat kategori terlebih dahulu sebelum menambahkan barang.</p>
                            <a class="button button-primary" href="{{ route('categories.create') }}">Tambah kategori</a>
                        </div>
                    </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
