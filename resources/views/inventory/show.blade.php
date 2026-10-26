@extends('layouts.app')

@section('title', 'Detail '.$item->name)

@section('content')
    <section class="page-heading">
        <div>
            <p class="eyebrow">DETAIL INVENTARIS</p>
            <h1>{{ $item->name }}</h1>
            <p class="heading-copy">{{ $item->code }} · {{ $item->category }}</p>
        </div>
        <div class="detail-heading-actions">
            <a class="button button-secondary" href="{{ route('inventory.index', ['type' => $item->type]) }}">← Kembali</a>
            <a class="button button-primary" href="{{ route('inventory.edit', $item) }}">Ubah barang</a>
        </div>
    </section>

    <section class="item-detail-layout">
        <article class="content-card item-photo-card">
            <div class="detail-card-heading">
                <div><h2>Foto barang</h2><p>Gambar barang inventaris</p></div>
            </div>
            <div class="item-photo-large">
                @if ($item->photo_path)
                    <img src="{{ asset('storage/'.$item->photo_path) }}" alt="Foto {{ $item->name }}">
                @else
                    <div class="item-photo-empty">
                        <span aria-hidden="true">▧</span>
                        <strong>Belum ada foto</strong>
                        <p>Tambahkan foto melalui menu ubah barang.</p>
                    </div>
                @endif
            </div>
        </article>

        <article class="content-card item-info-card">
            <div class="detail-card-heading">
                <div><h2>Informasi barang</h2><p>Rincian data inventaris</p></div>
                <span class="badge {{ $item->type === 'asset' ? 'badge-orange' : 'badge-blue' }}">{{ $item->type_label }}</span>
            </div>
            <dl class="item-detail-list">
                <div><dt>Nama barang</dt><dd>{{ $item->name }}</dd></div>
                <div><dt>Kode lengkap</dt><dd class="code-cell">{{ $item->code }}</dd></div>
                <div><dt>Nomor barang</dt><dd class="code-cell">{{ $item->suggested_item_number ?: '—' }}</dd></div>
                <div><dt>Kategori</dt><dd>{{ $item->category }}</dd></div>
                <div><dt>Kode kategori</dt><dd class="code-cell">{{ $item->categoryRecord?->code ?? '—' }}</dd></div>
                <div><dt>Jumlah</dt><dd>{{ number_format($item->quantity, 0, ',', '.') }}</dd></div>
                <div><dt>Satuan</dt><dd>{{ $item->unit }}</dd></div>
                <div><dt>Lokasi penyimpanan</dt><dd>{{ $item->location ?: '—' }}</dd></div>
                @if ($item->type === 'asset')
                    <div><dt>Kondisi</dt><dd>{{ $item->condition ?: '—' }}</dd></div>
                    <div><dt>Tanggal perolehan</dt><dd>{{ $item->acquired_at?->format('d/m/Y') ?? '—' }}</dd></div>
                    <div class="detail-price" data-price-detail>
                        <dt>Harga barang</dt>
                        <dd>{{ $item->price === null ? 'Belum diisi' : 'Rp '.number_format($item->price, 0, ',', '.') }}</dd>
                    </div>
                @endif
                <div class="detail-notes"><dt>Keterangan</dt><dd>{{ $item->notes ?: 'Tidak ada keterangan.' }}</dd></div>
            </dl>
            <div class="detail-card-footer">
                <span>Dibuat {{ $item->created_at->format('d/m/Y H:i') }} · Diperbarui {{ $item->updated_at->format('d/m/Y H:i') }}</span>
                <a class="action-button action-edit" href="{{ route('inventory.edit', $item) }}">Ubah data</a>
            </div>
        </article>
    </section>
@endsection
