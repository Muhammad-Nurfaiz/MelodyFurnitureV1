@if ($paginator->hasPages())
<nav
    role="navigation"
    aria-label="Pagination"
    class="inline-flex overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
    {{-- Previous --}}
    @if ($paginator->onFirstPage())
        <span
            class="inline-flex h-10 w-10 items-center justify-center
                border-r border-gray-200
                bg-white
                text-gray-300">
            <x-heroicon-o-chevron-left class="h-4 w-4"/>
        </span>
    @else
        <a
            href="{{ $paginator->previousPageUrl() }}"
            class="inline-flex h-10 w-10 items-center justify-center
                border-r border-gray-200
                bg-white
                text-gray-600
                transition
                hover:bg-gray-100">
            <x-heroicon-o-chevron-left class="h-4 w-4"/>
        </a>
    @endif
    {{-- Page Numbers --}}
    @foreach ($elements as $element)
        @if (is_string($element))
            <span
                class="px-2 text-sm text-gray-400">
                {{ $element }}
            </span>
        @endif
        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span
                        class="inline-flex h-10 min-w-10 items-center justify-center
                            border-r border-gray-200
                            bg-blue-600
                            px-4
                            text-sm
                            font-semibold
                            text-white">
                        {{ $page }}
                    </span>
                @else
                    <a
                        href="{{ $url }}"
                        class="inline-flex h-10 min-w-10 items-center justify-center
                            border-r border-gray-200
                            bg-white
                            px-4
                            text-sm
                            font-medium
                            text-gray-700
                            transition
                            hover:bg-gray-100">
                        {{ $page }}
                    </a>
                @endif
            @endforeach
        @endif
    @endforeach
    {{-- Next --}}
    @if ($paginator->hasMorePages())
        <a
            href="{{ $paginator->nextPageUrl() }}"
            class="inline-flex h-10 w-10 items-center justify-center
                bg-white
                text-gray-600
                transition
                hover:bg-gray-100">
            <x-heroicon-o-chevron-right class="h-4 w-4"/>
        </a>
    @else
        <span
            class="inline-flex h-10 w-10 items-center justify-center
                bg-white
                text-gray-300">
            <x-heroicon-o-chevron-right class="h-4 w-4"/>
        </span>
    @endif
</nav>
@endif