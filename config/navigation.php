<?php

// ──────────────────────────────────────────────────────────────
// META-CATEGORIES: sumber tunggal untuk navigasi RISA ERP.
// Dipakai oleh:
//   - resources/views/filament/components/top-navbar-menu.blade.php  (topbar desktop)
//   - resources/views/filament/components/mobile-navbar-menu.blade.php (hamburger mobile)
//
// Tiap meta-category mengelompokkan Filament NavigationGroup ke menu tingkat atas.
//   'Master Data' (meta) └── groups: ['Produk', ...future]
//
// Menambah parent menu (sidebar group) baru di bawah meta yang ada:
//   1. Tambah nama group di 'groups' meta yang sesuai di bawah
//   2. Tambah group di ->navigationGroups([...]) di AdminPanelProvider.php
//   3. Set $navigationGroup yang sesuai di Resource/Page baru
// ──────────────────────────────────────────────────────────────

return [
    'meta_categories' => [
        [
            'label' => 'Dashboard',
            'icon' => 'heroicon-o-home',
            'groups' => [''], // empty string = no-label group (Dashboard)
        ],
        [
            'label' => 'Master Data',
            'icon' => 'heroicon-o-circle-stack',
            'groups' => ['Produk'],
        ],
        [
            'label' => 'Manajemen Akses',
            'icon' => 'heroicon-o-shield-check',
            'groups' => ['Manajemen Akses', 'Filament Shield', 'Roles'],
        ],
    ],
];
