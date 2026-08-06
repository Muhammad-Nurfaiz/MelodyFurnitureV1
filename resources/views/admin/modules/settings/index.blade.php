@extends('admin.layouts.app')

@section('title', 'Settings')

@section('content')
@push('style')
    <style>
    .sortable-ghost{
        opacity:.45;
    }

    .sortable-drag{
        transform:rotate(1deg);
    }

    .sortable-chosen{
        cursor:grabbing;
    }
    </style>
@endpush

<div class="space-y-6">


{{-- ===================================================== --}}
{{-- PAGE HEADER --}}
{{-- ===================================================== --}}

<x-admin.page-header
    title="Settings"
    description="Kelola identitas, branding, media, dan informasi publik Melody Furniture."
/>

{{-- ===================================================== --}}
{{-- STORE IDENTITY --}}
{{-- ===================================================== --}}

<form
    method="POST"
    action="{{ route('admin.settings.store.update') }}"
>
    @csrf
    @method('PATCH')

    <x-admin.card>

        <x-admin.card-header
            title="Identitas Toko"
            description="Atur informasi utama yang digunakan pada website Melody Furniture."
        />

        <x-admin.card-body>

            <div class="grid grid-cols-1 gap-6">

                {{-- Store Name --}}
                <x-admin.form.group
                    label="Nama Toko"
                    required
                >

                    <x-admin.form.input
                        name="store_name"
                        value="{{ old('store_name', $settings?->store_name) }}"
                        placeholder="Masukkan nama toko..."
                    />

                    <x-admin.form.validation-error
                        alpine="errors.store_name"
                    />

                </x-admin.form.group>


                {{-- Store Description --}}
                <x-admin.form.group
                    label="Deskripsi Toko"
                >

                    <x-admin.form.textarea
                        name="store_description"
                        rows="5"
                        placeholder="Masukkan deskripsi toko..."
                    >{{ old('store_description', $settings?->store_description) }}</x-admin.form.textarea>

                    <x-admin.form.validation-error
                        alpine="errors.store_description"
                    />

                </x-admin.form.group>

            </div>

        </x-admin.card-body>


        <x-admin.card-footer>

            <x-admin.button type="submit">
                Simpan Perubahan
            </x-admin.button>

        </x-admin.card-footer>

    </x-admin.card>
</form>


{{-- ===================================================== --}}
{{-- BRANDING --}}
{{-- ===================================================== --}}


<div
    x-data="brandingCrud({
        updateUrl: @js(route('admin.settings.branding.update'))
    })"
    x-init="init()"
>
    <x-admin.card>

        <x-admin.card-header
            title="Branding"
            description="Kelola logo dan favicon yang digunakan pada website."
        />

        <x-admin.card-body>

            <div class="grid grid-cols-1 gap-8 md:grid-cols-2">

                {{-- Logo --}}

                <div>

                    <h3 class="mb-1 text-sm font-semibold text-gray-900">
                        Logo Toko
                    </h3>

                    <p class="mb-4 text-sm text-gray-500">
                        Logo utama Melody Furniture.
                    </p>

                    <div class="space-y-4">

                        @if(!empty($settings?->store_logo))

                            <div class="flex h-32 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 p-4">

                                <img
                                    src="{{ asset('storage/' . $settings->store_logo) }}"
                                    alt="Logo {{ $settings->store_name }}"
                                    class="max-h-full max-w-full object-contain"
                                >

                            </div>

                        @endif

                        <x-admin.button
                            type="button"
                            icon="pencil-square"
                            x-on:click="openLogo()"
                        >
                            Ganti Logo
                        </x-admin.button>

                    </div>

                </div>


                {{-- Favicon --}}

                <div>

                    <h3 class="mb-1 text-sm font-semibold text-gray-900">
                        Favicon
                    </h3>

                    <p class="mb-4 text-sm text-gray-500">
                        Icon yang digunakan pada tab browser.
                    </p>

                    <div class="space-y-4">

                        @if(!empty($settings?->store_favicon))

                            <div class="flex h-32 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 p-4">

                                <img
                                    src="{{ asset('storage/' . $settings->store_favicon) }}"
                                    alt="Favicon {{ $settings->store_name }}"
                                    class="max-h-16 max-w-16 object-contain"
                                >

                            </div>

                        @endif

                        <x-admin.button
                            type="button"
                            icon="pencil-square"
                            x-on:click="openFavicon()"
                        >
                            Ganti Favicon
                        </x-admin.button>

                    </div>

                </div>

            </div>

        </x-admin.card-body>

    </x-admin.card>

    <x-admin.modal.form
        name="branding-modal"
    >
        <input
            type="hidden"
            name="temporary_media_id"
            x-model="modal.data.temporary_media_id"
        >

        <input
            type="hidden"
            name="type"
            x-model="modal.data.type"
        >

    <x-admin.form.group
        label="Upload Gambar"
        required
    >

        <x-admin.form.file-upload
            name="branding_image"
            :temporary-upload="true"
            accept="image/png,image/jpeg,image/webp,image/x-icon"

            x-on:temporary-uploaded.window="
                modal.data.temporary_media_id = $event.detail.id
            "
        />

    </x-admin.form.group>

