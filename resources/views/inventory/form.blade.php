@extends('layouts.app')

@section('title', $title)

@section('content')
    @php
        $selectedCategoryId = (int) old('category_id', $item->category_id);
        $selectedCategory = $categories->firstWhere('id', $selectedCategoryId);
        $priceValue = old('price', $item->price);
    @endphp
    <section class="page-heading">
        <div>
            <p class="eyebrow">INVENTARIS</p>
            <h1>{{ $title }}</h1>
            <p class="heading-copy">Lengkapi informasi barang agar mudah ditemukan dan dikelola.</p>
        </div>
        <a class="button button-secondary" href="{{ route('inventory.index', ['type' => $item->type]) }}">← Kembali</a>
    </section>

    <section class="content-card form-card">
        @if ($categories->isEmpty())
            <div class="alert alert-error" role="alert">
                Belum ada kategori. Tambahkan kategori terlebih dahulu agar kode barang dapat dibuat.
                <a class="text-link" href="{{ route('categories.create') }}">Tambah kategori →</a>
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-error" role="alert">
                <strong>Data belum dapat disimpan.</strong>
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" enctype="multipart/form-data" action="{{ $item->exists ? route('inventory.update', $item) : route('inventory.store') }}">
            @csrf
            @if ($item->exists) @method('PUT') @endif

            <div class="form-section">
                <div class="form-section-heading"><span class="step-number">1</span><div><h2>Informasi barang</h2><p>Identitas dan klasifikasi barang inventaris.</p></div></div>
                <div class="form-grid">
                    <label class="field">
                        <span>Jenis barang <b>*</b></span>
                        <select name="type" id="item-type" required>
                            <option value="consumable" @selected(old('type', $item->type) === 'consumable')>Barang habis pakai</option>
                            <option value="asset" @selected(old('type', $item->type) === 'asset')>Barang aset</option>
                        </select>
                        @error('type')<small class="field-error">{{ $message }}</small>@enderror
                    </label>
                    <label class="field">
                        <span>Nama barang <b>*</b></span>
                        <input name="name" value="{{ old('name', $item->name) }}" maxlength="150" placeholder="Contoh: Kertas A4 atau Komputer" required>
                        @error('name')<small class="field-error">{{ $message }}</small>@enderror
                    </label>
                    <label class="field">
                        <span>Kategori barang <b>*</b></span>
                        <select name="category_id" id="item-category" required @disabled($categories->isEmpty())>
                            <option value="">Pilih kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" data-code="{{ $category->code }}" @selected($selectedCategoryId === $category->id)>
                                    {{ $category->name }} ({{ $category->code }})
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')<small class="field-error">{{ $message }}</small>@enderror
                        <a class="field-hint field-link" href="{{ route('categories.create') }}">+ Tambah kategori baru</a>
                    </label>
                    <label class="field">
                        <span>Kode barang <b>*</b></span>
                        <div class="code-input-group">
                            <input class="code-prefix" id="item-code-prefix" value="{{ $selectedCategory?->code ?? '' }}" aria-label="Awalan kode barang" readonly>
                            <span class="code-separator">-</span>
                            <input name="item_number" value="{{ old('item_number', $item->suggested_item_number) }}" maxlength="10" pattern="[0-9]{1,10}" inputmode="numeric" placeholder="001" aria-label="Nomor kode barang" required>
                        </div>
                        <small class="field-hint">Kode lengkap: <strong id="item-code-preview">{{ $selectedCategory?->code ?? 'KODE' }}-{{ old('item_number', $item->suggested_item_number) ?: '001' }}</strong></small>
                        @error('item_number')<small class="field-error">{{ $message }}</small>@enderror
                    </label>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-heading"><span class="step-number">2</span><div><h2>Stok dan lokasi</h2><p>Masukkan jumlah saat ini dan tempat penyimpanan.</p></div></div>
                <div class="form-grid">
                    <label class="field">
                        <span id="quantity-label">{{ $item->type === 'asset' ? 'Jumlah aset' : 'Jumlah stok' }} <b>*</b></span>
                        <input type="number" name="quantity" min="{{ $item->type === 'asset' ? 1 : 0 }}" step="1" value="{{ old('quantity', $item->quantity) }}" required>
                        @error('quantity')<small class="field-error">{{ $message }}</small>@enderror
                    </label>
                    <label class="field">
                        <span>Satuan <b>*</b></span>
                        <input name="unit" value="{{ old('unit', $item->unit) }}" maxlength="30" placeholder="Contoh: pcs, rim, botol, unit" required>
                        @error('unit')<small class="field-error">{{ $message }}</small>@enderror
                    </label>
                    <label class="field">
                        <span>Lokasi penyimpanan</span>
                        <input name="location" value="{{ old('location', $item->location) }}" maxlength="150" placeholder="Contoh: Gudang umum, Ruang pemeriksaan">
                        @error('location')<small class="field-error">{{ $message }}</small>@enderror
                    </label>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-heading"><span class="step-number">3</span><div><h2>Foto barang</h2><p>Pilih foto dan lihat pratinjaunya sebelum menyimpan.</p></div></div>
                <div class="photo-upload-grid">
                    <div class="photo-preview" data-photo-preview-container>
                        @if ($item->photo_path)
                            <img src="{{ asset('storage/'.$item->photo_path) }}" alt="Foto {{ $item->name }}" data-photo-preview>
                            <div class="photo-placeholder" data-photo-placeholder hidden>
                                <span aria-hidden="true">▧</span>
                                <strong>Pratinjau foto</strong>
                            </div>
                        @else
                            <img alt="Pratinjau foto barang" data-photo-preview hidden>
                            <div class="photo-placeholder" data-photo-placeholder>
                                <span aria-hidden="true">▧</span>
                                <strong>Belum ada foto</strong>
                            </div>
                        @endif
                    </div>
                    <div class="photo-upload-fields">
                        <label class="field">
                            <span>Unggah foto</span>
                            <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" data-photo-input>
                            <small class="field-hint">Format JPG, PNG, atau WebP. Maksimal 5 MB.</small>
                            @error('photo')<small class="field-error">{{ $message }}</small>@enderror
                        </label>
                        @if ($item->photo_path)
                            <label class="remove-photo-option">
                                <input type="checkbox" name="remove_photo" value="1" @checked(old('remove_photo'))>
                                Hapus foto saat ini
                            </label>
                        @endif
                    </div>
                </div>
            </div>

            <div class="form-section asset-fields" data-asset-fields>
                <div class="form-section-heading"><span class="step-number">4</span><div><h2>Informasi aset</h2><p>Detail tambahan untuk barang yang masa pakainya lama.</p></div></div>
                <div class="form-grid">
                    <label class="field">
                        <span>Kondisi</span>
                        <select name="condition">
                            <option value="">Pilih kondisi</option>
                            @foreach (['Baik', 'Perlu perbaikan', 'Rusak'] as $condition)
                                <option value="{{ $condition }}" @selected(old('condition', $item->condition) === $condition)>{{ $condition }}</option>
                            @endforeach
                        </select>
                        @error('condition')<small class="field-error">{{ $message }}</small>@enderror
                    </label>
                    <label class="field">
                        <span>Tanggal perolehan</span>
                        <input type="date" name="acquired_at" value="{{ old('acquired_at', $item->acquired_at?->format('Y-m-d')) }}">
                        @error('acquired_at')<small class="field-error">{{ $message }}</small>@enderror
                    </label>
                    <label class="field">
                        <span>Harga barang</span>
                        <div class="currency-input">
                            <span>Rp</span>
                            <input type="text" name="price" value="{{ $priceValue === null || $priceValue === '' ? '' : number_format((int) str_replace('.', '', (string) $priceValue), 0, ',', '.') }}" placeholder="Contoh: 1.000" inputmode="numeric" autocomplete="off" data-currency-input>
                        </div>
                        <small class="field-hint">Masukkan harga dalam rupiah. Pemisah ribuan ditambahkan otomatis.</small>
                        @error('price')<small class="field-error">{{ $message }}</small>@enderror
                    </label>
                </div>
            </div>

            <div class="form-section">
                <label class="field">
                    <span>Keterangan</span>
                    <textarea name="notes" rows="4" maxlength="2000" placeholder="Tambahkan catatan bila diperlukan...">{{ old('notes', $item->notes) }}</textarea>
                    @error('notes')<small class="field-error">{{ $message }}</small>@enderror
                </label>
            </div>

            <div class="form-actions">
                <a class="button button-secondary" href="{{ route('inventory.index', ['type' => $item->type]) }}">Batal</a>
                <button class="button button-primary" type="submit">{{ $submitLabel }}</button>
            </div>
        </form>
    </section>
@endsection
