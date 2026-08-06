@props([
'name',

'accept' => 'image/*',

'preview' => null,

'label' => 'Klik atau Drag gambar ke sini',

'helper' => 'JPG, PNG, WEBP (Maksimal 2MB)',

'multiple' => false,

'temporaryUpload' => false,

])

@php


$hasError = $errors->has(
    str_replace('[]', '', $name)
);

@endphp

<div


x-data="fileUpload({
        multiple: @js($multiple),
        temporaryUpload: @js($temporaryUpload),
        preview: @js($preview),
    })"

x-init="init()"


x-on:beforeunload.window="clearObjectUrls()"

class="space-y-3"

>

{{-- ===================================================== --}}
{{-- UPLOAD / PREVIEW AREA --}}
{{-- ===================================================== --}}

<label

    @dragover.prevent="drag = true"

    @dragleave="drag = false"

    @drop.prevent="dropFile($event)"

    :class="{
        'pointer-events-none opacity-60': uploading,
        'border-blue-500 bg-blue-50': drag,
        'border-red-400 bg-red-50': {{ $hasError ? 'true' : 'false' }},

        'border-gray-300 hover:border-blue-500 hover:bg-blue-50':
            !drag &&
            !{{ $hasError ? 'true' : 'false' }}

    }"

    class="group relative flex min-h-64 cursor-pointer
           flex-col items-center justify-center
           overflow-hidden rounded-2xl border-2
           border-dashed transition-all duration-200"

>

    {{-- ================================================= --}}
    {{-- FILE INPUT --}}
    {{-- ================================================= --}}

    <input

        x-ref="input"
        :disabled="uploading"
        type="file"

        name="{{ $name }}"

        accept="{{ $accept }}"

        @if($multiple)

            multiple

        @endif

        @change="updatePreview"

        {{ $attributes
            ->except([
                'name',
                'accept',
                'multiple'
            ])
            ->merge([
                'class' => 'hidden'
            ])
        }}

    >

    <template x-if="uploading">

        <div
            class="absolute inset-0 z-20 flex items-center justify-center bg-white/80 backdrop-blur-sm">

            <div class="flex flex-col items-center gap-3">

                <svg
                    class="h-8 w-8 animate-spin text-blue-600"
                    viewBox="0 0 24 24"
                    fill="none">

                    <circle
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="3"
                        class="opacity-25"/>

                    <path
                        fill="currentColor"
                        class="opacity-75"
                        d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"/>

                </svg>

                <span
                    class="text-sm font-medium text-gray-700">

                    Mengupload...

                </span>

            </div>

        </div>

    </template>


    {{-- ================================================= --}}
    {{-- PREVIEW --}}
    {{-- ================================================= --}}

    <template x-if="previews.length">

        <div

            class="absolute inset-0 flex items-center
                   justify-center bg-white p-3"

        >

            @if($multiple)

                {{-- ===================================== --}}
                {{-- MULTIPLE PREVIEW --}}
                {{-- ===================================== --}}

                <div

                    class="grid w-full grid-cols-2
                           gap-3 md:grid-cols-3"

                >

                    <template

                        x-for="(image, index) in previews"

                        :key="image + index"

                    >

                        <div

                            class="group/image relative
                                   overflow-hidden rounded-xl
                                   border border-gray-200
                                   bg-gray-50"

                        >

                            <img

                                :src="image"

                                class="aspect-square w-full
                                       object-cover"

                            >

                            <button

                                type="button"

                                @click.stop="remove(index)"

                                class="absolute right-2 top-2
                                       hidden rounded-lg
                                       bg-white/95 p-1.5
                                       text-gray-700 shadow
                                       group-hover/image:block
                                       hover:bg-white"

                            >

                                <x-heroicon-o-x-mark
                                    class="h-4 w-4"
                                />

                            </button>

                        </div>

                    </template>

                </div>

            @else

                {{-- ===================================== --}}
                {{-- SINGLE PREVIEW --}}
                {{-- ===================================== --}}

                <div

                    class="group/preview relative h-full
                           w-full overflow-hidden rounded-xl"

                >

                    <template

                        x-for="(image, index) in previews"

                        :key="image"

                    >

                        <img

                            :src="image"

                            class="h-full max-h-56 w-full
                                   object-contain"

                        >

                    </template>

                    {{-- Hover overlay --}}

                    <div

                        class="absolute inset-0 flex items-center
                               justify-center bg-black/0
                               transition group-hover/preview:bg-black/40"

                    >

                        <span

                            class="rounded-lg bg-white/95
                                   px-4 py-2 text-sm font-medium
                                   text-gray-800 opacity-0
                                   shadow transition
                                   group-hover/preview:opacity-100"

                        >

                            Klik untuk mengganti gambar

                        </span>

                    </div>

                </div>

            @endif

        </div>

    </template>


    {{-- ================================================= --}}
    {{-- EMPTY STATE --}}
    {{-- ================================================= --}}

    <template x-if="!previews.length">

        <div

            class="flex flex-col items-center
                   justify-center px-8 py-10 text-center"

        >

            <div

                class="flex h-16 w-16 items-center
                       justify-center rounded-full
                       bg-blue-100"

            >

                <x-heroicon-o-photo
                    class="h-8 w-8 text-blue-600"
                />

            </div>

            <h4

                class="mt-5 text-base font-semibold
                       text-gray-800"

            >

                {{ $label }}

            </h4>

            <p

                class="mt-2 text-sm text-gray-500"

            >

                {{ $helper }}

            </p>

        </div>

    </template>


    {{-- ================================================= --}}
    {{-- SELECTED FILE NAME --}}
    {{-- ================================================= --}}

    <template x-if="filenames.length">

        <div

            class="absolute bottom-3 left-3 right-3
                   rounded-lg bg-white/95 px-3 py-2
                   shadow-sm"

        >

            <template

                x-for="file in filenames"

                :key="file"

            >

                <p

                    class="truncate text-xs font-medium
                           text-blue-600"

                    x-text="file"

                ></p>

            </template>

        </div>

    </template>

</label>


{{-- ===================================================== --}}
{{-- ACTIONS --}}
{{-- ===================================================== --}}

<template x-if="previews.length">

    <div

        class="flex flex-wrap gap-3"

    >

        <button

            type="button"

            @click="if(!uploading) $refs.input.click()"

            class="rounded-xl border border-gray-300
                   px-4 py-2 text-sm transition
                   hover:bg-gray-50"

        >

            Ganti Gambar

        </button>

        <button

            type="button"

            @click="if(!uploading) remove()"

            class="rounded-xl border border-red-200
                   px-4 py-2 text-sm text-red-600
                   transition hover:bg-red-50"

        >

            Hapus Semua

        </button>

    </div>

</template>

<template x-if="uploadError">

    <p
        class="text-sm text-red-600"
        x-text="uploadError">

    </p>

</template>


{{-- ===================================================== --}}
{{-- VALIDATION --}}
{{-- ===================================================== --}}

@if($hasError)

    <p class="text-sm text-red-600">

        {{ $errors->first(
            str_replace('[]', '', $name)
        ) }}

    </p>

@endif
</div>