@extends('admin.layouts.app')
@php
    $isEdit = isset($voucher) && $voucher;
@endphp
@section('title', $isEdit ? 'Edit Voucher' : 'Tambah Voucher')

@section('content')

{{-- ===================================================== --}}
{{-- PAGE HEADER --}}
{{-- ===================================================== --}}

<x-admin.page-header
    :title="$isEdit ? 'Edit Voucher' : 'Tambah Voucher'"
    :description="$isEdit
        ? 'Perbarui informasi dan ketentuan voucher Melody Furniture.'
        : 'Buat voucher atau promo baru untuk pelanggan Melody Furniture.'"
>


<x-slot:actions>

    <x-admin.button
        href="{{ route('admin.vouchers.index') }}"
        icon="arrow-left"
    >
        Kembali
    </x-admin.button>

</x-slot:actions>


</x-admin.page-header>

{{-- ===================================================== --}}
{{-- FORM --}}
{{-- ===================================================== --}}

<form
    method="POST"
    action="{{ $isEdit
        ? route('admin.vouchers.update', $voucher->id)
        : route('admin.vouchers.store') }}"
>
    @csrf

    @if($isEdit)
        @method('PUT')
    @endif


{{-- ================================================= --}}
{{-- VALIDATION ERROR --}}
{{-- ================================================= --}}

@if($errors->any())

    <x-admin.card>

        <div class="border-l-4 border-red-500 bg-red-50 p-4">

            <div class="flex gap-3">

                <div class="flex-1">

                    <h3 class="text-sm font-semibold text-red-800">
                        Terdapat kesalahan pada form
                    </h3>

                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-700">

                        @foreach($errors->all() as $error)

                            <li>
                                {{ $error }}
                            </li>

                        @endforeach

                    </ul>

                </div>

            </div>

        </div>

    </x-admin.card>

@endif


{{-- ================================================= --}}
{{-- INFORMASI VOUCHER --}}
{{-- ================================================= --}}

<x-admin.card>

    <x-admin.card-header
        title="Informasi Voucher"
        description="Tentukan kode dan status voucher."
    />

    <x-admin.card-body>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

            {{-- CODE --}}

            <div>

                <label
                    for="code"
                    class="mb-2 block text-sm font-medium text-gray-700"
                >
                    Kode Voucher
                    <span class="text-red-500">*</span>
                </label>

                <input
                    type="text"
                    id="code"
                    name="code"
                    value="{{ old('code', $voucher?->code) }}"
                    maxlength="50"
                    required
                    autofocus
                    placeholder="Contoh: MELODY10"
                    class="
                        block
                        w-full
                        rounded-lg
                        border
                        border-gray-300
                        bg-white
                        px-4
                        py-2.5
                        text-sm
                        uppercase
                        text-gray-900
                        placeholder-gray-400
                        focus:border-blue-500
                        focus:ring-blue-500
                    "
                >

                <p class="mt-1.5 text-xs text-gray-500">
                    Maksimal 50 karakter dan harus unik.
                </p>

                @error('code')

                    <p class="mt-1.5 text-xs text-red-600">
                        {{ $message }}
                    </p>

                @enderror

            </div>

            {{-- ACTIVE --}}

            <div>

                <label
                    for="is_active"
                    class="mb-2 block text-sm font-medium text-gray-700"
                >
                    Status Voucher
                </label>

                <select
                    id="is_active"
                    name="is_active"
                    class="
                        block
                        w-full
                        rounded-lg
                        border
                        border-gray-300
                        bg-white
                        px-4
                        py-2.5
                        text-sm
                        text-gray-900
                        focus:border-blue-500
                        focus:ring-blue-500
                    "
                >

                    <option
                        value="1"
                        @selected(
                            old(
                                'is_active',
                                $voucher?->is_active ?? true
                            ) == '1'
                        )
                    >
                        Aktif
                    </option>

                    <option
                        value="0"
                        @selected(
                            old(
                                'is_active',
                                $voucher?->is_active ?? true
                            ) == '0'
                        )
                    >
                        Tidak Aktif
                    </option>

                </select>

                <p class="mt-1.5 text-xs text-gray-500">
                    Voucher aktif dapat digunakan selama memenuhi ketentuan.
                </p>

                @error('is_active')

                    <p class="mt-1.5 text-xs text-red-600">
                        {{ $message }}
                    </p>

                @enderror

            </div>

        </div>

    </x-admin.card-body>

