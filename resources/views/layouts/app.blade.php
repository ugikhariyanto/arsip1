<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Arsip Puskesmas') · Arsip Sehat</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar">
            <a class="brand" href="{{ route('dashboard') }}">
                <span class="brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M12 3v18M3 12h18" stroke="currentColor" stroke-width="2.8" stroke-linecap="round"/></svg>
                </span>
                <span class="brand-copy"><strong>Arsip Sehat</strong><small>Inventaris Puskesmas</small></span>
            </a>
            <div class="nav-label">MENU UTAMA</div>
            @php
                $routeItem = request()->route('item');
                $activeInventoryType = request()->query('type')
                    ?? ($routeItem instanceof \App\Models\InventoryItem ? $routeItem->type : null);
            @endphp
            <nav class="main-nav" aria-label="Navigasi utama">
                <a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                    <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1V10Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg></span><span class="nav-copy">Ringkasan</span>
                </a>
                <a class="{{ request()->routeIs('inventory.*') && $activeInventoryType === 'consumable' ? 'active' : '' }}" href="{{ route('inventory.index', ['type' => 'consumable']) }}">
                    <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M4 5h16v14H4zM8 9h8M8 13h8M8 17h4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span><span class="nav-copy">Barang habis pakai</span>
                </a>
                <a class="{{ request()->routeIs('inventory.*') && $activeInventoryType === 'asset' ? 'active' : '' }}" href="{{ route('inventory.index', ['type' => 'asset']) }}">
                    <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><rect x="3" y="4" width="18" height="13" rx="2" stroke="currentColor" stroke-width="1.7"/><path d="M8 21h8m-4-4v4m-5-9 3-3 2 2 4-4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span><span class="nav-copy">Barang aset</span>
                </a>
                <a class="{{ request()->routeIs('categories.*') ? 'active' : '' }}" href="{{ route('categories.index') }}">
                    <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M12 3H5a2 2 0 0 0-2 2v7l9 9 9-9-9-9Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><circle cx="7.5" cy="7.5" r="1" fill="currentColor"/></svg></span><span class="nav-copy">Tambah kategori</span>
                </a>
            </nav>
            <div class="sidebar-note">
                <span class="status-dot"></span>
                <span class="sidebar-note-copy">Data tersimpan lokal<br><strong>Database SQLite</strong></span>
            </div>
        </aside>

        <button class="sidebar-backdrop" type="button" aria-label="Tutup navigasi"></button>

        <div class="page-shell">
            <header class="topbar">
                <div class="topbar-start">
                    <button class="icon-button sidebar-toggle" type="button" aria-label="Buka atau ciutkan sidebar" aria-expanded="true">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2" stroke="currentColor" stroke-width="1.7"/><path d="M9 4v16" stroke="currentColor" stroke-width="1.7"/></svg>
                    </button>
                    <span class="topbar-divider"></span>
                    <span class="topbar-context">Sistem Informasi Inventaris</span>
                </div>
                <div class="topbar-actions">
                    <button class="icon-button theme-toggle" type="button" aria-label="Ganti tema" title="Ganti tema">
                        <svg class="sun-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.7"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                        <svg class="moon-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20.2 15.2A8.5 8.5 0 0 1 8.8 3.8 8.5 8.5 0 1 0 20.2 15.2Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                    </button>
                    <span class="topbar-divider user-divider"></span>
                    <span class="user-chip"><span class="user-avatar">P</span><span class="user-name">Puskesmas</span></span>
                </div>
            </header>
            <main class="main-content">
                @if (session('success'))
                    <div class="alert alert-success" role="status">{{ session('success') }}</div>
                @endif
                @yield('content')
            </main>
            <footer class="footer">Arsip Sehat <span>·</span> Pencatatan inventaris puskesmas</footer>
        </div>
    </div>
</body>
</html>
