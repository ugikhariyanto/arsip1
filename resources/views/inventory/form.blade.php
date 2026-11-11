@extends('layouts.app')

@section('title', $title)

@section('content')
    @php
        $selectedCategoryId = (int) old('category_id', $item->category_id);
        $selectedCategory = $categories->firstWhere('id', $selectedCategoryId);
        $priceValue = old('price', $item->price);
        $originalCategoryId = $item->exists ? (int) $item->category_id : null;
        $originalItemNumber = $item->exists ? $item->suggested_item_number : '';
        $initialItemNumber = $originalCategoryId !== null && $selectedCategoryId === $originalCategoryId
            ? $originalItemNumber
            : ($nextNumbers[$selectedCategoryId] ?? '');

        $selectedType = old('type', $item->type);
        $hasLocation = in_array($selectedType, ['asset', 'facility'], true);
        $selectedLocationId = (int) old('location_id', $item->location_id);
        $selectedCondition = old('condition', $item->condition ?: 'Baik');
        $needsConditionDetail = in_array($selectedCondition, \App\Models\InventoryItem::CONDITION_DETAIL_REQUIRED, true);
        $isLostCondition = in_array($selectedCondition, [\App\Models\InventoryItem::CONDITION_LOST, \App\Models\InventoryItem::CONDITION_NOT_FOUND], true);
        $calibrationStatus = old('calibration_status', $item->calibration_status);
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
                            <option value="consumable" @selected($selectedType === 'consumable')>Barang habis pakai</option>
                            <option value="asset" @selected($selectedType === 'asset')>Barang aset</option>
                            <option value="facility" @selected($selectedType === 'facility')>Sarana dan prasarana</option>
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
                                <option value="{{ $category->id }}" data-code="{{ $category->code }}" data-next-number="{{ $nextNumbers[$category->id] ?? '' }}" @selected($selectedCategoryId === $category->id)>
                                    {{ $category->name }} ({{ $category->code }})
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')<small class="field-error">{{ $message }}</small>@enderror
                        <a class="field-hint field-link" href="{{ route('categories.create') }}">+ Tambah kategori baru</a>
                    </label>
                    <label class="field">
                        <span>Kode barang</span>
                        <div class="code-input-group">
                            <input class="code-prefix" id="item-code-prefix" value="{{ $selectedCategory?->code ?? '' }}" aria-label="Awalan kode barang" readonly>
                            <span class="code-separator">-</span>
                            <input id="item-number-display" value="{{ $initialItemNumber }}" placeholder="0000" aria-label="Nomor kode barang otomatis" readonly
                                data-original-number="{{ $originalItemNumber }}"
                                data-original-category="{{ $originalCategoryId ?? '' }}">
                        </div>
                        <small class="field-hint">
                            Kode lengkap: <strong id="item-code-preview">{{ $selectedCategory ? $selectedCategory->code.'-'.$initialItemNumber : 'Pilih kategori' }}</strong>.
                            Nomor dibuat otomatis sesuai kategori.
                        </small>
                        @error('item_number')<small class="field-error">{{ $message }}</small>@enderror
                    </label>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-heading"><span class="step-number">2</span><div><h2>Stok dan lokasi</h2><p>Masukkan jumlah saat ini dan tempat penyimpanan barang.</p></div></div>
                <div class="form-grid">
                    <label class="field">
                        <span id="quantity-label">{{ $selectedType === 'consumable' ? 'Jumlah stok' : 'Jumlah' }} <b>*</b></span>
                        <input type="number" name="quantity" min="{{ $selectedType === 'consumable' ? 0 : 1 }}" step="1" value="{{ old('quantity', $item->quantity) }}" required data-numeric-keypad>
                        @error('quantity')<small class="field-error">{{ $message }}</small>@enderror
                    </label>
                    <label class="field">
                        <span>Satuan <b>*</b></span>
                        <input name="unit" value="{{ old('unit', $item->unit) }}" maxlength="30" placeholder="Contoh: pcs, rim, botol, unit" required>
                        @error('unit')<small class="field-error">{{ $message }}</small>@enderror
                    </label>
                    <label class="field" data-location-field @if (! $hasLocation) hidden @endif>
                        <span>Lokasi / ruangan</span>
                        <select name="location_id" @disabled(! $hasLocation)>
                            <option value="">Pilih lokasi / ruangan</option>
                            @foreach ($locations as $location)
                                <option value="{{ $location->id }}" @selected($selectedLocationId === $location->id)>{{ $location->name }}</option>
                            @endforeach
                        </select>
                        <small class="field-hint">Lokasi hanya digunakan untuk barang aset serta sarana dan prasarana.</small>
                        <a class="field-hint field-link" href="{{ route('locations.create') }}">+ Tambah lokasi / ruangan baru</a>
                        @error('location_id')<small class="field-error">{{ $message }}</small>@enderror
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

            <div class="form-section">
                <div class="form-section-heading"><span class="step-number">4</span><div><h2>Keadaan barang</h2><p>Pilih keadaan terkini untuk semua jenis barang.</p></div></div>
                <div class="form-grid">
                    <label class="field">
                        <span>Keadaan <b>*</b></span>
                        <select name="condition" id="item-condition" required>
                            @foreach (\App\Models\InventoryItem::CONDITIONS as $condition)
                                <option value="{{ $condition }}" @selected($selectedCondition === $condition)>{{ $condition }}</option>
                            @endforeach
                        </select>
                        @error('condition')<small class="field-error">{{ $message }}</small>@enderror
                    </label>
                    <div class="field" data-condition-detail @if (! $needsConditionDetail) hidden @endif>
                        <span data-condition-date-label>{{ $isLostCondition ? 'Tanggal hilang' : 'Tanggal rusak' }} <b>*</b></span>
                        <input type="date" name="condition_date" value="{{ old('condition_date', $item->condition_date?->format('Y-m-d')) }}">
                        <small class="field-hint" data-condition-date-hint>
                            {{ $isLostCondition ? 'Isi tanggal barang dinyatakan hilang atau tidak ditemukan.' : 'Isi tanggal barang mulai rusak.' }}
                        </small>
                        @error('condition_date')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                    <label class="field" data-condition-detail @if (! $needsConditionDetail) hidden @endif>
                        <span>Keterangan tindak lanjut</span>
                        <textarea name="follow_up" rows="3" maxlength="2000" placeholder="Contoh: Sudah diajukan perbaikan, menunggu teknisi, atau dilaporkan ke bagian umum.">{{ old('follow_up', $item->follow_up) }}</textarea>
                        @error('follow_up')<small class="field-error">{{ $message }}</small>@enderror
                    </label>
                </div>
            </div>

            <div class="form-section asset-fields" data-asset-fields @if ($selectedType === 'consumable') hidden @endif>
                <div class="form-section-heading"><span class="step-number">5</span><div><h2 data-asset-section-title>{{ $selectedType === 'facility' ? 'Informasi sarana dan prasarana' : 'Informasi aset' }}</h2><p>Detail tambahan untuk barang yang masa pakainya lama.</p></div></div>
                <div class="form-grid">
                    <label class="field">
                        <span>Tanggal perolehan</span>
                        <input type="date" name="acquired_at" value="{{ old('acquired_at', $item->acquired_at?->format('Y-m-d')) }}">
                        @error('acquired_at')<small class="field-error">{{ $message }}</small>@enderror
                    </label>
                    <label class="field">
                        <span>Harga barang</span>
                        <div class="currency-input">
                            <span>Rp</span>
                            <input type="text" name="price" value="{{ $priceValue === null || $priceValue === '' ? '' : number_format((int) str_replace('.', '', (string) $priceValue), 0, ',', '.') }}" placeholder="Contoh: 1.000" inputmode="numeric" autocomplete="off" data-currency-input data-numeric-keypad>
                        </div>
                        <small class="field-hint">Masukkan harga dalam rupiah. Pemisah ribuan ditambahkan otomatis.</small>
                        @error('price')<small class="field-error">{{ $message }}</small>@enderror
                    </label>
                    <label class="field" data-calibration-field @if ($selectedType !== 'asset') hidden @endif>
                        <span>Status kalibrasi</span>
                        <select name="calibration_status" id="item-calibration-status" @disabled($selectedType !== 'asset')>
                            <option value="">Pilih status kalibrasi</option>
                            <option value="belum" @selected($calibrationStatus === 'belum')>Belum</option>
                            <option value="sudah" @selected($calibrationStatus === 'sudah')>Sudah</option>
                        </select>
                        <small class="field-hint">Keterangan kalibrasi hanya tersedia untuk barang aset.</small>
                        @error('calibration_status')<small class="field-error">{{ $message }}</small>@enderror
                    </label>
                    <label class="field" data-calibration-field @if ($selectedType !== 'asset') hidden @endif>
                        <span>Tanggal kalibrasi</span>
                        <input type="date" name="calibration_date" value="{{ old('calibration_date', $item->calibration_date?->format('Y-m-d')) }}" @disabled($selectedType !== 'asset')>
                        <small class="field-hint">Wajib diisi bila status kalibrasi sudah dilakukan.</small>
                        @error('calibration_date')<small class="field-error">{{ $message }}</small>@enderror
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
