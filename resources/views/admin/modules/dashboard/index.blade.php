@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')

    {{-- ============================================================= --}}
    {{-- Dashboard --}}
    {{-- ============================================================= --}}

    <div class="space-y-6">

        {{-- ========================================================= --}}
        {{-- Header --}}
        {{-- ========================================================= --}}

        <div>
            <h2 class="text-2xl font-bold text-gray-900">
                Dashboard
            </h2>

            <p class="mt-1 text-sm text-gray-500">
                Ringkasan aktivitas dan performa penjualan Melody Furniture.
            </p>
        </div>


        {{-- ========================================================= --}}
        {{-- Filter Periode --}}
        {{-- ========================================================= --}}

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">

            <form
                method="GET"
                action="{{ route('admin.dashboard') }}"
                class="grid grid-cols-1 gap-4 md:grid-cols-3 md:items-end"
            >

                {{-- Start Date --}}
                <div>
                    <label
                        for="start_date"
                        class="mb-2 block text-sm font-medium text-gray-700"
                    >
                        Dari Tanggal
                    </label>

                    <input
                        type="date"
                        id="start_date"
                        name="start_date"
                        value="{{ $startDate }}"
                        class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    >
                </div>


                {{-- End Date --}}
                <div>
                    <label
                        for="end_date"
                        class="mb-2 block text-sm font-medium text-gray-700"
                    >
                        Sampai Tanggal
                    </label>

                    <input
                        type="date"
                        id="end_date"
                        name="end_date"
                        value="{{ $endDate }}"
                        class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    >
                </div>


                {{-- Action --}}
                <div class="flex gap-2">

                    <button
                        type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                    >

                        <x-heroicon-o-funnel class="h-5 w-5"/>

                        Terapkan Filter

                    </button>


                    {{-- Reset --}}
                    @if($startDate || $endDate)

                        <a
                            href="{{ route('admin.dashboard') }}"
                            class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                        >

                            <x-heroicon-o-arrow-path class="h-5 w-5"/>

                            Reset

                        </a>

                    @endif

                </div>

            </form>

        </div>


        {{-- ========================================================= --}}
        {{-- KPI Cards --}}
        {{-- ========================================================= --}}

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">


            {{-- ===================================================== --}}
            {{-- Net Revenue --}}
            {{-- ===================================================== --}}

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">

                <div class="flex items-start justify-between">

                    <div>

                        <p class="text-sm font-medium text-gray-500">
                            Pendapatan Bersih
                        </p>

                        <p class="mt-2 text-2xl font-bold text-gray-900">
                            Rp {{ number_format($summary['net_revenue'], 0, ',', '.') }}
                        </p>

                    </div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-green-50">

                        <x-heroicon-o-banknotes
                            class="h-6 w-6 text-green-600"
                        />

                    </div>

                </div>

                <div class="mt-4 flex items-center gap-2 text-xs text-gray-500">

                    <span>
                        Gross:
                    </span>

                    <span class="font-semibold text-gray-700">
                        Rp {{ number_format($summary['gross_revenue'], 0, ',', '.') }}
                    </span>

                </div>

            </div>


            {{-- ===================================================== --}}
            {{-- Total Orders --}}
            {{-- ===================================================== --}}

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">

                <div class="flex items-start justify-between">

                    <div>

                        <p class="text-sm font-medium text-gray-500">
                            Total Pesanan
                        </p>

                        <p class="mt-2 text-2xl font-bold text-gray-900">
                            {{ number_format($summary['total_orders'], 0, ',', '.') }}
                        </p>

                    </div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-blue-50">

                        <x-heroicon-o-shopping-bag
                            class="h-6 w-6 text-blue-600"
                        />

                    </div>

                </div>

                <div class="mt-4 text-xs text-gray-500">
                    Pesanan dengan pembayaran berhasil
                </div>

            </div>


            {{-- ===================================================== --}}
            {{-- Products Sold --}}
            {{-- ===================================================== --}}

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">

                <div class="flex items-start justify-between">

                    <div>

                        <p class="text-sm font-medium text-gray-500">
                            Produk Terjual
                        </p>

                        <p class="mt-2 text-2xl font-bold text-gray-900">
                            {{ number_format($summary['total_products_sold'], 0, ',', '.') }}
                        </p>

                    </div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-purple-50">

                        <x-heroicon-o-cube
                            class="h-6 w-6 text-purple-600"
                        />

                    </div>

                </div>

                <div class="mt-4 text-xs text-gray-500">
                    Berdasarkan item transaksi
                </div>

            </div>


            {{-- ===================================================== --}}
            {{-- Total Products --}}
            {{-- ===================================================== --}}

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">

                <div class="flex items-start justify-between">

                    <div>

                        <p class="text-sm font-medium text-gray-500">
                            Total Produk
                        </p>

                        <p class="mt-2 text-2xl font-bold text-gray-900">
                            {{ number_format($summary['total_products'], 0, ',', '.') }}
                        </p>

                    </div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-orange-50">

                        <x-heroicon-o-archive-box
                            class="h-6 w-6 text-orange-600"
                        />

                    </div>

                </div>

                <div class="mt-4 flex items-center gap-2 text-xs">

                    <span class="text-gray-500">
                        Siap stok:
                    </span>

                    <span class="font-semibold text-green-600">
                        {{ number_format($summary['ready_stock_products'], 0, ',', '.') }}
                    </span>

                </div>

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- Additional Summary --}}
        {{-- ========================================================= --}}

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">


            {{-- ===================================================== --}}
            {{-- Refund --}}
            {{-- ===================================================== --}}

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">

                <div class="flex items-center justify-between">

                    <div>

                        <h3 class="text-base font-semibold text-gray-900">
                            Refund Selesai
                        </h3>

                        <p class="mt-1 text-sm text-gray-500">
                            Nilai refund yang telah selesai diproses.
                        </p>

                    </div>

                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-red-50">

                        <x-heroicon-o-arrow-uturn-left
                            class="h-5 w-5 text-red-600"
                        />

                    </div>

                </div>

                <p class="mt-4 text-xl font-bold text-gray-900">
                    Rp {{ number_format($summary['completed_refund'], 0, ',', '.') }}
                </p>

            </div>


            {{-- ===================================================== --}}
            {{-- Categories --}}
            {{-- ===================================================== --}}

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">

                <div class="flex items-center justify-between">

                    <div>

                        <h3 class="text-base font-semibold text-gray-900">
                            Kategori Produk
                        </h3>

                        <p class="mt-1 text-sm text-gray-500">
                            Jumlah kategori yang tersedia.
                        </p>

                    </div>

                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50">

                        <x-heroicon-o-tag
                            class="h-5 w-5 text-indigo-600"
                        />

                    </div>

                </div>

                <p class="mt-4 text-xl font-bold text-gray-900">
                    {{ number_format($summary['total_categories'], 0, ',', '.') }}
                </p>

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- Empty / Informational State --}}
        {{-- ========================================================= --}}

        @if($summary['total_orders'] === 0)

            <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-10 text-center">

                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">

                    <x-heroicon-o-chart-bar
                        class="h-6 w-6 text-gray-500"
                    />

                </div>

                <h3 class="mt-4 text-base font-semibold text-gray-900">
                    Belum ada data penjualan
                </h3>

                <p class="mx-auto mt-1 max-w-md text-sm text-gray-500">
                    Belum terdapat pesanan dengan pembayaran berhasil
                    pada periode yang dipilih.
                </p>

            </div>

        @endif
        <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">

            {{-- Sales Trend --}}
            <section
                class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm xl:col-span-2">

                <div class="mb-6">

                    <h2 class="text-base font-bold text-gray-900">
                        Tren Penjualan
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        Perkembangan pendapatan, pesanan, dan produk terjual.
                    </p>

                </div>

                <div class="relative h-[320px]">

                    <canvas
                        id="salesTrendChart">
                    </canvas>

                </div>

            </section>


            {{-- Sales By Category --}}
            <section
                class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm xl:col-span-1">

                <div class="mb-6">

                    <h2 class="text-base font-bold text-gray-900">
                        Penjualan Berdasarkan Kategori
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        Distribusi produk terjual berdasarkan kategori.
                    </p>

                </div>

                <div class="relative h-[320px]">

                    <canvas
                        id="salesByCategoryChart">
                    </canvas>

                </div>

            </section>

        </div>

        {{-- ========================================================= --}}
        {{-- Top Selling Products --}}
        {{-- ========================================================= --}}

        <section class="rounded-xl border border-gray-200 bg-white shadow-sm">

            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-5">

                <div>

                    <h2 class="text-base font-bold text-gray-900">
                        Produk Terlaris
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        Produk dengan jumlah penjualan tertinggi pada periode yang dipilih.
                    </p>

                </div>

                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50">

                    <x-heroicon-o-trophy
                        class="h-5 w-5 text-amber-600"
                    />

                </div>

            </div>


            {{-- Table --}}
            <div class="overflow-x-auto">

                <table class="min-w-full divide-y divide-gray-200">

                    <thead class="bg-gray-50">

                        <tr>

                            <th
                                scope="col"
                                class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Produk
                            </th>

                            <th
                                scope="col"
                                class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                SKU
                            </th>

                            <th
                                scope="col"
                                class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Terjual
                            </th>

                            <th
                                scope="col"
                                class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Total Penjualan
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-gray-100 bg-white">

                        @forelse($topSellingProducts as $index => $product)

                            <tr class="transition hover:bg-gray-50">

                                {{-- Product --}}
                                <td class="whitespace-nowrap px-6 py-4">

                                    <div class="flex items-center gap-3">

                                        {{-- Ranking --}}
                                        <div
                                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full
                                            {{ $index === 0
                                                ? 'bg-amber-100 text-amber-700'
                                                : ($index === 1
                                                    ? 'bg-gray-100 text-gray-600'
                                                    : ($index === 2
                                                        ? 'bg-orange-100 text-orange-700'
                                                        : 'bg-gray-50 text-gray-500')) }}"
                                        >

                                            <span class="text-xs font-bold">
                                                {{ $index + 1 }}
                                            </span>

                                        </div>


                                        <div>

                                            <p class="text-sm font-semibold text-gray-900">
                                                {{ $product->product_name }}
                                            </p>

                                        </div>

                                    </div>

                                </td>


                                {{-- SKU --}}
                                <td class="whitespace-nowrap px-6 py-4">

                                    <span
                                        class="rounded-md bg-gray-100 px-2.5 py-1 font-mono text-xs font-medium text-gray-700"
                                    >
                                        {{ $product->product_sku ?? '-' }}
                                    </span>

                                </td>


                                {{-- Quantity --}}
                                <td class="whitespace-nowrap px-6 py-4 text-right">

                                    <span class="text-sm font-semibold text-gray-900">

                                        {{ number_format($product->total_quantity, 0, ',', '.') }}

                                    </span>

                                    <span class="text-xs text-gray-500">
                                        unit
                                    </span>

                                </td>


                                {{-- Total Sales --}}
                                <td class="whitespace-nowrap px-6 py-4 text-right">

                                    <span class="text-sm font-bold text-gray-900">

                                        Rp {{ number_format($product->total_sales, 0, ',', '.') }}

                                    </span>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="4"
                                    class="px-6 py-10 text-center"
                                >

                                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">

                                        <x-heroicon-o-cube
                                            class="h-6 w-6 text-gray-400"
                                        />

                                    </div>

                                    <h3 class="mt-4 text-sm font-semibold text-gray-900">
                                        Belum ada produk terjual
                                    </h3>

                                    <p class="mt-1 text-sm text-gray-500">
                                        Belum terdapat data produk terjual pada periode yang dipilih.
                                    </p>

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>


            {{-- Footer --}}
            @if($topSellingProducts->isNotEmpty())

                <div class="border-t border-gray-200 px-6 py-4">

                    <p class="text-xs text-gray-500">

                        Menampilkan
                        <span class="font-semibold text-gray-700">
                            {{ $topSellingProducts->count() }}
                        </span>
                        produk terlaris.

                    </p>

                </div>

            @endif

        </section>

        {{-- ========================================================= --}}
        {{-- Recent Orders --}}
        {{-- ========================================================= --}}

        <section class="rounded-xl border border-gray-200 bg-white shadow-sm">

            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">

                <div>
                    <h2 class="text-base font-bold text-gray-900">
                        Pesanan Terbaru
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        Daftar pesanan terbaru yang masuk ke sistem.
                    </p>
                </div>

                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50">

                    <x-heroicon-o-shopping-bag
                        class="h-5 w-5 text-blue-600"
                    />

                </div>

            </div>


            {{-- Table --}}
            @if($recentOrders->isNotEmpty())

                <div class="overflow-x-auto">

                    <table class="min-w-full text-sm">

                        <thead class="border-b border-gray-200 bg-gray-50">

                            <tr>

                                <th
                                    class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"
                                >
                                    Order
                                </th>

                                <th
                                    class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"
                                >
                                    Customer
                                </th>

                                <th
                                    class="whitespace-nowrap px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500"
                                >
                                    Total Pembayaran
                                </th>

                                <th
                                    class="whitespace-nowrap px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500"
                                >
                                    Status
                                </th>

                                <th
                                    class="whitespace-nowrap px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500"
                                >
                                    Pembayaran
                                </th>

                                <th
                                    class="whitespace-nowrap px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500"
                                >
                                    Tanggal
                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y divide-gray-100">

                            @foreach($recentOrders as $order)

                                <tr class="transition hover:bg-gray-50">

                                    {{-- Order Number --}}
                                    <td class="whitespace-nowrap px-5 py-4">

                                        <span class="font-semibold text-gray-900">
                                            {{ $order->order_number }}
                                        </span>

                                    </td>


                                    {{-- Customer --}}
                                    <td class="px-5 py-4">

                                        <div class="font-medium text-gray-900">
                                            {{ $order->customer?->name ?? $order->customer_name ?? 'Guest' }}
                                        </div>

                                        @if($order->customer?->email ?? $order->customer_email)

                                            <div class="mt-0.5 text-xs text-gray-500">
                                                {{ $order->customer?->email ?? $order->customer_email }}
                                            </div>

                                        @endif

                                    </td>


                                    {{-- Total Payment --}}
                                    <td class="whitespace-nowrap px-5 py-4 text-right">

                                        <span class="font-semibold text-gray-900">
                                            Rp {{ number_format($order->total_payment, 0, ',', '.') }}
                                        </span>

                                    </td>


                                    {{-- Order Status --}}
                                    <td class="whitespace-nowrap px-5 py-4 text-center">

                                        @php
                                            $status = $order->status;

                                            $statusClasses = match ($status) {

                                                'pending' =>
                                                    'bg-yellow-50 text-yellow-700 ring-yellow-600/20',

                                                'paid' =>
                                                    'bg-blue-50 text-blue-700 ring-blue-600/20',

                                                'processing' =>
                                                    'bg-purple-50 text-purple-700 ring-purple-600/20',

                                                'picked_up' =>
                                                    'bg-indigo-50 text-indigo-700 ring-indigo-600/20',

                                                'shipped' =>
                                                    'bg-cyan-50 text-cyan-700 ring-cyan-600/20',

                                                'completed' =>
                                                    'bg-green-50 text-green-700 ring-green-600/20',

                                                'cancelled' =>
                                                    'bg-red-50 text-red-700 ring-red-600/20',

                                                default =>
                                                    'bg-gray-50 text-gray-700 ring-gray-600/20',
                                            };

                                            $statusLabels = match ($status) {

                                                'pending' => 'Pending',
                                                'paid' => 'Paid',
                                                'processing' => 'Processing',
                                                'picked_up' => 'Picked Up',
                                                'shipped' => 'Shipped',
                                                'completed' => 'Completed',
                                                'cancelled' => 'Cancelled',

                                                default => ucfirst(str_replace('_', ' ', $status)),
                                            };
                                        @endphp

                                        <span
                                            class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $statusClasses }}"
                                        >
                                            {{ $statusLabels }}
                                        </span>

                                    </td>


                                    {{-- Payment Status --}}
                                    <td class="whitespace-nowrap px-5 py-4 text-center">

                                        @php
                                            $paymentStatus = $order->payment_status;

                                            $paymentClasses = match ($paymentStatus) {

                                                'paid' =>
                                                    'bg-green-50 text-green-700 ring-green-600/20',

                                                'pending' =>
                                                    'bg-yellow-50 text-yellow-700 ring-yellow-600/20',

                                                'failed' =>
                                                    'bg-red-50 text-red-700 ring-red-600/20',

                                                'expired' =>
                                                    'bg-gray-50 text-gray-700 ring-gray-600/20',

                                                default =>
                                                    'bg-gray-50 text-gray-700 ring-gray-600/20',
                                            };

                                            $paymentLabels = match ($paymentStatus) {

                                                'paid' => 'Paid',
                                                'pending' => 'Pending',
                                                'failed' => 'Failed',
                                                'expired' => 'Expired',

                                                default => ucfirst(str_replace('_', ' ', $paymentStatus)),
                                            };
                                        @endphp

                                        <span
                                            class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $paymentClasses }}"
                                        >
                                            {{ $paymentLabels }}
                                        </span>

                                    </td>


                                    {{-- Date --}}
                                    <td class="whitespace-nowrap px-5 py-4 text-right">

                                        <div class="font-medium text-gray-700">
                                            {{ $order->created_at?->format('d/m/Y') }}
                                        </div>

                                        <div class="mt-0.5 text-xs text-gray-400">
                                            {{ $order->created_at?->format('H:i') }}
                                        </div>

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>


                {{-- Footer --}}
                <div class="border-t border-gray-200 px-5 py-3">

                    <p class="text-xs text-gray-500">
                        Menampilkan {{ $recentOrders->count() }} pesanan terbaru.
                    </p>

                </div>

            @else

                {{-- Empty State --}}
                <div class="px-6 py-10 text-center">

                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">

                        <x-heroicon-o-shopping-bag
                            class="h-6 w-6 text-gray-400"
                        />

                    </div>

                    <h3 class="mt-4 text-sm font-semibold text-gray-900">
                        Belum ada pesanan
                    </h3>

                    <p class="mt-1 text-sm text-gray-500">
                        Belum terdapat pesanan yang masuk ke sistem.
                    </p>

                </div>

            @endif

        </section>

        {{-- ========================================================= --}}
        {{-- Order Status Summary --}}
        {{-- ========================================================= --}}

        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">

            {{-- Header --}}
            <div class="flex items-center justify-between">

                <div>
                    <h2 class="text-base font-bold text-gray-900">
                        Status Pesanan
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        Ringkasan jumlah pesanan berdasarkan status.
                    </p>
                </div>

                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50">

                    <x-heroicon-o-clipboard-document-list
                        class="h-5 w-5 text-blue-600"
                    />

                </div>

            </div>


            {{-- Status Grid --}}
            <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-7">

                {{-- Pending --}}
                <div class="rounded-lg border border-yellow-100 bg-yellow-50 p-4">

                    <p class="text-xs font-medium text-yellow-700">
                        Pending
                    </p>

                    <p class="mt-2 text-2xl font-bold text-yellow-900">
                        {{ number_format($orderStatus['pending'], 0, ',', '.') }}
                    </p>

                </div>


                {{-- Paid --}}
                <div class="rounded-lg border border-blue-100 bg-blue-50 p-4">

                    <p class="text-xs font-medium text-blue-700">
                        Paid
                    </p>

                    <p class="mt-2 text-2xl font-bold text-blue-900">
                        {{ number_format($orderStatus['paid'], 0, ',', '.') }}
                    </p>

                </div>


                {{-- Processing --}}
                <div class="rounded-lg border border-purple-100 bg-purple-50 p-4">

                    <p class="text-xs font-medium text-purple-700">
                        Processing
                    </p>

                    <p class="mt-2 text-2xl font-bold text-purple-900">
                        {{ number_format($orderStatus['processing'], 0, ',', '.') }}
                    </p>

                </div>


                {{-- Picked Up --}}
                <div class="rounded-lg border border-indigo-100 bg-indigo-50 p-4">

                    <p class="text-xs font-medium text-indigo-700">
                        Picked Up
                    </p>

                    <p class="mt-2 text-2xl font-bold text-indigo-900">
                        {{ number_format($orderStatus['picked_up'], 0, ',', '.') }}
                    </p>

                </div>


                {{-- Shipped --}}
                <div class="rounded-lg border border-cyan-100 bg-cyan-50 p-4">

                    <p class="text-xs font-medium text-cyan-700">
                        Shipped
                    </p>

                    <p class="mt-2 text-2xl font-bold text-cyan-900">
                        {{ number_format($orderStatus['shipped'], 0, ',', '.') }}
                    </p>

                </div>


                {{-- Completed --}}
                <div class="rounded-lg border border-green-100 bg-green-50 p-4">

                    <p class="text-xs font-medium text-green-700">
                        Completed
                    </p>

                    <p class="mt-2 text-2xl font-bold text-green-900">
                        {{ number_format($orderStatus['completed'], 0, ',', '.') }}
                    </p>

                </div>


                {{-- Cancelled --}}
                <div class="rounded-lg border border-red-100 bg-red-50 p-4">

                    <p class="text-xs font-medium text-red-700">
                        Cancelled
                    </p>

                    <p class="mt-2 text-2xl font-bold text-red-900">
                        {{ number_format($orderStatus['cancelled'], 0, ',', '.') }}
                    </p>

                </div>

            </div>

        </section>

        {{-- ========================================================= --}}
        {{-- Refund Summary --}}
        {{-- ========================================================= --}}

        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">

            {{-- Header --}}
            <div class="flex items-center justify-between">

                <div>

                    <h2 class="text-base font-bold text-gray-900">
                        Refund
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        Ringkasan refund pada periode yang dipilih.
                    </p>

                </div>

                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-red-50">

                    <x-heroicon-o-arrow-uturn-left
                        class="h-5 w-5 text-red-600"
                    />

                </div>

            </div>


            {{-- Status Summary --}}
            <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">

                {{-- Total --}}
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">

                    <p class="text-xs font-medium text-gray-500">
                        Total Refund
                    </p>

                    <p class="mt-2 text-2xl font-bold text-gray-900">
                        {{ number_format($refundSummary['total'], 0, ',', '.') }}
                    </p>

                </div>


                {{-- Pending --}}
                <div class="rounded-lg border border-yellow-100 bg-yellow-50 p-4">

                    <p class="text-xs font-medium text-yellow-700">
                        Pending
                    </p>

                    <p class="mt-2 text-2xl font-bold text-yellow-900">
                        {{ number_format($refundSummary['pending'], 0, ',', '.') }}
                    </p>

                </div>


                {{-- Processing --}}
                <div class="rounded-lg border border-blue-100 bg-blue-50 p-4">

                    <p class="text-xs font-medium text-blue-700">
                        Processing
                    </p>

                    <p class="mt-2 text-2xl font-bold text-blue-900">
                        {{ number_format($refundSummary['processing'], 0, ',', '.') }}
                    </p>

                </div>


                {{-- Completed --}}
                <div class="rounded-lg border border-green-100 bg-green-50 p-4">

                    <p class="text-xs font-medium text-green-700">
                        Completed
                    </p>

                    <p class="mt-2 text-2xl font-bold text-green-900">
                        {{ number_format($refundSummary['completed'], 0, ',', '.') }}
                    </p>

                </div>


                {{-- Rejected --}}
                <div class="rounded-lg border border-red-100 bg-red-50 p-4">

                    <p class="text-xs font-medium text-red-700">
                        Rejected
                    </p>

                    <p class="mt-2 text-2xl font-bold text-red-900">
                        {{ number_format($refundSummary['rejected'], 0, ',', '.') }}
                    </p>

                </div>

            </div>


            {{-- Completed Amount --}}
            <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4">

                <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">

                    <div>

                        <p class="text-sm font-medium text-gray-500">
                            Total Nominal Refund Selesai
                        </p>

                        <p class="mt-1 text-xs text-gray-400">
                            Akumulasi refund dengan status completed.
                        </p>

                    </div>

                    <p class="text-xl font-bold text-gray-900">

                        Rp {{ number_format(
                            $refundSummary['completed_amount'],
                            0,
                            ',',
                            '.'
                        ) }}

                    </p>

                </div>

            </div>

        </section>

    </div>
    
    <script
        type="application/json"
        id="salesTrendData"
    >
        @json($salesTrend)
    </script>

    <script
        type="application/json"
        id="salesByCategoryData"
    >
        @json($salesByCategory)
    </script>
@endsection