</x-admin.card>


{{-- ================================================= --}}
{{-- KONFIGURASI DISKON --}}
{{-- ================================================= --}}

<x-admin.card>

    <x-admin.card-header
        title="Konfigurasi Diskon"
        description="Atur jenis dan nilai diskon voucher."
    />

    <x-admin.card-body>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

            {{-- DISCOUNT TYPE --}}

            <div>

                <label
                    for="discount_type"
                    class="mb-2 block text-sm font-medium text-gray-700"
                >
                    Jenis Diskon
                    <span class="text-red-500">*</span>
                </label>

                <select
                    id="discount_type"
                    name="discount_type"
                    required
                    class="
                        block
                        w-full
                        rounded-lg
                        border
                        border-gray-300
                        bg-white
                        px-4
                        py-2.5
                        text-sm
                        text-gray-900
                        focus:border-blue-500
                        focus:ring-blue-500
                    "
                >

                    <option value="">
                        Pilih jenis diskon
                    </option>

                    <option
                        value="percentage"
                        @selected(
                            old('discount_type', $voucher?->discount_type) === 'percentage'
                        )
                    >
                        Persentase (%)
                    </option>

                    <option
                        value="fixed"
                        @selected(
                            old('discount_type', $voucher?->discount_type) === 'fixed'
                        )
                    >
                        Nominal Tetap (Rp)
                    </option>

                </select>

                <p class="mt-1.5 text-xs text-gray-500">
                    Pilih apakah diskon menggunakan persentase atau nominal tetap.
                </p>

                @error('discount_type')

                    <p class="mt-1.5 text-xs text-red-600">
                        {{ $message }}
                    </p>

                @enderror

            </div>


            {{-- DISCOUNT VALUE --}}

            <div>

                <label
                    for="discount_value"
                    class="mb-2 block text-sm font-medium text-gray-700"
                >
                    Nilai Diskon
                    <span class="text-red-500">*</span>
                </label>

                <input
                    type="number"
                    id="discount_value"
                    name="discount_value"
                    value="{{ old('discount_value', $voucher?->discount_value) }}"
                    min="0"
                    step="0.01"
                    required
                    placeholder="Contoh: 10"
                    class="
                        block
                        w-full
                        rounded-lg
                        border
                        border-gray-300
                        bg-white
                        px-4
                        py-2.5
                        text-sm
                        text-gray-900
                        placeholder-gray-400
                        focus:border-blue-500
                        focus:ring-blue-500
                    "
                >

                <p
                    id="discount-value-help"
                    class="mt-1.5 text-xs text-gray-500"
                >
                    Untuk persentase masukkan angka seperti 10 untuk 10%.
                </p>

                @error('discount_value')

                    <p class="mt-1.5 text-xs text-red-600">
                        {{ $message }}
                    </p>

                @enderror

            </div>


            {{-- MINIMUM ORDER --}}

            <div>

                <label
                    for="min_order_amount"
                    class="mb-2 block text-sm font-medium text-gray-700"
                >
                    Minimal Belanja
                    <span class="text-red-500">*</span>
                </label>

                <div class="relative">

                    <span
                        class="
                            pointer-events-none
                            absolute
                            inset-y-0
                            left-0
                            flex
                            items-center
                            pl-4
                            text-sm
                            text-gray-500
                        "
                    >
                        Rp
                    </span>

                    <input
                        type="number"
                        id="min_order_amount"
                        name="min_order_amount"
                        value="{{ old('min_order_amount', $voucher?->min_order_amount ?? '0') }}"
                        min="0"
                        step="0.01"
                        required
                        class="
                            block
                            w-full
                            rounded-lg
                            border
                            border-gray-300
                            bg-white
                            py-2.5
                            pl-11
                            pr-4
                            text-sm
                            text-gray-900
                            focus:border-blue-500
                            focus:ring-blue-500
                        "
                    >

                </div>

                <p class="mt-1.5 text-xs text-gray-500">
                    Isi 0 jika tidak ada minimal pembelian.
                </p>

                @error('min_order_amount')

                    <p class="mt-1.5 text-xs text-red-600">
                        {{ $message }}
                    </p>

                @enderror

            </div>


            {{-- MAX DISCOUNT --}}

            <div>

                <label
                    for="max_discount_amount"
                    class="mb-2 block text-sm font-medium text-gray-700"
                >
                    Maksimal Potongan
                </label>

                <div class="relative">

                    <span
                        class="
                            pointer-events-none
                            absolute
                            inset-y-0
                            left-0
                            flex
                            items-center
                            pl-4
                            text-sm
                            text-gray-500
                        "
                    >
                        Rp
                    </span>

                    <input
                        type="number"
                        id="max_discount_amount"
                        name="max_discount_amount"
                        value="{{ old('max_discount_amount', $voucher?->max_discount_amount) }}"
                        min="0"
                        step="0.01"
                        placeholder="Tidak dibatasi"
                        class="
                            block
                            w-full
                            rounded-lg
                            border
                            border-gray-300
                            bg-white
                            py-2.5
                            pl-11
                            pr-4
                            text-sm
                            text-gray-900
                            placeholder-gray-400
                            focus:border-blue-500
                            focus:ring-blue-500
                        "
                    >

                </div>

                <p class="mt-1.5 text-xs text-gray-500">
                    Opsional. Umumnya digunakan untuk voucher persentase.
                </p>

                @error('max_discount_amount')

                    <p class="mt-1.5 text-xs text-red-600">
                        {{ $message }}
                    </p>

                @enderror

            </div>

        </div>

    </x-admin.card-body>

