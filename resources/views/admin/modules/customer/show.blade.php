@extends('admin.layouts.app')
@section('title', 'Detail Customer')
@section('content')

<div class="space-y-6">


{{-- ===================================================== --}}
{{-- HEADER --}}
{{-- ===================================================== --}}

<div class="flex flex-col gap-4 sm:flex-row sm:items-center justify-between">

    <div class="flex flex-row justify-between items-center gap-2 w-full">
            <div>
                <h1 class="text-xl font-bold text-gray-900">
                    Detail Customer
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Informasi customer dan riwayat pesanan.
                </p>
            </div>
            <a
                href="{{ route('admin.customers.index') }}"
                class="
                    inline-flex
                    items-center
                    gap-2
                    rounded-lg
                    border
                    border-gray-300
                    bg-white
                    px-4
                    py-2.5
                    text-sm
                    font-medium
                    text-gray-700
                    shadow-sm
                    transition
                    hover:bg-gray-50
                    focus:outline-none
                    focus:ring-2
                    focus:ring-blue-500
                    focus:ring-offset-2
                "
            >
                <x-heroicon-o-arrow-left class="h-4 w-4"/>

                Kembali
            </a>
    </div>

</div>


{{-- ===================================================== --}}
{{-- CUSTOMER INFORMATION --}}
{{-- ===================================================== --}}

<x-admin.card>

    <x-admin.card-header
        title="Informasi Customer"
        description="Data customer yang diperoleh dari proses checkout."
    />

    <x-admin.card-body>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

            {{-- Nama --}}
            <div>

                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                    Nama
                </p>

                <p class="mt-1 text-sm font-semibold text-gray-900">
                    {{ $customer->name ?: '-' }}
                </p>

            </div>


            {{-- No. Telepon --}}
            <div>

                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                    No. Telepon
                </p>

                <p class="mt-1 text-sm font-semibold text-gray-900">
                    {{ $customer->phone ?: '-' }}
                </p>

            </div>


            {{-- Email --}}
            <div>

                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                    Email
                </p>

                <p class="mt-1 text-sm font-semibold text-gray-900">
                    {{ $customer->email ?: '-' }}
                </p>

            </div>


            {{-- Customer Sejak --}}
            <div>

                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                    Customer Sejak
                </p>

                <p class="mt-1 text-sm font-semibold text-gray-900">
                    {{ $customer->created_at?->format('d M Y, H:i') ?: '-' }}
                </p>

            </div>


            {{-- Alamat --}}
            <div class="md:col-span-2">

                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                    Alamat
                </p>

                <p class="mt-1 whitespace-pre-line text-sm font-medium text-gray-900">
                    {{ $customer->address_detail ?: '-' }}
                </p>

            </div>

        </div>

    </x-admin.card-body>

</x-admin.card>


{{-- ===================================================== --}}
{{-- CUSTOMER STATISTICS --}}
{{-- ===================================================== --}}

<div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

    {{-- Total Order --}}
    <x-admin.card>

        <x-admin.card-body>

            <div class="flex items-center justify-between">

                <div>

                    <p class="text-sm font-medium text-gray-500">
                        Total Order
                    </p>

                    <p class="mt-2 text-2xl font-bold text-gray-900">
                        {{ $totalOrders }}
                    </p>

                </div>

                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-600">

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
                            d="M3 7h18M5 7v12h14V7M8 7V5a2 2 0 012-2h4a2 2 0 012 2v2"
                        />
                    </svg>

                </div>

            </div>

        </x-admin.card-body>

    </x-admin.card>


    {{-- Total Spending --}}
    <x-admin.card>

        <x-admin.card-body>

            <div class="flex items-center justify-between">

                <div>

                    <p class="text-sm font-medium text-gray-500">
                        Total Spending
                    </p>

                    <p class="mt-2 text-2xl font-bold text-gray-900">
                        Rp {{ number_format($totalSpending ?? 0,0,',','.') }}
                    </p>

                </div>

                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-green-50 text-green-600">

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
                            d="M12 6v12m4-9.5c0-1.1-1.8-2-4-2s-4 .9-4 2 1.8 2 4 2 4 .9 4 2-1.8 2-4 2-4-.9-4-2"
                        />
                    </svg>

                </div>

            </div>

        </x-admin.card-body>

    </x-admin.card>


    {{-- Last Order --}}
    <x-admin.card>

        <x-admin.card-body>

            <div class="flex items-center justify-between">

                <div>

                    <p class="text-sm font-medium text-gray-500">
                        Last Order
                    </p>

                    @if($lastOrder)

                        <p class="mt-2 text-sm font-bold text-gray-900">
                            {{ $lastOrder->order_number }}
                        </p>

                        <p class="mt-1 text-xs text-gray-500">
                            {{ $lastOrder->created_at?->format('d M Y, H:i') }}
                        </p>

                    @else

                        <p class="mt-2 text-sm font-semibold text-gray-900">
                            Belum ada order
                        </p>

                    @endif

                </div>

                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-purple-50 text-purple-600">

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
                            d="M12 6v6l4 2"
                        />
                        <circle
                            cx="12"
                            cy="12"
                            r="9"
                        />
                    </svg>

                </div>

            </div>

        </x-admin.card-body>

    </x-admin.card>

