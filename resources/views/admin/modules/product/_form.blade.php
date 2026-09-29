@csrf

@isset($product)

    @method('PUT')

@endisset

@php
    $product ??= null;
    $specification = $product?->specification;
@endphp

<div
    x-data="productForm(@js([
        'originalPrice' => old('original_price', $product?->original_price),
        'discountPrice' => old('discount_price', $product?->discount_price),
        'discountPercentage' => old('discount_percentage', $product?->discount_percentage),
        'isSale' => old('is_sale', $product?->is_sale),

        'variantsEnabled' => old(
            'variants_enabled',
            $product?->variants?->isNotEmpty() ?? false
        ),

        'variants' => old(
            'variants',
            $product?->variants
                ?->map(fn ($variant) => [
                    'id' => $variant->id,
                    'name' => $variant->name,
                    'sku' => $variant->sku,
                    'original_price' => $variant->original_price,
                    'discount_price' => $variant->discount_price,
                    'media_id' => $variant->media_id,
                    'ready_stock' => $variant->ready_stock,
                ])
                ->values()
                ->all() ?? []
        ),
    ]))"
    class="space-y-8">

    @if ($errors->any())
        <div class="mb-5 rounded-lg bg-red-100 p-4 text-red-700">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    {{-- ===================================================== --}}
    {{-- WIZARD --}}
    {{-- ===================================================== --}}

    <x-admin.wizard.progress
        :steps="[
            'Informasi Produk',
            'Spesifikasi',
            'Harga & Stok',
            'Media & Publish',
        ]"
    />

    {{-- ===================================================== --}}
    {{-- STEP 1 --}}
    {{-- ===================================================== --}}

    <x-admin.wizard.step
        number="1"
        title="Informasi Produk"
        description="Masukkan informasi dasar produk seperti nama, kategori, series, deskripsi, dan detail produk."
    >

        <x-admin.card>

            <div class="space-y-6 p-5">

                {{-- Nama Produk --}}
                <x-admin.form.group
                    label="Nama Produk"
                    required
                >
                    <x-admin.form.input
                        name="name"
                        x-ref="name"
                        :value="old('name', $product?->name)"
                        placeholder="Contoh: Kursi Makan Scandinavian"
                    />
                </x-admin.form.group>

                {{-- SKU Produk --}}
                <x-admin.form.group
                    label="SKU Produk"
                    required
                >
                    <x-admin.form.input
                        name="sku"
                        x-ref="sku"
                        :value="old('sku', $product?->sku)"
                        placeholder="Contoh: MF-001-ABC"
                        maxlength="100"
                        autocomplete="off"
                        @input="normalizeSku()"
                    />

                    <p class="mt-1 text-xs text-gray-500">
                        Masukkan SKU produk dari perusahaan. Gunakan huruf, angka, dan tanda strip (-).
                    </p>
                </x-admin.form.group>

                {{-- Kategori & Series --}}
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

                    {{-- Kategori --}}
                    <x-admin.form.group
                        label="Kategori"
                        required
                    >
                        <x-admin.form.select
                            name="category_id"
                            x-ref="category"
                            placeholder="Pilih kategori"
                        >
                            @foreach($categories as $category)

                                <option
                                    value="{{ $category->id }}"
                                    @selected(
                                        old('category_id', $product?->category_id) == $category->id
                                    )
                                >
                                    {{ $category->name }}
                                </option>

                            @endforeach
                        </x-admin.form.select>
                    </x-admin.form.group>

                    {{-- Series --}}
                    <x-admin.form.group
                        label="Series"
                    >
                        <x-admin.form.select
                            name="series_id"
                            placeholder="Tanpa Series"
                        >
                            @foreach($series as $item)

                                <option
                                    value="{{ $item->id }}"
                                    @selected(
                                        old('series_id', $product?->series_id) == $item->id
                                    )
                                >
                                    {{ $item->name }}
                                </option>

                            @endforeach
                        </x-admin.form.select>
                    </x-admin.form.group>

                </div>

                {{-- Deskripsi --}}
                <x-admin.form.group
                    label="Deskripsi"
                    required
                >
                    <x-admin.form.textarea
                        name="description"
                        x-ref="description"
                        rows="5"
                        placeholder="Deskripsi singkat produk..."
                    >{{ old('description', $product?->description) }}</x-admin.form.textarea>
                </x-admin.form.group>

                {{-- Detail Produk --}}
                <x-admin.form.group
                    label="Detail Produk"
                    required
                >
                    <x-admin.form.textarea
                        name="product_detail"
                        x-ref="product_detail"
                        rows="10"
                        placeholder="Detail lengkap produk..."
                    >{{ old('product_detail', $product?->product_detail) }}</x-admin.form.textarea>
                </x-admin.form.group>

            </div>

        </x-admin.card>

    </x-admin.wizard.step>

    {{-- ===================================================== --}}
    {{-- STEP 2 --}}
    {{-- ===================================================== --}}

    <x-admin.wizard.step
        number="2"
        title="Spesifikasi Produk"
        description="Masukkan informasi ukuran, berat, kapasitas beban, material, dan kebutuhan perakitan produk."
    >

        <x-admin.card>

            <div class="space-y-6 p-5">

                {{-- Dimensi --}}
                <x-admin.form.group
                    label="Dimensi Produk"
                    required
                >
                    <x-admin.form.input
                        name="dimensions"
                        x-ref="dimensions"
                        :value="old(
                            'dimensions',
                            $specification?->dimensions
                        )"
                        placeholder="Contoh: 80 × 60 × 75 cm"
                    />
                </x-admin.form.group>

                {{-- Berat --}}
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

                    {{-- Berat Produk --}}
                    <x-admin.form.group
                        label="Berat Produk"
                        required
                    >
                        <x-admin.form.input
                            name="weight"
                            x-ref="weight"
                            type="number"
                            step="0.01"
                            min="0"
                            :value="old(
                                'weight',
                                $specification?->weight
                            )"
                            placeholder="Contoh: 12.50 kg"
                        />
                    </x-admin.form.group>

                    {{-- Berat Setelah Packing --}}
                    <x-admin.form.group
                        label="Berat Setelah Packing"
                        required
                    >
                        <x-admin.form.input
                            name="packing_weight"
                            x-ref="packing_weight"
                            type="number"
                            step="0.01"
                            min="0"
                            :value="old(
                                'packing_weight',
                                $specification?->packing_weight
                            )"
                            placeholder="Contoh: 15.00 kg"
                        />
                    </x-admin.form.group>

                </div>

                {{-- Kapasitas Beban --}}
                <x-admin.form.group
                    label="Kapasitas Beban"
                    required
                >
                    <x-admin.form.input
                        name="load_capacity"
                        x-ref="load_capacity"
                        :value="old(
                            'load_capacity',
                            $specification?->load_capacity
                        )"
                        placeholder="Contoh: 120 kg"
                    />
                </x-admin.form.group>

                {{-- Assembly --}}
                <x-admin.form.group
                    label="Perakitan Produk"
                >
                    <label class="flex cursor-pointer items-center gap-3">

                        <input
                            type="checkbox"
                            name="assembly_required"
                            value="1"
                            @checked(
                                old(
                                    'assembly_required',
                                    $specification?->assembly_required
                                )
                            )
                            class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        >

                        <span class="text-sm text-gray-700">
                            Produk memerlukan perakitan
                        </span>

                    </label>
                </x-admin.form.group>

            </div>

        </x-admin.card>

    </x-admin.wizard.step>

    {{-- ===================================================== --}}
    {{-- STEP 3 --}}
    {{-- ===================================================== --}}

    <x-admin.wizard.step
        number="3"
        title="Media Produk"
        description="Tambahkan gambar produk dan video tutorial."
    >

        {{-- =============================================== --}}
        {{-- Media --}}
        {{-- =============================================== --}}

        <x-admin.card style="margin-bottom: 20px;">

            <div class="space-y-6 p-5">

                {{-- Thumbnail --}}

                <x-admin.form.group label="Media Produk">

                    <x-admin.product.media-manager
                        :media="$product?->media ?? collect()" />

                </x-admin.form.group>

                {{-- Video --}}

                <x-admin.form.group
                    label="Video Tutorial">

                    <x-admin.form.input
                        name="video_tutorial_url"
                        :value="old(
                            'video_tutorial_url',
                            $product?->video_tutorial_url
                        )"
                        placeholder="https://youtube.com/..."/>

                </x-admin.form.group>

            </div>

        </x-admin.card>

    </x-admin.wizard.step>

    {{-- ===================================================== --}}
    {{-- STEP 4 --}}
    {{-- ===================================================== --}}

    <x-admin.wizard.step
        number="4"
        title="Harga, Stok & Varian"
        description="Atur harga, diskon, stok, varian, dan informasi statistik produk."
    >

        {{-- =============================================== --}}
        {{-- Harga Produk --}}
        {{-- =============================================== --}}

        <div class="mb-4">
            <h3 class="text-sm font-semibold text-gray-800">
                Harga Produk
            </h3>

            <p class="mt-1 text-xs text-gray-500">
                Atur harga normal dan harga diskon produk.
            </p>
        </div>

        <x-admin.card style="margin-bottom: 20px;">

            <div class="space-y-6 p-5">

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

                    {{-- Harga Normal --}}
                    <x-admin.form.group
                        label="Harga Normal"
                        required
                    >
                        <x-admin.form.input
                            name="original_price"
                            x-ref="original_price"
                            x-model="originalPrice"
                            @input="calculateDiscount()"
                            type="number"
                            min="0"
                            step="0.01"
                            :value="old(
                                'original_price',
                                $product?->original_price
                            )"
                            placeholder="Contoh: 2500000"
                        />
                    </x-admin.form.group>

                    {{-- Harga Diskon --}}
                    <x-admin.form.group
                        label="Harga Diskon"
                    >
                        <x-admin.form.input
                            name="discount_price"
                            x-ref="discount_price"
                            x-model="discountPrice"
                            @input="calculateDiscount()"
                            type="number"
                            min="0"
                            step="0.01"
                            :value="old(
                                'discount_price',
                                $product?->discount_price
                            )"
                            placeholder="Contoh: 2250000"
                        />
                    </x-admin.form.group>

                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

                    {{-- Persentase Diskon --}}
                    <x-admin.form.group
                        label="Persentase Diskon"
                    >
                        <x-admin.form.input
                            name="discount_percentage"
                            x-ref="discount_percentage"
                            x-model="discountPercentage"
                            type="number"
                            min="0"
                            max="100"
                            step="1"
                            :value="old(
                                'discount_percentage',
                                $product?->discount_percentage
                            )"
                            placeholder="Contoh: 10"
                            readonly
                        />

                        <p class="mt-1 text-xs text-gray-500">
                            Nilai persentase akan dihitung otomatis berdasarkan harga normal dan harga diskon.
                        </p>
                    </x-admin.form.group>

                    {{-- Status Sale --}}
                    <x-admin.form.group
                        label="Status Sale"
                    >
                        <label class="flex cursor-pointer items-center gap-3">
                            <input type="hidden" name="is_sale" value="0">
                            <input
                                type="checkbox"
                                name="is_sale"
                                value="1"
                                x-ref="is_sale"
                                x-model="isSale"
                                @checked(
                                    old(
                                        'is_sale',
                                        $product?->is_sale
                                    )
                                )
                                class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            >

                            <span class="text-sm text-gray-700">
                                Produk sedang dalam program sale
                            </span>

                        </label>
                    </x-admin.form.group>

                </div>

            </div>

        </x-admin.card>


        {{-- =============================================== --}}
        {{-- Stok & Varian --}}
        {{-- =============================================== --}}

        <div class="mb-4">
            <h3 class="text-sm font-semibold text-gray-800">
                Stok & Varian
            </h3>

            <p class="mt-1 text-xs text-gray-500">
                Tentukan apakah produk menggunakan stok tunggal atau memiliki beberapa varian.
            </p>
        </div>

        <x-admin.card style="margin-bottom: 20px;">

            <div class="space-y-6 p-5">

                {{-- Gunakan Varian --}}
                <div>
                    <label class="flex cursor-pointer items-center gap-3">
                        <input
                            type="hidden"
                            name="variants_enabled"
                            value="0"
                        >

                        <input
                            type="checkbox"
                            name="variants_enabled"
                            value="1"
                            x-model="variantsEnabled"
                            @change="variantsEnabled ? enableVariants() : disableVariants()"
                            class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        >

                        <span class="text-sm font-medium text-gray-700">
                            Gunakan Varian Warna
                        </span>
                    </label>

                    <p class="mt-1 text-xs text-gray-500">
                        Aktifkan jika produk memiliki pilihan warna dengan stok masing-masing.
                    </p>
                </div>

                {{-- =========================================== --}}
                {{-- Stok Produk Tanpa Varian --}}
                {{-- =========================================== --}}

                <div
                    x-show="!variantsEnabled"
                    x-cloak
                >
                    <x-admin.form.group
                        label="Ready Stock"
                        required
                    >
                        <x-admin.form.input
                            name="ready_stock"
                            x-ref="ready_stock"
                            type="number"
                            min="0"
                            step="1"
                            :value="old(
                                'ready_stock',
                                $product?->ready_stock ?? 0
                            )"
                            placeholder="Contoh: 20"
                        />

                        <p class="mt-1 text-xs text-gray-500">
                            Jumlah produk yang tersedia dan siap dijual.
                        </p>
                    </x-admin.form.group>
                </div>

                {{-- =========================================== --}}
                {{-- Varian --}}
                {{-- =========================================== --}}

                <div
                    x-show="variantsEnabled"
                    x-cloak
                    class="space-y-4"
                >

                    <div>
                        <h3 class="text-sm font-semibold text-gray-800">
                            Varian Produk
                        </h3>

                        <p class="mt-1 text-xs text-gray-500">
                            Tambahkan setiap varian secara terpisah. Nama, SKU, harga, foto,
                            dan stok dapat berbeda untuk setiap varian.
                        </p>
                    </div>


                    {{-- ================= Variant List ================= --}}

                    <div class="space-y-4">

                        <template
                            x-for="(variant, index) in variants"
                            :key="variant.id || `new-${index}`"
                        >

                            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">

                                {{-- ================= Header ================= --}}

                                <div class="mb-4 flex items-center justify-between">

                                    <div>
                                        <p class="text-sm font-semibold text-gray-800">
                                            Varian <span x-text="index + 1"></span>
                                        </p>

                                        <p
                                            x-show="variant.id"
                                            class="mt-0.5 text-xs text-gray-500"
                                        >
                                            Varian tersimpan
                                        </p>
                                    </div>

                                    <button
                                        type="button"
                                        x-show="isVariantFilled(variant)"
                                        @click="removeVariant(index)"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-red-200 text-red-600 transition hover:bg-red-50"
                                        title="Hapus varian"
                                    >
                                        <x-heroicon-o-trash class="h-4 w-4" />
                                    </button>

                                </div>


                                {{-- ================= Existing Variant ID ================= --}}

                                <input
                                    type="hidden"
                                    x-bind:name="
                                        variant.id
                                            ? `variants[${index}][id]`
                                            : null
                                    "
                                    x-model="variant.id"
                                >


                                {{-- ================= Name + SKU ================= --}}

                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">

                                    {{-- Nama Variant --}}

                                    <div>

                                        <label class="mb-1.5 block text-sm font-medium text-gray-700">
                                            Nama Varian
                                        </label>

                                        <input
                                            type="text"
                                            x-bind:name="
                                                isVariantFilled(variant)
                                                    ? `variants[${index}][name]`
                                                    : null
                                            "
                                            x-model="variant.name"
                                            @input="handleVariantNameInput(index)"
                                            placeholder="Contoh: Natural"
                                            maxlength="100"
                                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                        >

                                        <p class="mt-1 text-xs text-gray-500">
                                            Bebas sesuai jenis varian produk, misalnya warna atau ukuran.
                                        </p>

                                    </div>


                                    {{-- SKU --}}

                                    <div>

                                        <label class="mb-1.5 block text-sm font-medium text-gray-700">
                                            SKU
                                        </label>

                                        <input
                                            type="text"
                                            x-bind:name="
                                                isVariantFilled(variant)
                                                    ? `variants[${index}][sku]`
                                                    : null
                                            "
                                            x-model="variant.sku"
                                            @input="variant.sku = variant.sku.toUpperCase()"
                                            maxlength="100"
                                            placeholder="Contoh: MF-NAT-001"
                                            class="block w-full rounded-lg border-gray-300 uppercase shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                        >

                                        <p class="mt-1 text-xs text-gray-500">
                                            SKU varian diinput secara manual.
                                        </p>

                                    </div>

                                </div>


                                {{-- ================= Pricing ================= --}}

                                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">

                                    {{-- Harga Asli --}}

                                    <div>

                                        <label class="mb-1.5 block text-sm font-medium text-gray-700">
                                            Harga Asli
                                        </label>

                                        <input
                                            type="number"
                                            x-bind:name="
                                                isVariantFilled(variant)
                                                    ? `variants[${index}][original_price]`
                                                    : null
                                            "
                                            x-model="variant.original_price"
                                            min="0"
                                            step="0.01"
                                            placeholder="Contoh: 800000"
                                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                        >

                                    </div>


                                    {{-- Harga Diskon --}}

                                    <div>

                                        <label class="mb-1.5 block text-sm font-medium text-gray-700">
                                            Harga Diskon
                                        </label>

                                        <input
                                            type="number"
                                            x-bind:name="
                                                isVariantFilled(variant)
                                                    ? `variants[${index}][discount_price]`
                                                    : null
                                            "
                                            x-model="variant.discount_price"
                                            min="0"
                                            step="0.01"
                                            placeholder="Kosongkan jika tidak ada diskon"
                                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                        >

                                        <p class="mt-1 text-xs text-gray-500">
                                            Persentase diskon akan dihitung otomatis oleh sistem.
                                        </p>

                                    </div>

                                </div>


                                {{-- ================= Media ================= --}}

                                <div class="mt-4">

                                    <label class="mb-1.5 block text-sm font-medium text-gray-700">
                                        Foto Varian
                                    </label>

                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start">

                                        {{-- Preview --}}

                                        <div
                                            class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-gray-50"
                                        >

                                            <template x-if="getVariantMedia(variant)">

                                                <img
                                                    x-show="getVariantMedia(variant)?.media_type !== 'video'"
                                                    :src="getVariantMedia(variant)?.url"
                                                    alt=""
                                                    class="h-full w-full object-cover"
                                                >

                                            </template>

                                            <template x-if="!getVariantMedia(variant)">

                                                <div class="px-2 text-center text-xs text-gray-400">
                                                    Belum dipilih
                                                </div>

                                            </template>

                                        </div>


                                        {{-- Media Select --}}

                                        <div class="min-w-0 flex-1">

                                            <select
                                                x-bind:name="
                                                    isVariantFilled(variant)
                                                        ? `variants[${index}][media_id]`
                                                        : null
                                                "
                                                x-model="variant.media_id"
                                                x-effect="
                                                    availableMedia.length;
                                                    $nextTick(() => {
                                                        if (variant.media_id) {
                                                            $el.value = variant.media_id;
                                                        }
                                                    });
                                                "
                                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                            >

                                                <option value="">
                                                    Pilih foto dari gallery produk
                                                </option>

                                                <template
                                                    x-for="media in availableMedia.filter(item => item.media_type !== 'video')"
                                                    :key="media.id"
                                                >

                                                    <option
                                                        :value="media.id"
                                                        x-text="media.temporary
                                                            ? 'Foto baru'
                                                            : `Foto ${availableMedia.filter(item => item.media_type !== 'video').indexOf(media) + 1}`"
                                                    ></option>

                                                </template>

                                            </select>

                                            <p class="mt-1 text-xs text-gray-500">
                                                Foto harus dipilih dari media produk yang tersedia di gallery.
                                            </p>

                                        </div>

                                    </div>

                                </div>


                                {{-- ================= Stock ================= --}}

                                <div class="mt-4 max-w-xs">

                                    <label class="mb-1.5 block text-sm font-medium text-gray-700">
                                        Ready Stock
                                    </label>

                                    <input
                                        type="number"
                                        x-bind:name="
                                            isVariantFilled(variant)
                                                ? `variants[${index}][ready_stock]`
                                                : null
                                        "
                                        x-model="variant.ready_stock"
                                        min="0"
                                        step="1"
                                        placeholder="Contoh: 10"
                                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                    >

                                    <p class="mt-1 text-xs text-gray-500">
                                        Jumlah stok yang tersedia dan siap dijual.
                                    </p>

                                </div>

                            </div>

                        </template>

                    </div>

                </div>

            </div>

        </x-admin.card>


        {{-- =============================================== --}}
        {{-- Statistik --}}
        {{-- =============================================== --}}
        <div class="mb-4">
            <h3 class="text-sm font-semibold text-gray-800">
                Informasi Statistik
            </h3>

            <p class="mt-1 text-xs text-gray-500">
                Data awal statistik produk yang dapat disesuaikan oleh admin.
            </p>
        </div>

        <x-admin.card>

            <div class="space-y-6 p-5">

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

                    {{-- Average Rating --}}
                    <x-admin.form.group
                        label="Average Rating"
                    >
                        <x-admin.form.input
                            name="average_rating"
                            x-ref="average_rating"
                            type="number"
                            min="0"
                            max="5"
                            step="0.1"
                            :value="old(
                                'average_rating',
                                $product?->average_rating ?? 0
                            )"
                            placeholder="Contoh: 4.8"
                        />
                    </x-admin.form.group>

                    {{-- Total Terjual --}}
                    <x-admin.form.group
                        label="Total Terjual"
                    >
                        <x-admin.form.input
                            name="total_sold"
                            x-ref="total_sold"
                            type="number"
                            min="0"
                            step="1"
                            :value="old(
                                'total_sold',
                                $product?->total_sold ?? 0
                            )"
                            placeholder="Contoh: 125"
                        />
                    </x-admin.form.group>

                </div>

            </div>

        </x-admin.card>

    </x-admin.wizard.step>

    {{-- ===================================================== --}}
    {{-- NAVIGATION --}}
    {{-- ===================================================== --}}

    <x-admin.wizard.navigation
        :cancel-url="route('admin.products.index')"
        submit-text="Simpan Produk">

        <x-slot:left>

            <template x-if="step == 1">

                <x-admin.button
                    color="secondary"
                    href="{{ route('admin.products.index') }}">

                    Batal

                </x-admin.button>

            </template>

            <template x-if="step > 1">

                <x-admin.button
                    type="button"
                    color="secondary"
                    icon="arrow-left"
                    @click="prevStep()">

                    Sebelumnya

                </x-admin.button>

            </template>

        </x-slot:left>

        <x-slot:right>

            <template x-if="step < maxStep">

                <x-admin.button
                    type="button"
                    icon="arrow-right"
                    @click="nextStep()">

                    Selanjutnya

                </x-admin.button>

            </template>

            <template x-if="step == maxStep">

                <x-admin.button
                    type="submit"
                    icon="check">

                    Simpan Produk

                </x-admin.button>

            </template>

        </x-slot:right>

    </x-admin.wizard.navigation>

</div>