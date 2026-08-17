@extends('admin.layouts.app')

@section('title', 'Detail Voucher')

@section('content')

{{-- ===================================================== --}}
{{-- PAGE HEADER --}}
{{-- ===================================================== --}}

<x-admin.page-header
    title="Detail Voucher"
    description="Lihat informasi lengkap dan status voucher Melody Furniture."
>

    <x-slot:actions>

        <x-admin.button
            href="{{ route('admin.vouchers.index') }}"
            icon="arrow-left"
        >
            Kembali
        </x-admin.button>

        <x-admin.button
            href="{{ route('admin.vouchers.edit', $voucher->id) }}"
            icon="pencil"
        >
            Edit Voucher
        </x-admin.button>

    </x-slot:actions>

</x-admin.page-header>


{{-- ===================================================== --}}
{{-- FLASH MESSAGE --}}
{{-- ===================================================== --}}

@if(session('success'))

    <div class="mb-6">

        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3">

            <p class="text-sm font-medium text-green-800">
                {{ session('success') }}
            </p>

        </div>

    </div>

@endif


{{-- ===================================================== --}}
{{-- VOUCHER HEADER --}}
{{-- ===================================================== --}}

<x-admin.card class="mb-6">

    <x-admin.card-body>

        <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">

            {{-- CODE --}}

            <div>

                <div class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-500">
                    Kode Voucher
                </div>

                <div class="flex flex-wrap items-center gap-3">

                    <h2 class="text-2xl font-bold tracking-wide text-gray-900">
                        {{ $voucher->code }}
                    </h2>

                    @if($voucher->is_expired)

                        <x-admin.badge variant="danger">
                            Expired
                        </x-admin.badge>

                    @elseif($voucher->is_usage_limit_reached)

                        <x-admin.badge variant="warning">
                            Limit Habis
                        </x-admin.badge>

                    @elseif(!$voucher->is_active)

                        <x-admin.badge>
                            Tidak Aktif
                        </x-admin.badge>

                    @elseif(!$voucher->is_started)

                        <x-admin.badge variant="warning">
                            Belum Aktif
                        </x-admin.badge>

                    @else

                        <x-admin.badge variant="success">
                            Aktif
                        </x-admin.badge>

                    @endif

                </div>

                <p class="mt-2 text-sm text-gray-500">

                    @if($voucher->discount_type === 'percentage')

                        Voucher diskon persentase

                    @else

                        Voucher diskon nominal tetap

                    @endif

                </p>

            </div>


            {{-- QUICK ACTION --}}

            <div class="flex flex-wrap gap-2">

                {{-- Toggle Active --}}

                <form
                    method="POST"
                    action="{{ route('admin.vouchers.toggle-active', $voucher->id) }}"
                >

                    @csrf
                    @method('PATCH')

                    <x-admin.button
                        type="submit"
                        icon="{{ $voucher->is_active ? 'pause' : 'play' }}"
                    >
                        {{ $voucher->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                    </x-admin.button>

                </form>


                {{-- Delete --}}

                <form
                    method="POST"
                    action="{{ route('admin.vouchers.destroy', $voucher->id) }}"
                    onsubmit="return confirm('Yakin ingin menghapus voucher {{ $voucher->code }}?')"
                >

                    @csrf
                    @method('DELETE')

                    <x-admin.button
                        type="submit"
                        icon="trash"
                    >
                        Hapus
                    </x-admin.button>

                </form>

            </div>

        </div>

    </x-admin.card-body>

</x-admin.card>


{{-- ===================================================== --}}
{{-- STATISTICS --}}
{{-- ===================================================== --}}

<x-admin.stats.grid class="mb-6">

    {{-- DISCOUNT --}}

    <x-admin.stats.card
        title="Nilai Diskon"
        value="{{
            $voucher->discount_type === 'percentage'
                ? number_format($voucher->discount_value, 0, ',', '.') . '%'
                : 'Rp ' . number_format($voucher->discount_value, 0, ',', '.')
        }}"
        icon="ticket"
        color="blue"
    />


    {{-- MINIMUM ORDER --}}

    <x-admin.stats.card
        title="Minimal Belanja"
        value="{{
            $voucher->min_order_amount > 0
                ? 'Rp ' . number_format($voucher->min_order_amount, 0, ',', '.')
                : 'Tidak Ada'
        }}"
        icon="shopping-cart"
        color="green"
    />


    {{-- USED COUNT --}}

    <x-admin.stats.card
        title="Sudah Digunakan"
        value="{{ number_format($voucher->used_count, 0, ',', '.') }}"
        icon="chart-bar"
        color="blue"
    />


    {{-- USAGE LIMIT --}}

    <x-admin.stats.card
        title="Batas Penggunaan"
        value="{{
            $voucher->usage_limit !== null
                ? number_format($voucher->usage_limit, 0, ',', '.')
                : '∞'
        }}"
        icon="users"
        color="gray"
    />