</div>


{{-- ===================================================== --}}
{{-- ORDER HISTORY --}}
{{-- ===================================================== --}}

<x-admin.card>

    <x-admin.card-header
        title="Order History"
        description="Daftar seluruh pesanan yang pernah dibuat customer."
    />

    <x-admin.card-body class="p-0">

        @if($orders->count())

            <div class="overflow-x-auto">

                <table class="min-w-full divide-y divide-gray-200">

                    <thead class="bg-gray-50">

                        <tr>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Order
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Tanggal
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Total
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Pembayaran
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Status
                            </th>

                        </tr>

                    </thead>

                    <tbody class="divide-y divide-gray-200 bg-white">

                        @foreach($orders as $order)

                            <tr class="transition hover:bg-gray-50">

                                {{-- Order --}}
                                <td class="whitespace-nowrap px-6 py-4">

                                    <p class="text-sm font-semibold text-gray-900">
                                        {{ $order->order_number }}
                                    </p>

                                </td>


                                {{-- Date --}}
                                <td class="whitespace-nowrap px-6 py-4">

                                    <p class="text-sm text-gray-700">
                                        {{ $order->created_at?->format('d M Y') }}
                                    </p>

                                    <p class="mt-0.5 text-xs text-gray-500">
                                        {{ $order->created_at?->format('H:i') }}
                                    </p>

                                </td>


                                {{-- Total --}}
                                <td class="whitespace-nowrap px-6 py-4">

                                    <p class="text-sm font-semibold text-gray-900">
                                        Rp {{ number_format($order->total_payment, 0, ',', '.') }}
                                    </p>

                                </td>


                                {{-- Payment Status --}}
                                <td class="whitespace-nowrap px-6 py-4">

                                    @php
                                        $paymentColor = match ($order->payment_status) {
                                            'paid' => 'green',
                                            'pending' => 'yellow',
                                            'expired', 'failed' => 'red',
                                            default => 'gray',
                                        };

                                        $paymentLabel = match ($order->payment_status) {
                                            'paid' => 'Dibayar',
                                            'pending' => 'Menunggu',
                                            'expired' => 'Kedaluwarsa',
                                            'failed' => 'Gagal',
                                            default => ucfirst($order->payment_status),
                                        };
                                    @endphp

                                    <x-admin.badge :color="$paymentColor">
                                        {{ $paymentLabel }}
                                    </x-admin.badge>

                                </td>


                                {{-- Order Status --}}
                                <td class="whitespace-nowrap px-6 py-4">

                                    @php
                                        $statusColor = match ($order->status) {
                                            'completed' => 'green',
                                            'paid' => 'blue',
                                            'processing' => 'yellow',
                                            'picked_up', 'shipped' => 'purple',
                                            'cancelled' => 'red',
                                            'pending' => 'gray',
                                            default => 'gray',
                                        };

                                        $statusLabel = match ($order->status) {
                                            'pending' => 'Menunggu',
                                            'paid' => 'Dibayar',
                                            'processing' => 'Diproses',
                                            'picked_up' => 'Diambil Kurir',
                                            'shipped' => 'Dikirim',
                                            'completed' => 'Selesai',
                                            'cancelled' => 'Dibatalkan',
                                            default => ucfirst($order->status),
                                        };
                                    @endphp

                                    <x-admin.badge :color="$statusColor">
                                        {{ $statusLabel }}
                                    </x-admin.badge>

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

            {{-- Pagination --}}
            @if($orders->hasPages())

                <div class="border-t border-gray-200 px-6 py-4">
                    {{ $orders->links() }}
                </div>

            @endif

        @else

            <div class="px-6 py-12">

                <x-admin.state.empty
                    title="Belum ada order"
                    description="Customer ini belum memiliki riwayat pesanan."
                />

            </div>

        @endif

    </x-admin.card-body>

</x-admin.card>


</div>

@endsection
