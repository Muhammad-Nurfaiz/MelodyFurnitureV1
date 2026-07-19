@props([
    'media'
])

<div
    class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">

    <img
        src="{{ Storage::url($media->media_url) }}"
        class="aspect-square w-full object-cover">

    <div class="space-y-3 p-3">

        <div class="text-xs text-gray-500">

            Urutan {{ $media->sort_order }}

        </div>

        <div class="flex gap-2">

            <x-admin.button
                size="sm"
                variant="outline"
                class="flex-1">

                Edit

            </x-admin.button>

            <x-admin.button
                size="sm"
                variant="danger"
                class="flex-1">

                Hapus

            </x-admin.button>

        </div>

    </div>

</div>