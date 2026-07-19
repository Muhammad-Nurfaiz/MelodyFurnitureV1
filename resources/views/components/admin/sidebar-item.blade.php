@php

    $active = false;

    if (isset($menu['active'])) {

        foreach ($menu['active'] as $pattern) {

            if (request()->routeIs($pattern)) {

                $active = true;
                break;

            }

        }

    } else {

        $active = request()->routeIs($menu['route']);

    }

@endphp

<li :class="sidebarCollapsed ? 'px-1.5' : 'px-3'"
    @mouseenter="
        if(sidebarCollapsed && !isMobile){

            const rect = $el.getBoundingClientRect();

            tooltip.show = true;
            tooltip.text = '{{ $menu['title'] }}';
            tooltip.x = rect.right + 12;
            tooltip.y = rect.top + rect.height/2;
        }"
    @mouseleave="tooltip.show = false;">

    <a
        href="{{ Route::has($menu['route']) ? route($menu['route']) : '#' }}"
        title="{{ $menu['title'] }}"
        class="group relative flex items-center rounded-lg px-3 py-3 text-sm font-medium transition-all duration-200
            {{ $active
                ? 'bg-blue-50 text-blue-700'
                : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'
            }}"
        :class="sidebarCollapsed && !isMobile
            ? 'justify-center'
            : 'gap-3'"
    >

        <x-dynamic-component
            :component="'heroicon-o-'.$menu['icon']"
            class="h-5 w-5 flex-shrink-0"/>

        {{-- Judul --}}
        <span
            x-show="!sidebarCollapsed || isMobile"
            x-transition.opacity
            class="flex-1 whitespace-nowrap">

            {{ $menu['title'] }}

        </span>

    </a>
</li>