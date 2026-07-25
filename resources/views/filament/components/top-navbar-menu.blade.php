@php
    $navigation = filament()->getNavigation();
@endphp

<div class="hidden items-center gap-x-1.5 md:flex ms-4">
    @foreach ($navigation as $group)
        @php
            $groupLabel = $group->getLabel();
            $items = collect($group->getItems());
        @endphp

        @if ($items->isEmpty())
            @continue
        @endif

        @php
            $isActive = $group->isActive();
            $firstItemUrl = $items->first()?->getUrl();

            $icon = match ($groupLabel) {
                'Master Data' => 'heroicon-o-circle-stack',
                'Manajemen Akses' => 'heroicon-o-shield-check',
                default => 'heroicon-o-squares-2x2',
            };
            if (! $groupLabel) {
                $icon = 'heroicon-o-home';
            }
        @endphp

        <a
            href="{{ $firstItemUrl }}"
            @class([
                'flex items-center gap-x-2 rounded-lg px-3 py-1.5 text-sm font-medium transition-all duration-150',
                'bg-primary-500/10 text-primary-600 dark:bg-primary-400/10 dark:text-primary-400 font-semibold ring-1 ring-primary-500/30' => $isActive,
                'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white' => ! $isActive,
            ])
        >
            <x-filament::icon
                :icon="$icon"
                @class([
                    'h-4 w-4',
                    'text-primary-600 dark:text-primary-400' => $isActive,
                    'text-gray-400 dark:text-gray-500' => ! $isActive,
                ])
            />
            <span>{{ $groupLabel ?: 'Dashboard' }}</span>
        </a>
    @endforeach
</div>
