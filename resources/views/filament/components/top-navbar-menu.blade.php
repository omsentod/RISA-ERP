@php
    $navigation = filament()->getNavigation();

    // ──────────────────────────────────────────────────────────
    // META-CATEGORIES: Topbar utama yang mengelompokkan
    // Filament NavigationGroups ke dalam menu tingkat atas.
    //
    // "Master Data" = meta-category (dropdown di topbar)
    //   └── berisi groups: ['Produk', ...future 'Gudang', etc.]
    //
    // "Manajemen Akses" = meta-category (direct link, 1:1 mapping)
    //   └── berisi groups: ['Manajemen Akses']
    //
    // Untuk menambah parent menu baru di bawah "Master Data":
    //   1. Tambah nama group baru di array 'groups' di bawah
    //   2. Tambah group di ->navigationGroups() di AdminPanelProvider.php
    //   3. Set $navigationGroup yang sesuai di Resource/Page baru
    // ──────────────────────────────────────────────────────────

    $metaCategories = [
        [
            'label' => 'Dashboard',
            'icon' => 'heroicon-o-home',
            'groups' => [''],  // empty string = no-label group (Dashboard)
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
    ];

    // Build menu data dari navigation groups
    $menuItems = [];
    foreach ($metaCategories as $meta) {
        $subGroups = [];
        $isAnyActive = false;

        foreach ($navigation as $group) {
            $groupLabel = $group->getLabel() ?: '';
            if (in_array($groupLabel, $meta['groups'], true)) {
                $items = collect($group->getItems());
                if ($items->isNotEmpty()) {
                    $isActive = $group->isActive();
                    if ($isActive) {
                        $isAnyActive = true;
                    }
                    $subGroups[] = [
                        'label' => $groupLabel ?: 'Dashboard',
                        'url' => $items->first()?->getUrl(),
                        'isActive' => $isActive,
                    ];
                }
            }
        }

        if (! empty($subGroups)) {
            $menuItems[] = [
                'label' => $meta['label'],
                'icon' => $meta['icon'],
                'subGroups' => $subGroups,
                'isActive' => $isAnyActive,
                'hasDropdown' => count($subGroups) > 1 || (count($meta['groups']) > 1),
                'directUrl' => $subGroups[0]['url'] ?? '#',
            ];
        }
    }
@endphp

<div class="hidden items-center gap-x-1.5 md:flex ms-4">
    @foreach ($menuItems as $item)
        @if ($item['hasDropdown'])
            {{-- Dropdown menu untuk meta-category dengan multiple sub-groups --}}
            <div x-data="{ open: false }" @click.outside="open = false" class="relative">
                <button
                    @click="open = !open"
                    @class([
                        'flex items-center gap-x-2 rounded-lg px-3 py-1.5 text-sm font-medium transition-all duration-150',
                        'bg-primary-500/10 text-primary-600 dark:bg-primary-400/10 dark:text-primary-400 font-semibold ring-1 ring-primary-500/30' => $item['isActive'],
                        'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white' => ! $item['isActive'],
                    ])
                >
                    <x-filament::icon
                        :icon="$item['icon']"
                        @class([
                            'h-4 w-4',
                            'text-primary-600 dark:text-primary-400' => $item['isActive'],
                            'text-gray-400 dark:text-gray-500' => ! $item['isActive'],
                        ])
                    />
                    <span>{{ $item['label'] }}</span>
                    <x-filament::icon
                        icon="heroicon-m-chevron-down"
                        @class([
                            'h-3 w-3 transition-transform duration-200',
                            'text-primary-600 dark:text-primary-400' => $item['isActive'],
                            'text-gray-400 dark:text-gray-500' => ! $item['isActive'],
                        ])
                        x-bind:class="open && 'rotate-180'"
                    />
                </button>

                {{-- Dropdown panel --}}
                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="absolute top-full left-0 mt-1 w-48 rounded-lg bg-white shadow-lg ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10 z-50"
                    style="display: none;"
                >
                    <div class="py-1">
                        @foreach ($item['subGroups'] as $sub)
                            <a
                                href="{{ $sub['url'] }}"
                                @click="open = false"
                                @class([
                                    'flex items-center gap-x-2 px-4 py-2 text-sm transition-colors',
                                    'bg-primary-50 text-primary-700 font-medium dark:bg-primary-500/10 dark:text-primary-400' => $sub['isActive'],
                                    'text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700' => ! $sub['isActive'],
                                ])
                            >
                                {{ $sub['label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @else
            {{-- Direct link untuk meta-category 1:1 --}}
            <a
                href="{{ $item['directUrl'] }}"
                @class([
                    'flex items-center gap-x-2 rounded-lg px-3 py-1.5 text-sm font-medium transition-all duration-150',
                    'bg-primary-500/10 text-primary-600 dark:bg-primary-400/10 dark:text-primary-400 font-semibold ring-1 ring-primary-500/30' => $item['isActive'],
                    'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white' => ! $item['isActive'],
                ])
            >
                <x-filament::icon
                    :icon="$item['icon']"
                    @class([
                        'h-4 w-4',
                        'text-primary-600 dark:text-primary-400' => $item['isActive'],
                        'text-gray-400 dark:text-gray-500' => ! $item['isActive'],
                    ])
                />
                <span>{{ $item['label'] }}</span>
            </a>
        @endif
    @endforeach
</div>
