@props([
    'paginator',
])
@if($paginator->hasPages())
<div class="flex items-center justify-between border-t border-gray-200 bg-white px-6 py-4">
    <p class="text-sm text-gray-500">
        Menampilkan
        <span class="font-semibold text-gray-900">
            {{ $paginator->firstItem() }}
        </span>
        –
        <span class="font-semibold text-gray-900">
            {{ $paginator->lastItem() }}
        </span>
        dari
        <span class="font-semibold text-gray-900">
            {{ $paginator->total() }}
        </span>
        data
    </p>
    {{ $paginator->links('components.admin.pagination.links') }}
</div>
@endif