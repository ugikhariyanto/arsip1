@extends('layouts.app')

@section('title', $title)

@section('content')
    <section class="page-heading">
        <div>
            <p class="eyebrow">PENGATURAN INVENTARIS</p>
            <h1>{{ $title }}</h1>
            <p class="heading-copy">Kode kategori akan otomatis digunakan sebagai awalan kode setiap barang.</p>
        </div>
        <a class="button button-secondary" href="{{ route('categories.index') }}">← Kembali</a>
    </section>

    <section class="content-card form-card category-form-card">
        @if ($errors->any())
            <div class="alert alert-error" role="alert">
                <strong>Data belum dapat disimpan.</strong>
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ $category->exists ? route('categories.update', $category) : route('categories.store') }}">
            @csrf
            @if ($category->exists) @method('PUT') @endif
            <div class="form-section">
                <div class="form-grid">
                    <label class="field">
                        <span>Nama kategori <b>*</b></span>
                        <input name="name" value="{{ old('name', $category->name) }}" maxlength="100" placeholder="Contoh: Alat tulis kantor" required>
                        @error('name')<small class="field-error">{{ $message }}</small>@enderror
                    </label>
                    <label class="field">
                        <span>Kode kategori <b>*</b></span>
                        <input name="code" value="{{ old('code', $category->code) }}" maxlength="10" pattern="[A-Za-z0-9]{2,10}" placeholder="Contoh: ATK" required>
                        <small class="field-hint">2–10 huruf atau angka. Kode barang akan menjadi ATK-001, misalnya.</small>
                        @error('code')<small class="field-error">{{ $message }}</small>@enderror
                    </label>
                </div>
            </div>
            <div class="form-actions">
                <a class="button button-secondary" href="{{ route('categories.index') }}">Batal</a>
                <button class="button button-primary" type="submit">{{ $submitLabel }}</button>
            </div>
        </form>
    </section>
@endsection