</x-admin.modal.form>

</div>

{{-- ===================================================== --}}
{{-- SOCIAL MEDIA --}}
{{-- ===================================================== --}}

<form
    method="POST"
    action="{{ route('admin.settings.store.update') }}"
>
    @csrf
    @method('PATCH')

    <x-admin.card>

        <x-admin.card-header
            title="Social Media"
            description="Atur link media sosial resmi Melody Furniture."
        />

        <x-admin.card-body>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

                {{-- Instagram --}}
                <x-admin.form.group
                    label="Instagram"
                >

                    <x-admin.form.input
                        type="url"
                        name="instagram_url"
                        value="{{ old('instagram_url', $settings?->instagram_url) }}"
                        placeholder="https://instagram.com/..."
                    />

                </x-admin.form.group>


                {{-- Facebook --}}
                <x-admin.form.group
                    label="Facebook"
                >

                    <x-admin.form.input
                        type="url"
                        name="facebook_url"
                        value="{{ old('facebook_url', $settings?->facebook_url) }}"
                        placeholder="https://facebook.com/..."
                    />

                </x-admin.form.group>


                {{-- TikTok --}}
                <x-admin.form.group
                    label="TikTok"
                >

                    <x-admin.form.input
                        type="url"
                        name="tiktok_url"
                        value="{{ old('tiktok_url', $settings?->tiktok_url) }}"
                        placeholder="https://tiktok.com/@..."
                    />

                </x-admin.form.group>


                {{-- YouTube --}}
                <x-admin.form.group
                    label="YouTube"
                >

                    <x-admin.form.input
                        type="url"
                        name="youtube_url"
                        value="{{ old('youtube_url', $settings?->youtube_url) }}"
                        placeholder="https://youtube.com/..."
                    />

                </x-admin.form.group>


                {{-- WhatsApp --}}
                <x-admin.form.group
                    label="WhatsApp"
                >

                    <x-admin.form.input
                        type="url"
                        name="whatsapp_url"
                        value="{{ old('whatsapp_url', $settings?->whatsapp_url) }}"
                        placeholder="https://wa.me/..."
                    />

                </x-admin.form.group>

            </div>

        </x-admin.card-body>


        <x-admin.card-footer>

            <x-admin.button type="submit">
                Simpan Social Media
            </x-admin.button>

        </x-admin.card-footer>

    </x-admin.card>
</form>

{{-- ===================================================== --}}
{{-- HERO SECTION --}}
{{-- ===================================================== --}}

<div
    x-data="heroCrud({
        storeUrl: @js(route('admin.settings.hero.store')),
        updateUrl: @js(url('admin/settings/hero')),
        deleteUrl: @js(url('admin/settings/hero')),
    })"
    x-init="init()"
