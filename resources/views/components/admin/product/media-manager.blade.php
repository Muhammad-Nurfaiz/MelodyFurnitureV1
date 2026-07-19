@props([
    'media' => collect(),
])

<div
    x-data="mediaManager(@js(
        $media->map(fn($item) => [
            'id'        => $item->id,
            'url'       => Storage::url($item->media_url),
            'media_url' => $item->media_url,
            'uploaded'  => true,
            'is_main'   => (bool) $item->is_main,
            'file'      => null,
        ])->values()
    ))"
    x-init="init()"
    class="space-y-6"
>

    {{-- ================= Hidden Inputs ================= --}}

    <input
        type="hidden"
        name="main_media"
        :value="media.find(item => item.is_main)?.id ?? ''"
    >

    <template
        x-for="image in media.filter(item => item.temporary)"
        :key="'temp-'+image.id"
    >
        <input
            type="hidden"
            name="temporary_media[]"
            :value="image.id"
        >
    </template>

    <template
        x-for="image in media"
        :key="'order-'+image.id"
    >
        <input
            type="hidden"
            name="media_order[]"
            :value="image.id"
        >
    </template>

    <template
        x-for="id in deletedMedia"
        :key="'delete-'+id"
    >
        <input
            type="hidden"
            name="deleted_media[]"
            :value="id"
        >
    </template>

    {{-- ================= GRID ================= --}}

    <div
        id="media-grid"
        class="flex flex-wrap gap-4"
    >

        <template
            x-for="(image,index) in media"
            :key="image.id"
        >

            <div
                class="media-item group relative"
                :data-id="image.id"
            >

                {{-- IMAGE --}}
                <div
                    class="overflow-hidden rounded-xl border-2 transition-all duration-200"
                    :class="image.is_main
                        ? 'border-primary shadow-lg'
                        : 'border-gray-200'"
                >

                    <img
                        :src="image.url"
                        class="h-28 w-28 object-cover"
                    >

                </div>

                {{-- BADGE --}}
                <div
                    x-show="image.is_main"
                    x-transition.opacity
                    class="absolute top-2 left-2 rounded bg-yellow-500 px-2 py-1 text-[10px] text-white font-semibold"
                >
                    Thumbnail
                </div>

                {{-- TOOLBAR --}}
                <div
                    x-show = "!uploading"
                    class="absolute inset-x-0 bottom-2 flex justify-center opacity-0 group-hover:opacity-100 transition"
                >

                    <div
                        class="flex gap-1 rounded-lg bg-white p-1 shadow-lg"
                    >

                        {{-- Thumbnail --}}
                        <button
                            type="button"
                            @click="setMain(index)"
                            class="rounded p-1 hover:bg-yellow-100"
                        >

                            {{-- aktif --}}
                            <template x-if="image.is_main">
                                <x-heroicon-s-star
                                    class="h-4 w-4 text-yellow-500"/>
                            </template>

                            {{-- non aktif --}}
                            <template x-if="!image.is_main">
                                <x-heroicon-o-star
                                    class="h-4 w-4 text-yellow-500"/>
                            </template>

                        </button>

                        {{-- Delete --}}
                        <button
                            type="button"
                            @click="remove(index)"
                            class="rounded p-1 hover:bg-red-100"
                        >
                            <x-heroicon-o-trash
                                class="h-4 w-4 text-red-500"/>
                        </button>

                        {{-- Drag --}}
                        <div
                            class="drag-handle cursor-move rounded p-1 hover:bg-gray-100"
                        >
                            <x-heroicon-o-bars-3
                                class="h-4 w-4 text-gray-500"/>
                        </div>

                    </div>

                </div>

            </div>

        </template>

        {{-- Upload --}}
        <label
            :class="uploading
                ? 'pointer-events-none opacity-50'
                : ''"
            class="flex h-28 w-28 cursor-pointer items-center justify-center rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 hover:border-primary"
        >

            <input
                type="file"
                multiple
                class="hidden"
                accept=".jpg,.jpeg,.png,.webp"
                @change="preview($event)"
            >

            <div class="text-center">

                <x-heroicon-o-plus
                    class="mx-auto h-8 w-8 text-primary"/>

                <div
                    class="mt-1 text-xs font-medium text-gray-600"
                >
                    Upload
                </div>

            </div>
            
        </label>
        <div
            x-show="uploading"
            x-cloak
            class="absolute inset-0 flex items-center justify-center rounded-xl bg-white/80"
        >

            <svg
                class="h-6 w-6 animate-spin text-primary"
                fill="none"
                viewBox="0 0 24 24"
            >
                <circle
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="4"
                    class="opacity-25"
                />

                <path
                    fill="currentColor"
                    class="opacity-75"
                    d="M4 12a8 8 0 018-8v8z"
                />

            </svg>

        </div>

    </div>

</div>