</x-admin.card>


{{-- ================================================= --}}
{{-- PERIODE BERLAKU --}}
{{-- ================================================= --}}

<x-admin.card>

    <x-admin.card-header
        title="Periode Berlaku"
        description="Tentukan kapan voucher mulai dan berakhir."
    />

    <x-admin.card-body>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

            {{-- START DATE --}}

            <div>

                <label
                    for="start_date"
                    class="mb-2 block text-sm font-medium text-gray-700"
                >
                    Mulai Berlaku
                </label>

                <input
                    type="datetime-local"
                    id="start_date"
                    name="start_date"
                    value="{{ old(
                        'start_date',
                        $voucher?->start_date
                            ? \Carbon\Carbon::parse($voucher->start_date)->format('Y-m-d\TH:i')
                            : ''
                    ) }}"
                    class="
                        block
                        w-full
                        rounded-lg
                        border
                        border-gray-300
                        bg-white
                        px-4
                        py-2.5
                        text-sm
                        text-gray-900
                        focus:border-blue-500
                        focus:ring-blue-500
                    "
                >

                <p class="mt-1.5 text-xs text-gray-500">
                    Opsional. Kosongkan jika voucher langsung dapat digunakan.
                </p>

                @error('start_date')

                    <p class="mt-1.5 text-xs text-red-600">
                        {{ $message }}
                    </p>

                @enderror

            </div>


            {{-- EXPIRY DATE --}}

            <div>

                <label
                    for="expiry_date"
                    class="mb-2 block text-sm font-medium text-gray-700"
                >
                    Berakhir Pada
                    <span class="text-red-500">*</span>
                </label>

                <input
                    type="datetime-local"
                    id="expiry_date"
                    name="expiry_date"
                    value="{{ old(
                        'expiry_date',
                        $voucher?->expiry_date
                            ? \Carbon\Carbon::parse($voucher->expiry_date)->format('Y-m-d\TH:i')
                            : ''
                    ) }}"
                    required
                    class="
                        block
                        w-full
                        rounded-lg
                        border
                        border-gray-300
                        bg-white
                        px-4
                        py-2.5
                        text-sm
                        text-gray-900
                        focus:border-blue-500
                        focus:ring-blue-500
                    "
                >

                <p class="mt-1.5 text-xs text-gray-500">
                    Voucher tidak dapat digunakan setelah waktu ini.
                </p>

                @error('expiry_date')

                    <p class="mt-1.5 text-xs text-red-600">
                        {{ $message }}
                    </p>

                @enderror

            </div>

        </div>

    </x-admin.card-body>