>

    {{-- ================================================= --}}
    {{-- HERO CARD --}}
    {{-- ================================================= --}}

    <x-admin.card>

        <x-admin.card-header
            title="Hero Section"
            description="Kelola gambar dan konten utama yang tampil pada hero section website."
        >

            <x-slot:actions>

                <x-admin.button
                    icon="plus"
                    type="button"
                    x-on:click="openCreate()"
                >
                    Tambah Hero
                </x-admin.button>

            </x-slot:actions>

        </x-admin.card-header>

        <x-admin.card-body>

            @if(isset($heroSlides) && $heroSlides->count())

                <div class="space-y-4">

                    @foreach($heroSlides as $hero)

                        <div class="flex flex-col gap-4 rounded-xl border border-gray-200 p-4 md:flex-row">

                            <div class="h-32 w-full shrink-0 overflow-hidden rounded-lg bg-gray-100 md:w-56">

                                <img
                                    src="{{ asset('storage/' . $hero->image) }}"
                                    alt="{{ $hero->title }}"
                                    class="h-full w-full object-cover"
                                >

                            </div>

                            <div class="min-w-0 flex-1">

                                <div class="flex items-start justify-between gap-4">

                                    <div>

                                        @if($hero->eyebrow)

                                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                {{ $hero->eyebrow }}
                                            </p>

                                        @endif

                                        <h3 class="mt-1 text-base font-semibold text-gray-900">
                                            {{ $hero->title }}
                                        </h3>

                                    </div>

                                    <x-admin.badge
                                        :color="$hero->is_active ? 'green' : 'gray'"
                                    >
                                        {{ $hero->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </x-admin.badge>

                                </div>

                                @if($hero->description)

                                    <p class="mt-2 line-clamp-2 text-sm text-gray-500">
                                        {{ $hero->description }}
                                    </p>

                                @endif

                                <div class="mt-4 flex items-center gap-2">

                                    <x-admin.icon-button
                                        icon="pencil-square"
                                        type="button"
                                        x-on:click="openEdit({{ Js::from([
                                            'id' => $hero->id,
                                            'image' => $hero->image,
                                            'eyebrow' => $hero->eyebrow,
                                            'title' => $hero->title,
                                            'description' => $hero->description,
                                            'button_text' => $hero->button_text,
                                            'button_url' => $hero->button_url,
                                            'is_active' => $hero->is_active,
                                        ]) }})"
                                    />

                                    <x-admin.icon-button
                                        icon="trash"
                                        color="red"
                                        type="button"
                                        x-on:click="deleteHero('{{ $hero->id }}')"
                                    />

                                </div>

                            </div>

                        </div>

                    @endforeach

                </div>

            @else

                <x-admin.state.empty
                    title="Belum ada Hero"
                    description="Tambahkan hero pertama untuk menampilkan konten utama pada website."
                >

                    <x-slot:action>

                        <x-admin.button
                            icon="plus"
                            type="button"
                            x-on:click="openCreate()"
                        >
                            Tambah Hero
                        </x-admin.button>

                    </x-slot:action>

                </x-admin.state.empty>

            @endif

        </x-admin.card-body>

    </x-admin.card>


    {{-- ================================================= --}}
    {{-- HERO MODAL --}}
    {{-- ================================================= --}}

    <x-admin.modal.form
        name="hero-modal"
        size="lg"
    >
        <input
            type="hidden"
            name="temporary_media_id"
            x-model="modal.data.temporary_media_id"
        >
        <x-admin.form.group
            label="Gambar Hero"
            required
        >

            <div class="space-y-4">

                {{-- Existing / Preview Image --}}
                <template x-if="modal.data.image">

                    <div class="relative overflow-hidden rounded-xl border border-gray-200 bg-gray-50">

                        <img
                            :src="modal.data.imagePreview || '{{ asset('storage') }}/' + modal.data.image"
                            alt="Hero preview"
                            class="h-56 w-full object-cover"
                        >

                        <button
                            type="button"
                            class="absolute right-3 top-3 inline-flex h-9 w-9 items-center justify-center rounded-lg bg-black/60 text-white transition hover:bg-black/80"
                            x-on:click="removeImage()"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>

                    </div>

                </template>


                {{-- Upload --}}
                <div
                    x-show="!modal.data.image"
                    class="rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 p-6 text-center transition hover:border-blue-400 hover:bg-blue-50/30"
                >

                    <input
                        type="file"
                        id="hero-image"
                        name="image"
                        accept="image/png,image/jpeg,image/webp"
                        class="hidden"
                        x-on:change="handleImageChange($event)"
                    >

                    <label
                        for="hero-image"
                        class="flex cursor-pointer flex-col items-center"
                    >

                        <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-600">

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-6 w-6"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M3 16.5V18a2 2 0 002 2h14a2 2 0 002-2v-1.5M7 10l5-5m0 0l5 5m-5-5v12"
                                />
                            </svg>

                        </div>

                        <span class="text-sm font-semibold text-gray-700">
                            Pilih gambar hero
                        </span>

                        <span class="mt-1 text-xs text-gray-500">
                            PNG, JPG, atau WEBP · Maksimal 5MB
                        </span>

                    </label>

                </div>


                {{-- Image Error --}}
                <x-admin.form.validation-error
                    alpine="errors.image"
                />

            </div>

        </x-admin.form.group>


        {{-- ===================================================== --}}
        {{-- EYEbROW --}}
        {{-- ===================================================== --}}

        <x-admin.form.group
            label="Eyebrow"
        >

            <x-admin.form.input
                name="eyebrow"
                x-model="modal.data.eyebrow"
                placeholder="Contoh: Koleksi Terbaru"
                maxlength="100"
            />

            <p class="mt-1.5 text-xs text-gray-500">
                Teks kecil yang tampil di atas judul hero.
            </p>

            <x-admin.form.validation-error
                alpine="errors.eyebrow"
            />

        </x-admin.form.group>


        {{-- ===================================================== --}}
        {{-- TITLE --}}
        {{-- ===================================================== --}}

        <x-admin.form.group
            label="Judul"
            required
        >

            <x-admin.form.input
                name="title"
                x-model="modal.data.title"
                placeholder="Masukkan judul hero..."
                maxlength="255"
            />

            <x-admin.form.validation-error
                alpine="errors.title"
            />

        </x-admin.form.group>


        {{-- ===================================================== --}}
        {{-- DESCRIPTION --}}
        {{-- ===================================================== --}}

        <x-admin.form.group
            label="Deskripsi"
        >

            <x-admin.form.textarea
                name="description"
                rows="4"
                x-model="modal.data.description"
                placeholder="Masukkan deskripsi singkat hero..."
                maxlength="1000"
            ></x-admin.form.textarea>

            <x-admin.form.validation-error
                alpine="errors.description"
            />

        </x-admin.form.group>


        {{-- ===================================================== --}}
        {{-- BUTTON --}}
        {{-- ===================================================== --}}

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

            {{-- Button Text --}}
            <x-admin.form.group
                label="Teks Tombol"
            >

                <x-admin.form.input
                    name="button_text"
                    x-model="modal.data.button_text"
                    placeholder="Contoh: Lihat Koleksi"
                    maxlength="100"
                />

                <x-admin.form.validation-error
                    alpine="errors.button_text"
                />

            </x-admin.form.group>


            {{-- Button URL --}}
            <x-admin.form.group
                label="URL Tombol"
            >

                <x-admin.form.input
                    type="url"
                    name="button_url"
                    x-model="modal.data.button_url"
                    placeholder="https://..."
                />

                <x-admin.form.validation-error
                    alpine="errors.button_url"
                />

            </x-admin.form.group>

        </div>


        {{-- ===================================================== --}}
        {{-- ACTIVE --}}
        {{-- ===================================================== --}}

        <x-admin.form.group
            label="Status"
        >

            <label class="flex cursor-pointer items-center justify-between rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">

                <div>

                    <p class="text-sm font-semibold text-gray-900">
                        Aktifkan Hero
                    </p>

                    <p class="mt-0.5 text-xs text-gray-500">
                        Hero aktif akan ditampilkan pada website.
                    </p>

                </div>

                <input
                    type="checkbox"
                    name="is_active"
                    value="1"
                    x-model="modal.data.is_active"
                    class="h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                >

            </label>

            <x-admin.form.validation-error
                alpine="errors.is_active"
            />

        </x-admin.form.group>


        {{-- ===================================================== --}}
        {{-- HIDDEN ID --}}
        {{-- ===================================================== --}}

        <input
            type="hidden"
            name="id"
            x-model="modal.data.id"
        >

    </x-admin.modal.form>

