<aside

    x-bind:class="{
        'translate-x-0': sidebarOpen || !isMobile,
        '-translate-x-full': !sidebarOpen && isMobile,
        'lg:w-64': !sidebarCollapsed,
        'lg:w-20': sidebarCollapsed,
    }"

    class="
        fixed inset-y-0 left-0 z-40
        flex flex-col
        bg-white
        border-r border-gray-200
        transition-all
        duration-300
        transform
        overflow-visible
        lg:translate-x-0
        w-64
        ">

    {{-- ===================================== --}}
    {{-- Logo --}}
    {{-- ===================================== --}}

    <div class="flex h-16 items-center justify-between border-b border-gray-200 px-5">

        {{-- Logo --}}
        <div
            x-show="!sidebarCollapsed || isMobile"
            x-transition.opacity
            class="overflow-hidden">

            <h1 class="whitespace-nowrap text-lg font-bold tracking-wide text-gray-800">
                Melody Furniture
            </h1>

        </div>

        {{-- Desktop Collapse --}}
        <button
            @click="toggleCollapse();"
            class="hidden rounded-lg p-2 transition hover:bg-gray-100 lg:flex">

            {{-- Expanded --}}
            <template x-if="!sidebarCollapsed">
                <x-heroicon-o-chevron-double-left class="h-5 w-5 text-gray-600"/>
            </template>

            {{-- Collapsed --}}
            <template x-if="sidebarCollapsed">
                <x-heroicon-o-chevron-double-right class="h-5 w-5 text-gray-600"/>
            </template>

        </button>

        {{-- Mobile / Tablet Close --}}
        <button
            @click="sidebarOpen = false;
                    tooltip.show = false;"
            class="rounded-lg p-2 transition hover:bg-gray-100 lg:hidden">

            <x-heroicon-o-x-mark class="h-5 w-5 text-gray-600"/>

        </button>

    </div>

    {{-- ===================================== --}}
    {{-- Menu --}}
    {{-- ===================================== --}}

    <div
        class="flex-1 overflow-y-auto py-6">

        @foreach(config('admin-menu') as $group)

            @if(!empty($group['header']))

                <div
                    x-show="!sidebarCollapsed || isMobile"
                    x-transition>

                    <p
                        class="mb-2 px-6 text-xs font-semibold uppercase tracking-wider text-gray-400">

                        {{ $group['header'] }}

                    </p>

                </div>

            @endif

            <ul class="mb-4 space-y-1">

                @foreach($group['items'] as $menu)

                    <x-admin.sidebar-item
                        :menu="$menu" />

                @endforeach

            </ul>

        @endforeach

    </div>

    {{-- ===================================== --}}
    {{-- Footer --}}
    {{-- ===================================== --}}

    <div
        class="border-t border-gray-200 p-4">

        <form
            method="POST"
            action="{{ route('logout') }}">

            @csrf

            <button
                type="submit"

                class="flex w-full items-center gap-3 rounded-lg px-3 py-2

                       text-sm font-medium

                       text-red-600

                       transition

                       hover:bg-red-50">

                <x-heroicon-o-arrow-right-on-rectangle
                    class="h-5 w-5"/>

                <span
                    x-show="!sidebarCollapsed || isMobile"
                    x-transition>

                    Logout

                </span>

            </button>

        </form>

    </div>

</aside>