</x-admin.card>


{{-- ================================================= --}}
{{-- PENGGUNAAN --}}
{{-- ================================================= --}}

<x-admin.card>

    <x-admin.card-header
        title="Batas Penggunaan"
        description="Atur jumlah maksimal penggunaan voucher."
    />

    <x-admin.card-body>

        <div class="max-w-xl">

            <label
                for="usage_limit"
                class="mb-2 block text-sm font-medium text-gray-700"
            >
                Batas Penggunaan
            </label>

            <input
                type="number"
                id="usage_limit"
                name="usage_limit"
                value="{{ old('usage_limit', $voucher?->usage_limit) }}"
                min="1"
                step="1"
                placeholder="Tidak terbatas"
                class="
                    block
                    w-full
                    rounded-lg
                    border
                    border-gray-300
                    bg-white
                    px-4
                    py-2.5
                    text-sm
                    text-gray-900
                    placeholder-gray-400
                    focus:border-blue-500
                    focus:ring-blue-500
                "
            >

            <p class="mt-1.5 text-xs text-gray-500">
                Opsional. Kosongkan jika voucher tidak memiliki batas penggunaan.
            </p>

            @error('usage_limit')

                <p class="mt-1.5 text-xs text-red-600">
                    {{ $message }}
                </p>

            @enderror

        </div>

    </x-admin.card-body>

</x-admin.card>


{{-- ================================================= --}}
{{-- FORM ACTION --}}
{{-- ================================================= --}}

<div class="flex items-center justify-end gap-3">

    <x-admin.button
        href="{{ route('admin.vouchers.index') }}"
    >
        Batal
    </x-admin.button>


    <x-admin.button
        type="submit"
        icon="check"
    >
        {{ $isEdit ? 'Perbarui Voucher' : 'Simpan Voucher' }}
    </x-admin.button>

</div>


</form>

{{-- ===================================================== --}}
{{-- DISCOUNT TYPE HELPER --}}
{{-- ===================================================== --}}

@push('scripts')

<script>

document.addEventListener('DOMContentLoaded', function () {

    const discountType = document.getElementById('discount_type');
    const discountValue = document.getElementById('discount_value');
    const discountHelp = document.getElementById('discount-value-help');
    const maxDiscount = document.getElementById('max_discount_amount');

    function updateDiscountUI() {

        if (!discountType) {
            return;
        }

        if (discountType.value === 'percentage') {

            discountValue.placeholder = 'Contoh: 10';
            discountValue.max = '100';

            discountHelp.textContent =
                'Masukkan angka 0–100. Contoh: 10 berarti diskon 10%.';

            maxDiscount.closest('div').closest('div').style.display = '';

        } else if (discountType.value === 'fixed') {

            discountValue.placeholder = 'Contoh: 50000';
            discountValue.removeAttribute('max');

            discountHelp.textContent =
                'Masukkan nominal potongan dalam Rupiah.';

            maxDiscount.closest('div').closest('div').style.display = 'none';

        } else {

            discountValue.placeholder = 'Pilih jenis diskon terlebih dahulu.';
            discountHelp.textContent =
                'Pilih jenis diskon untuk menentukan format nilai diskon.';

        }

    }

    discountType.addEventListener('change', updateDiscountUI);

    updateDiscountUI();

});

</script>

@endpush

@endsection