</x-admin.stats.grid>


{{-- ===================================================== --}}
{{-- MAIN CONTENT --}}
{{-- ===================================================== --}}

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">


    {{-- ================================================= --}}
    {{-- LEFT / MAIN --}}
    {{-- ================================================= --}}

    <div class="space-y-6 lg:col-span-2">


        {{-- ============================================= --}}
        {{-- DISCOUNT CONFIGURATION --}}
        {{-- ============================================= --}}

        <x-admin.card>

            <x-admin.card-header
                title="Konfigurasi Diskon"
                description="Informasi aturan diskon yang diterapkan oleh voucher."
            />

            <x-admin.card-body>

                <dl class="divide-y divide-gray-100">


                    {{-- DISCOUNT TYPE --}}

                    <div class="grid grid-cols-1 gap-2 py-4 sm:grid-cols-3">

                        <dt class="text-sm font-medium text-gray-500">
                            Jenis Diskon
                        </dt>

                        <dd class="text-sm text-gray-900 sm:col-span-2">

                            @if($voucher->discount_type === 'percentage')

                                Persentase (%)

                            @else

                                Nominal Tetap (Rp)

                            @endif

                        </dd>

                    </div>


                    {{-- DISCOUNT VALUE --}}

                    <div class="grid grid-cols-1 gap-2 py-4 sm:grid-cols-3">

                        <dt class="text-sm font-medium text-gray-500">
                            Nilai Diskon
                        </dt>

                        <dd class="font-semibold text-gray-900 sm:col-span-2">

                            @if($voucher->discount_type === 'percentage')

                                {{ number_format($voucher->discount_value, 0, ',', '.') }}%

                            @else

                                Rp {{ number_format($voucher->discount_value, 0, ',', '.') }}

                            @endif

                        </dd>

                    </div>


                    {{-- MINIMUM ORDER --}}

                    <div class="grid grid-cols-1 gap-2 py-4 sm:grid-cols-3">

                        <dt class="text-sm font-medium text-gray-500">
                            Minimal Belanja
                        </dt>

                        <dd class="text-sm text-gray-900 sm:col-span-2">

                            @if($voucher->min_order_amount > 0)

                                Rp {{ number_format($voucher->min_order_amount, 0, ',', '.') }}

                            @else

                                Tidak ada minimum pembelian

                            @endif

                        </dd>

                    </div>


                    {{-- MAX DISCOUNT --}}

                    <div class="grid grid-cols-1 gap-2 py-4 sm:grid-cols-3">

                        <dt class="text-sm font-medium text-gray-500">
                            Maksimal Potongan
                        </dt>

                        <dd class="text-sm text-gray-900 sm:col-span-2">

                            @if($voucher->max_discount_amount !== null)

                                Rp {{ number_format($voucher->max_discount_amount, 0, ',', '.') }}

                            @else

                                Tidak dibatasi

                            @endif

                        </dd>

                    </div>

                </dl>

            </x-admin.card-body>

        </x-admin.card>


        {{-- ============================================= --}}
        {{-- VALIDITY PERIOD --}}
        {{-- ============================================= --}}

        <x-admin.card>

            <x-admin.card-header
                title="Periode Berlaku"
                description="Informasi periode penggunaan voucher."
            />

            <x-admin.card-body>

                <dl class="divide-y divide-gray-100">


                    {{-- START DATE --}}

                    <div class="grid grid-cols-1 gap-2 py-4 sm:grid-cols-3">

                        <dt class="text-sm font-medium text-gray-500">
                            Mulai Berlaku
                        </dt>

                        <dd class="text-sm text-gray-900 sm:col-span-2">

                            @if($voucher->start_date)

                                {{ \Carbon\Carbon::parse($voucher->start_date)->format('d M Y, H:i') }}

                            @else

                                <span class="text-gray-500">
                                    Langsung aktif
                                </span>

                            @endif

                        </dd>

                    </div>


                    {{-- EXPIRY DATE --}}

                    <div class="grid grid-cols-1 gap-2 py-4 sm:grid-cols-3">

                        <dt class="text-sm font-medium text-gray-500">
                            Berakhir Pada
                        </dt>

                        <dd class="text-sm text-gray-900 sm:col-span-2">

                            {{ \Carbon\Carbon::parse($voucher->expiry_date)->format('d M Y, H:i') }}

                        </dd>

                    </div>


                    {{-- CREATED --}}

                    <div class="grid grid-cols-1 gap-2 py-4 sm:grid-cols-3">

                        <dt class="text-sm font-medium text-gray-500">
                            Dibuat Pada
                        </dt>

                        <dd class="text-sm text-gray-900 sm:col-span-2">

                            {{ $voucher->created_at?->format('d M Y, H:i') }}

                        </dd>

                    </div>


                    {{-- UPDATED --}}

                    <div class="grid grid-cols-1 gap-2 py-4 sm:grid-cols-3">

                        <dt class="text-sm font-medium text-gray-500">
                            Terakhir Diperbarui
                        </dt>

                        <dd class="text-sm text-gray-900 sm:col-span-2">

                            {{ $voucher->updated_at?->format('d M Y, H:i') }}

                        </dd>

                    </div>

                </dl>

            </x-admin.card-body>

        </x-admin.card>

    </div>


    {{-- ================================================= --}}
    {{-- RIGHT / SIDEBAR --}}
    {{-- ================================================= --}}

    <div class="space-y-6">


        {{-- ============================================= --}}
        {{-- USAGE --}}
        {{-- ============================================= --}}

        <x-admin.card>

            <x-admin.card-header
                title="Penggunaan Voucher"
                description="Ringkasan penggunaan voucher."
            />

            <x-admin.card-body>

                @if($voucher->usage_limit !== null)

                    @php

                        $usagePercentage = $voucher->usage_limit > 0
                            ? min(
                                ($voucher->used_count / $voucher->usage_limit) * 100,
                                100
                            )
                            : 0;

                    @endphp


                    <div class="flex items-end justify-between">

                        <div>

                            <p class="text-2xl font-bold text-gray-900">
                                {{ number_format($voucher->used_count, 0, ',', '.') }}
                            </p>

                            <p class="text-xs text-gray-500">
                                dari
                                {{ number_format($voucher->usage_limit, 0, ',', '.') }}
                                penggunaan
                            </p>

                        </div>

                        <span class="text-sm font-semibold text-gray-700">
                            {{ number_format($usagePercentage, 0) }}%
                        </span>

                    </div>


                    <div class="mt-4 h-2 w-full overflow-hidden rounded-full bg-gray-200">

                        <div
                            class="
                                h-full
                                rounded-full
                                {{ $usagePercentage >= 100
                                    ? 'bg-red-500'
                                    : ($usagePercentage >= 80
                                        ? 'bg-yellow-500'
                                        : 'bg-blue-500')
                                }}
                            "
                            style="width: {{ $usagePercentage }}%"
                        ></div>

                    </div>


                    @if($voucher->is_usage_limit_reached)

                        <div class="mt-3 rounded-lg bg-red-50 px-3 py-2">

                            <p class="text-xs font-medium text-red-700">
                                Batas penggunaan voucher sudah tercapai.
                            </p>

                        </div>

                    @elseif($usagePercentage >= 80)

                        <div class="mt-3 rounded-lg bg-yellow-50 px-3 py-2">

                            <p class="text-xs font-medium text-yellow-700">
                                Penggunaan voucher sudah mencapai lebih dari 80%.
                            </p>

                        </div>

                    @endif

                @else

                    <div class="text-center">

                        <div class="text-3xl font-bold text-gray-900">
                            {{ number_format($voucher->used_count, 0, ',', '.') }}
                        </div>

                        <p class="mt-1 text-sm text-gray-500">
                            penggunaan
                        </p>

                        <div class="mt-4 rounded-lg bg-blue-50 px-4 py-3">

                            <p class="text-sm font-medium text-blue-700">
                                Voucher tidak memiliki batas penggunaan.
                            </p>

                        </div>

                    </div>

                @endif

            </x-admin.card-body>

        </x-admin.card>


        {{-- ============================================= --}}
        {{-- STATUS --}}
        {{-- ============================================= --}}

        <x-admin.card>

            <x-admin.card-header
                title="Status Voucher"
                description="Status berdasarkan konfigurasi dan periode voucher."
            />

            <x-admin.card-body>

                <div class="space-y-4">


                    {{-- ACTIVE STATUS --}}

                    <div class="flex items-center justify-between">

                        <span class="text-sm text-gray-600">
                            Status
                        </span>

                        @if($voucher->is_active)

                            <x-admin.badge variant="success">
                                Aktif
                            </x-admin.badge>

                        @else

                            <x-admin.badge>
                                Tidak Aktif
                            </x-admin.badge>

                        @endif

                    </div>


                    {{-- STARTED --}}

                    <div class="flex items-center justify-between">

                        <span class="text-sm text-gray-600">
                            Periode Mulai
                        </span>

                        @if($voucher->is_started)

                            <x-admin.badge variant="success">
                                Sudah Mulai
                            </x-admin.badge>

                        @else

                            <x-admin.badge variant="warning">
                                Belum Mulai
                            </x-admin.badge>

                        @endif

                    </div>


                    {{-- EXPIRED --}}

                    <div class="flex items-center justify-between">

                        <span class="text-sm text-gray-600">
                            Masa Berlaku
                        </span>

                        @if($voucher->is_expired)

                            <x-admin.badge variant="danger">
                                Expired
                            </x-admin.badge>

                        @else

                            <x-admin.badge variant="success">
                                Belum Expired
                            </x-admin.badge>

                        @endif

                    </div>


                    {{-- USAGE LIMIT --}}

                    <div class="flex items-center justify-between">

                        <span class="text-sm text-gray-600">
                            Batas Penggunaan
                        </span>

                        @if($voucher->is_usage_limit_reached)

                            <x-admin.badge variant="danger">
                                Limit Habis
                            </x-admin.badge>

                        @else

                            <x-admin.badge variant="success">
                                Tersedia
                            </x-admin.badge>

                        @endif

                    </div>

                </div>

            </x-admin.card-body>

        </x-admin.card>


        {{-- ============================================= --}}
        {{-- ACTION --}}
        {{-- ============================================= --}}

        <x-admin.card>

            <x-admin.card-header
                title="Aksi"
                description="Kelola voucher ini."
            />

            <x-admin.card-body>

                <div class="space-y-3">


                    <x-admin.button
                        href="{{ route('admin.vouchers.edit', $voucher->id) }}"
                        icon="pencil"
                        class="w-full"
                    >
                        Edit Voucher
                    </x-admin.button>


                    <form
                        method="POST"
                        action="{{ route('admin.vouchers.toggle-active', $voucher->id) }}"
                    >

                        @csrf
                        @method('PATCH')

                        <x-admin.button
                            type="submit"
                            icon="{{ $voucher->is_active ? 'pause' : 'play' }}"
                            class="w-full"
                        >
                            {{ $voucher->is_active
                                ? 'Nonaktifkan Voucher'
                                : 'Aktifkan Voucher'
                            }}
                        </x-admin.button>

                    </form>


                    <form
                        method="POST"
                        action="{{ route('admin.vouchers.destroy', $voucher->id) }}"
                        onsubmit="return confirm('Yakin ingin menghapus voucher {{ $voucher->code }}?')"
                    >

                        @csrf
                        @method('DELETE')

                        <x-admin.button
                            type="submit"
                            icon="trash"
                            class="w-full"
                        >
                            Hapus Voucher
                        </x-admin.button>

                    </form>

                </div>

            </x-admin.card-body>

        </x-admin.card>

    </div>