</div>


{{-- ===================================================== --}}
{{-- PROMO BANNER --}}
{{-- ===================================================== --}}
<div
    x-data="promoCrud({
        storeUrl: @js(route('admin.settings.promo.store')),
        updateUrl: @js(url('admin/settings/promo')),
        deleteUrl: @js(url('admin/settings/promo')),
        sortUrl: @js(route('admin.settings.promo.sort')),
    })"
    x-init="init()"
>
    <x-admin.card>

        <x-admin.card-header
            title="Promo Banner"
            description="Kelola banner promosi yang ditampilkan pada website."
        >

            <x-slot:actions>

                <x-admin.button
                    icon="plus"
                    type="button"
                    x-on:click="openCreatePromo()"
                >
                    Tambah Banner
                </x-admin.button>
                <span
                    x-show="sorting"
                    class="text-sm text-blue-600 font-medium"
                >
                    Menyimpan urutan...
                </span>

            </x-slot:actions>

        </x-admin.card-header>

        <x-admin.card-body>

            @if(isset($promoBanners) && $promoBanners->count())

                <div
                    id="promo-sortable"
                    x-ref="sortableContainer"
                    class="grid grid-cols-1 gap-4 md:grid-cols-2"
                >

                    @foreach($promoBanners as $banner)

                        <div
                            class="overflow-hidden rounded-xl border border-gray-200 bg-white transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
                            :class="{
                                'opacity-70 pointer-events-none cursor-wait': sorting
                            }"
                            data-id="{{ $banner->id }}"
                        >

                            <div class="aspect-[16/7] overflow-hidden bg-gray-100">

                                <img
                                    src="{{ asset('storage/' . $banner->image) }}"
                                    alt="{{ $banner->alt ?? 'Promo Banner' }}"
                                    class="h-full w-full object-cover"
                                >

                            </div>

                            <div class="space-y-3 p-4">

                                <div class="flex items-start justify-between gap-4">

                                    <div class="min-w-0">
                                        <div
                                            class="data-sort-handle mb-2 inline-flex cursor-move items-center gap-2 rounded-md border border-gray-200 bg-gray-50 px-2 py-1 text-xs text-gray-500 hover:bg-gray-100"
                                            
                                            >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                class="h-4 w-4"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                                stroke-width="2"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M4 8h16M4 12h16M4 16h16"
                                                />
                                            </svg>

                                            Geser
                                        </div>
                                        <p class="truncate text-sm font-medium text-gray-900">
                                            {{ $banner->alt ?? 'Tanpa alt text' }}
                                        </p>

                                        @if($banner->url)

                                            <p class="mt-1 truncate text-xs text-gray-500">
                                                {{ $banner->url }}
                                            </p>

                                        @endif

                                    </div>

                                    <x-admin.badge
                                        :color="$banner->is_active ? 'green' : 'gray'"
                                    >
                                        {{ $banner->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </x-admin.badge>

                                </div>

                                <div class="flex items-center gap-2">

                                    <x-admin.icon-button
                                        icon="pencil-square"
                                        type="button"
                                        x-on:click="openEditPromo({{ Js::from([
                                            'id'        => $banner->id,
                                            'image'     => asset('storage/'.$banner->image),
                                            'url'       => $banner->url,
                                            'alt'       => $banner->alt,
                                            'is_active' => (bool) $banner->is_active,
                                        ]) }})"
                                    />

                                    <x-admin.icon-button
                                        icon="trash"
                                        color="red"
                                        type="button"
                                        x-on:click="deletePromo({{ Js::from($banner->id) }})"
                                    />

                                </div>

                            </div>

                        </div>

                    @endforeach

                </div>

            @else

                <x-admin.state.empty
                    title="Belum ada Promo Banner"
                    description="Tambahkan banner promosi pertama untuk website."
                >

                    <x-slot:action>

                        <x-admin.button
                            icon="plus"
                            type="button"
                            x-on:click="openCreatePromo()"
                        >
                            Tambah Banner
                        </x-admin.button>

                    </x-slot:action>

                </x-admin.state.empty>

            @endif

        </x-admin.card-body>

    </x-admin.card>

    <x-admin.modal
        name="promo-modal"
        size="xl"
    >

        <form
            x-on:submit="submitPromo($event)"
            class="flex h-full flex-col"
        >

            {{-- Header --}}
            <div class="border-b border-gray-200 px-6 py-4">

                <h2
                    class="text-lg font-semibold text-gray-900"
                    x-text="promoModal.title"
                ></h2>

            </div>

            {{-- Body --}}
            <div class="flex-1 overflow-y-auto p-6">

                <div class="space-y-6">

                    {{-- ===================================================== --}}
                    {{-- IMAGE --}}
                    {{-- ===================================================== --}}
                    <x-admin.form.group
                        label="Banner"
                        required
                    >

                        <div class="space-y-4">
                            {{-- Upload --}}
                            <x-admin.form.file-upload
                                name="promo_image"
                                :temporary-upload="true"
                                x-on:preview-created="promoModal.data.image"
                                x-on:temporary-uploaded="promoModal.data.temporary_media_id = $event.detail.id"
                            />

                            <x-admin.form.validation-error
                                alpine="promoModal.errors.temporary_media_id"
                            />

                        </div>

                    </x-admin.form.group>


                    {{-- ===================================================== --}}
                    {{-- URL --}}
                    {{-- ===================================================== --}}
                    <x-admin.form.group
                        label="URL Banner"
                    >

                        <x-admin.form.input
                            name="url"
                            placeholder="https://..."
                            x-model="promoModal.data.url"
                            x-bind:disabled="promoModal.loading"
                        />

                        <x-admin.form.validation-error
                            alpine="promoModal.errors.url"
                        />

                    </x-admin.form.group>


                    {{-- ===================================================== --}}
                    {{-- ALT --}}
                    {{-- ===================================================== --}}
                    <x-admin.form.group
                        label="Alt Text"
                    >

                        <x-admin.form.input
                            name="alt"
                            placeholder="Promo Furniture"
                            x-model="promoModal.data.alt"
                            x-bind:disabled="promoModal.loading"
                        />

                        <x-admin.form.validation-error
                            alpine="promoModal.errors.alt"
                        />

                    </x-admin.form.group>


                    {{-- ===================================================== --}}
                    {{-- STATUS --}}
                    {{-- ===================================================== --}}
                    <x-admin.form.group
                        label="Status"
                    >

                        <label class="flex cursor-pointer items-center justify-between rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">

                            <div>

                                <p class="text-sm font-semibold text-gray-900">
                                    Aktifkan Banner
                                </p>

                                <p class="mt-1 text-xs text-gray-500">
                                    Banner aktif akan tampil pada website.
                                </p>

                            </div>

                            <input
                                type="checkbox"
                                value="1"
                                x-model="promoModal.data.is_active"
                                class="h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            >

                        </label>

                        <x-admin.form.validation-error
                            alpine="promoModal.errors.is_active"
                        />

                    </x-admin.form.group>

                </div>

            </div>
            <input
                type="hidden"
                x-model="promoModal.data.temporary_media_id"
            >

            {{-- Footer --}}
            <div
                class="flex justify-end gap-3 border-t border-gray-200 px-6 py-4"
            >

                <x-admin.button
                    type="button"
                    color="secondary"
                    x-on:click="closePromoModal()"
                >
                    Batal
                </x-admin.button>

                <x-admin.button
                    type="submit"
                    color="primary"
                    x-bind:disabled="promoModal.loading"
                >

                    <span
                        x-show="!promoModal.loading"
                        x-text="promoModal.submitText"
                    ></span>

                    <span x-show="promoModal.loading">
                        Menyimpan...
                    </span>

                </x-admin.button>

            </div>

        </form>

    </x-admin.modal>
</div>
</div>

@endsection