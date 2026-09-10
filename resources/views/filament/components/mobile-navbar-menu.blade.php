@php
    // Navigasi hamburger mobile: menampilkan hierarki penuh
    // Meta-category → Group → Item (accordion), meniru topbar desktop.
    // Hanya tampil di layar kecil (< md); di md+ pakai sidebar terfilter default.
    $navigation = filament()->getNavigation();
    $metaCategories = config('navigation.meta_categories', []);

    $menu = [];
    foreach ($metaCategories as $meta) {
        $groups = [];
        $metaActive = false;

        foreach ($navigation as $group) {
            $groupLabel = $group->getLabel() ?: '';
            if (! in_array($groupLabel, $meta['groups'], true)) {
                continue;
            }

            $items = collect($group->getItems());
            if ($items->isEmpty()) {
                continue;
            }

            $groupActive = $group->isActive();
            $metaActive = $metaActive || $groupActive;

            $groups[] = [
                'label' => $groupLabel ?: 'Dashboard',
                'isActive' => $groupActive,
                'items' => $items->map(fn ($item) => [
                    'label' => $item->getLabel(),
                    'url' => $item->getUrl(),
                    'isActive' => $item->isActive(),
                ])->values()->all(),
            ];
        }

        if (! empty($groups)) {
            $menu[] = [
                'label' => $meta['label'],
                'icon' => $meta['icon'],
                'isActive' => $metaActive,
                'groups' => $groups,
            ];
        }
    }
@endphp

<div class="fi-mobile-nav flex flex-col gap-y-1 md:hidden">
    @foreach ($menu as $meta)
        @php
            $metaSingleItem = count($meta['groups']) === 1 && count($meta['groups'][0]['items']) === 1
                ? $meta['groups'][0]['items'][0]
                : null;
        @endphp

        @if ($metaSingleItem)
            {{-- LEVEL 1 sebagai link langsung (mis. Dashboard) --}}
            <a
                href="{{ $metaSingleItem['url'] }}"
                @class([
                    'flex items-center gap-x-2.5 rounded-lg px-2 py-2 text-sm font-medium',
                    'bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400' => $meta['isActive'],
                    'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5' => ! $meta['isActive'],
                ])
            >
                <x-filament::icon :icon="$meta['icon']" class="h-5 w-5 shrink-0" />
                <span>{{ $meta['label'] }}</span>
            </a>
        @else
            {{-- LEVEL 1: meta-category (accordion) --}}
            <div x-data="{ open: @js($meta['isActive']) }">
                <button
                    type="button"
                    @click="open = ! open"
                    @class([
                        'flex w-full items-center gap-x-2.5 rounded-lg px-2 py-2 text-sm font-medium',
                        'bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400' => $meta['isActive'],
                        'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5' => ! $meta['isActive'],
                    ])
                >
                    <x-filament::icon :icon="$meta['icon']" class="h-5 w-5 shrink-0" />
                    <span class="flex-1 text-start">{{ $meta['label'] }}</span>
                    <x-filament::icon
                        icon="heroicon-m-chevron-down"
                        class="h-4 w-4 shrink-0 text-gray-400 transition-transform duration-200 dark:text-gray-500"
                        x-bind:class="open && 'rotate-180'"
                    />
                </button>

                <div
                    x-show="open"
                    x-collapse.duration.200ms
                    class="ms-3 mt-1 flex flex-col gap-y-1 border-s border-gray-200 ps-2 dark:border-white/10"
                >
                    @foreach ($meta['groups'] as $group)
                        @php
                            $groupSingleItem = count($group['items']) === 1 ? $group['items'][0] : null;
                        @endphp

                        @if ($groupSingleItem)
                            {{-- LEVEL 2 sebagai link langsung --}}
                            <a
                                href="{{ $groupSingleItem['url'] }}"
                                @class([
                                    'rounded-lg px-2 py-1.5 text-sm',
                                    'font-medium text-primary-600 dark:text-primary-400' => $group['isActive'],
                                    'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5' => ! $group['isActive'],
                                ])
                            >
                                {{ $group['label'] }}
                            </a>
                        @else
                            {{-- LEVEL 2: group (accordion) --}}
                            <div x-data="{ open: @js($group['isActive']) }">
                                <button
                                    type="button"
                                    @click="open = ! open"
                                    @class([
                                        'flex w-full items-center gap-x-2 rounded-lg px-2 py-1.5 text-sm',
                                        'font-medium text-primary-600 dark:text-primary-400' => $group['isActive'],
                                        'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5' => ! $group['isActive'],
                                    ])
                                >
                                    <span class="flex-1 text-start">{{ $group['label'] }}</span>
                                    <x-filament::icon
                                        icon="heroicon-m-chevron-down"
                                        class="h-3.5 w-3.5 shrink-0 text-gray-400 transition-transform duration-200 dark:text-gray-500"
                                        x-bind:class="open && 'rotate-180'"
                                    />
                                </button>

                                <div
                                    x-show="open"
                                    x-collapse.duration.200ms
                                    class="ms-3 mt-1 flex flex-col gap-y-0.5 border-s border-gray-200 ps-2 dark:border-white/10"
                                >
                                    @foreach ($group['items'] as $item)
                                        {{-- LEVEL 3: item --}}
                                        <a
                                            href="{{ $item['url'] }}"
                                            @class([
                                                'rounded-lg px-2 py-1.5 text-sm',
                                                'bg-primary-50 font-medium text-primary-600 dark:bg-primary-500/10 dark:text-primary-400' => $item['isActive'],
                                                'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5' => ! $item['isActive'],
                                            ])
                                        >
                                            {{ $item['label'] }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif
    @endforeach
</div>
