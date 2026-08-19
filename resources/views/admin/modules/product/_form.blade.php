@csrf

@isset($product)

    @method('PUT')

@endisset

@php
    $product ??= null;
    $specification = $product?->specification;
@endphp

<div
    x-data="productForm()"
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
        title="Harga & Stok"
        description="Atur harga, diskon, status ketersediaan, dan informasi stok produk."
    >

        {{-- =============================================== --}}
        {{-- Harga --}}
        {{-- =============================================== --}}

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
                            type="number"
                            min="0"
                            max="100"
                            step="1"
                            :value="old(
                                'discount_percentage',
                                $product?->discount_percentage
                            )"
                            placeholder="Contoh: 10"
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

                            <input
                                type="checkbox"
                                name="is_sale"
                                value="1"
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
        {{-- Stok --}}
        {{-- =============================================== --}}

        <x-admin.card style="margin-bottom: 20px;">

            <div class="space-y-6 p-5">

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

                    {{-- Ready Stock --}}
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

            </div>

        </x-admin.card>


        {{-- =============================================== --}}
        {{-- Statistik --}}
        {{-- =============================================== --}}

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
    {{-- STEP 4 --}}
    {{-- ===================================================== --}}

    <x-admin.wizard.step
        number="4"
        title="Media"
        description="Unggah gambar produk dan video tutorial">

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