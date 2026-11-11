@extends('layouts.app')

@section('title', $title)

@section('content')
    <section class="page-heading">
        <div>
            <p class="eyebrow">PENGATURAN INVENTARIS</p>
            <h1>{{ $title }}</h1>
            <p class="heading-copy">Nama lokasi akan muncul sebagai pilihan ruangan pada data barang.</p>
        </div>
        <a class="button button-secondary" href="{{ route('locations.index') }}">← Kembali</a>
    </section>

    <section class="content-card form-card category-form-card">
        @if ($errors->any())
            <div class="alert alert-error" role="alert">
                <strong>Data belum dapat disimpan.</strong>
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ $location->exists ? route('locations.update', $location) : route('locations.store') }}">
            @csrf
            @if ($location->exists) @method('PUT') @endif
            <div class="form-section">
                <div class="form-grid">
                    <label class="field">
                        <span>Nama lokasi / ruangan <b>*</b></span>
                        <input name="name" value="{{ old('name', $location->name) }}" maxlength="150" placeholder="Contoh: Ruang Manajemen" required>
                        <small class="field-hint">Gunakan nama ruangan yang jelas, misalnya Ruang Manajemen, Ruang Farmasi, atau Gudang Umum.</small>
                        @error('name')<small class="field-error">{{ $message }}</small>@enderror
                    </label>
                </div>
            </div>
            <div class="form-actions">
                <a class="button button-secondary" href="{{ route('locations.index') }}">Batal</a>
                <button class="button button-primary" type="submit">{{ $submitLabel }}</button>
            </div>
        </form>
    </section>
@endsection