</div>
{{-- ===================================================== --}}
{{-- USAGE HISTORY --}}
{{-- ===================================================== --}}

<x-admin.card class="mt-6">

    <div class="flex flex-col gap-4 border-b border-gray-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">

        <div>

            <h3 class="text-base font-semibold text-gray-900">
                Riwayat Penggunaan Voucher
            </h3>

            <p class="mt-1 text-sm text-gray-500">
                Daftar pesanan yang menggunakan voucher ini dan telah berhasil dibayar.
            </p>

        </div>

        <x-admin.button
            href="{{ route('admin.vouchers.export-usage', $voucher) }}"
            icon="arrow-down-tray"
        >
            Export Excel
        </x-admin.button>

    </div>

    {{-- ================================================= --}}
    {{-- TABLE --}}
    {{-- ================================================= --}}

    <x-admin.table.table>

        <x-admin.table.thead>

            <tr>

                <x-admin.table.th>
                    Order
                </x-admin.table.th>

                <x-admin.table.th>
                    Pelanggan
                </x-admin.table.th>

                <x-admin.table.th>
                    Total Belanja
                </x-admin.table.th>

                <x-admin.table.th>
                    Diskon Voucher
                </x-admin.table.th>

                <x-admin.table.th>
                    Total Pembayaran
                </x-admin.table.th>

                <x-admin.table.th>
                    Status
                </x-admin.table.th>

                <x-admin.table.th>
                    Digunakan Pada
                </x-admin.table.th>

            </tr>

        </x-admin.table.thead>


        <x-admin.table.tbody>

            @if($usageOrders->isEmpty())

                <tr>

                    <td
                        colspan="7"
                        class="px-6 py-16 text-center"
                    >

                        <x-admin.empty-state
                            title="Belum ada penggunaan"
                            description="Belum ada pesanan berhasil dibayar yang menggunakan voucher ini."
                        />

                    </td>

                </tr>

            @else

                @foreach($usageOrders as $order)

                    <x-admin.table.tr>

                        {{-- ORDER --}}

                        <x-admin.table.td>

                            <a
                                href="{{ route('admin.orders.show', $order->id) }}"
                                class="
                                    font-semibold
                                    text-gray-900
                                    transition
                                    hover:text-blue-600
                                "
                            >
                                {{ $order->order_number }}
                            </a>

                            <div class="mt-1 text-xs text-gray-500">

                                {{ $order->midtrans_order_id }}

                            </div>

                        </x-admin.table.td>


                        {{-- CUSTOMER --}}

                        <x-admin.table.td>

                            <div class="font-medium text-gray-900">

                                {{ $order->customer_name }}

                            </div>

                            @if($order->customer_email)

                                <div class="mt-1 text-xs text-gray-500">

                                    {{ $order->customer_email }}

                                </div>

                            @endif

                        </x-admin.table.td>


                        {{-- TOTAL PRODUCT --}}

                        <x-admin.table.td>

                            Rp
                            {{ number_format(
                                $order->total_product_price,
                                0,
                                ',',
                                '.'
                            ) }}

                        </x-admin.table.td>


                        {{-- VOUCHER DISCOUNT --}}

                        <x-admin.table.td>

                            <span class="font-medium text-green-600">

                                - Rp
                                {{ number_format(
                                    $order->voucher_discount_amount,
                                    0,
                                    ',',
                                    '.'
                                ) }}

                            </span>

                        </x-admin.table.td>


                        {{-- TOTAL PAYMENT --}}

                        <x-admin.table.td>

                            <span class="font-semibold text-gray-900">

                                Rp
                                {{ number_format(
                                    $order->total_payment,
                                    0,
                                    ',',
                                    '.'
                                ) }}

                            </span>

                        </x-admin.table.td>


                        {{-- STATUS --}}

                        <x-admin.table.td>

                            @switch($order->status)

                                @case('pending')

                                    <x-admin.badge variant="warning">
                                        Pending
                                    </x-admin.badge>

                                    @break

                                @case('paid')

                                    <x-admin.badge variant="success">
                                        Paid
                                    </x-admin.badge>

                                    @break

                                @case('processing')

                                    <x-admin.badge variant="info">
                                        Processing
                                    </x-admin.badge>

                                    @break

                                @case('picked_up')

                                    <x-admin.badge variant="info">
                                        Picked Up
                                    </x-admin.badge>

                                    @break

                                @case('shipped')

                                    <x-admin.badge variant="info">
                                        Shipped
                                    </x-admin.badge>

                                    @break

                                @case('completed')

                                    <x-admin.badge variant="success">
                                        Completed
                                    </x-admin.badge>

                                    @break

                                @case('cancelled')

                                    <x-admin.badge variant="danger">
                                        Cancelled
                                    </x-admin.badge>

                                    @break

                                @default

                                    <x-admin.badge>
                                        {{ ucfirst($order->status) }}
                                    </x-admin.badge>

                            @endswitch

                        </x-admin.table.td>


                        {{-- DATE --}}

                        <x-admin.table.td>

                            <div class="text-sm text-gray-900">

                                {{ $order->created_at?->format('d M Y') }}

                            </div>

                            <div class="mt-1 text-xs text-gray-500">

                                {{ $order->created_at?->format('H:i') }}

                            </div>

                        </x-admin.table.td>

                    </x-admin.table.tr>

                @endforeach

            @endif

        </x-admin.table.tbody>

    </x-admin.table.table>


    {{-- ================================================= --}}
    {{-- PAGINATION --}}
    {{-- ================================================= --}}

    @if($usageOrders->hasPages())

        <div class="border-t border-gray-200 px-6 py-4">

            <x-admin.pagination.pagination
                :paginator="$usageOrders"
            />

        </div>

    @endif

</x-admin.card>
@endsection