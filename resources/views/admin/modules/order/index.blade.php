@extends('admin.layouts.app')

@section('title', 'Manajemen Pesanan')

@section('content')

<div>

    {{-- ===================================================== --}}
    {{-- PAGE HEADER --}}
    {{-- ===================================================== --}}

    <x-admin.page-header
        title="Manajemen Pesanan"
        description="Kelola seluruh pesanan pelanggan Melody Furniture.">

        <x-slot:actions>

            <x-admin.button
                icon="arrow-down-tray">

                Export

            </x-admin.button>

        </x-slot:actions>

    </x-admin.page-header>


    {{-- ===================================================== --}}
    {{-- STATS --}}
    {{-- ===================================================== --}}

    <x-admin.stats.grid class="mb-6">

        <x-admin.stats.card
            title="Total Pesanan"
            value="324"
            icon="shopping-bag"
            color="blue"/>

        <x-admin.stats.card
            title="Menunggu Pembayaran"
            value="18"
            icon="clock"
            color="yellow"/>

        <x-admin.stats.card
            title="Sedang Diproses"
            value="42"
            icon="truck"
            color="purple"/>

        <x-admin.stats.card
            title="Pesanan Selesai"
            value="264"
            icon="check-circle"
            color="green"/>

    </x-admin.stats.grid>

    <x-admin.card class="mb-6">

        <div class="flex flex-wrap gap-3">

            <button
                class="rounded-full bg-blue-600 px-5 py-2 text-sm font-medium text-white">

                Semua
                <span class="ml-2 rounded-full bg-white/20 px-2 py-0.5">
                    324
                </span>

            </button>

            <button
                class="rounded-full border px-5 py-2 text-sm hover:bg-gray-50">

                Pending
                <span class="ml-2 rounded-full bg-yellow-100 px-2 py-0.5 text-yellow-700">
                    18
                </span>

            </button>

            <button
                class="rounded-full border px-5 py-2 text-sm hover:bg-gray-50">

                Paid
                <span class="ml-2 rounded-full bg-blue-100 px-2 py-0.5 text-blue-700">
                    27
                </span>

            </button>

            <button
                class="rounded-full border px-5 py-2 text-sm hover:bg-gray-50">

                Diproses
                <span class="ml-2 rounded-full bg-purple-100 px-2 py-0.5 text-purple-700">
                    42
                </span>

            </button>

            <button
                class="rounded-full border px-5 py-2 text-sm hover:bg-gray-50">

                Selesai
                <span class="ml-2 rounded-full bg-green-100 px-2 py-0.5 text-green-700">
                    264
                </span>

            </button>

            <button
                class="rounded-full border px-5 py-2 text-sm hover:bg-gray-50">

                Cancelled
                <span class="ml-2 rounded-full bg-red-100 px-2 py-0.5 text-red-700">
                    6
                </span>

            </button>

        </div>

    </x-admin.card>


    {{-- ===================================================== --}}
    {{-- TABLE --}}
    {{-- ===================================================== --}}

    <x-admin.card>

        <x-admin.table.toolbar>

            <x-slot:left>

                <form class="w-full">

                    <x-admin.form.search-input

                        placeholder="Cari nomor pesanan atau customer..."

                    />

                </form>

            </x-slot:left>

            <x-slot:right>

                <div class="flex gap-3">

                    <select
                        class="rounded-lg border-gray-300 text-sm">

                        <option>Status</option>

                        <option>Pending</option>

                        <option>Paid</option>

                        <option>Picked Up</option>

                        <option>Completed</option>

                        <option>Cancelled</option>

                    </select>

                    <select
                        class="rounded-lg border-gray-300 text-sm">

                        <option>Payment</option>

                        <option>Pending</option>

                        <option>Paid</option>

                        <option>Expired</option>

                    </select>

                </div>

            </x-slot:right>

        </x-admin.table.toolbar>


        <x-admin.table.table>

            <x-admin.table.thead>

                <tr>

                    <x-admin.table.th>
                        No Pesanan
                    </x-admin.table.th>

                    <x-admin.table.th>
                        Customer
                    </x-admin.table.th>

                    <x-admin.table.th>
                        Tanggal
                    </x-admin.table.th>

                    <x-admin.table.th class="text-right">
                        Total
                    </x-admin.table.th>

                    <x-admin.table.th>
                        Payment
                    </x-admin.table.th>

                    <x-admin.table.th>
                        Status
                    </x-admin.table.th>

                    <x-admin.table.th>
                        Kurir
                    </x-admin.table.th>

                    <x-admin.table.th class="text-right w-28">
                        Aksi
                    </x-admin.table.th>

                </tr>

            </x-admin.table.thead>

            <x-admin.table.tbody>

                @php

                    $orders = [

                        [
                            'number'=>'MLD202607230001',
                            'customer'=>'Andi Saputra',
                            'date'=>'23 Jul 2026',
                            'total'=>'Rp 2.450.000',
                            'payment'=>'Paid',
                            'status'=>'Picked Up',
                            'courier'=>'JNE'
                        ],

                        [
                            'number'=>'MLD202607230002',
                            'customer'=>'Budi Hartono',
                            'date'=>'23 Jul 2026',
                            'total'=>'Rp 875.000',
                            'payment'=>'Pending',
                            'status'=>'Pending',
                            'courier'=>'-'
                        ],

                        [
                            'number'=>'MLD202607230003',
                            'customer'=>'Siska Dewi',
                            'date'=>'22 Jul 2026',
                            'total'=>'Rp 4.200.000',
                            'payment'=>'Paid',
                            'status'=>'Completed',
                            'courier'=>'J&T'
                        ],

                        [
                            'number'=>'MLD202607230004',
                            'customer'=>'Yoga Pratama',
                            'date'=>'22 Jul 2026',
                            'total'=>'Rp 1.540.000',
                            'payment'=>'Expired',
                            'status'=>'Cancelled',
                            'courier'=>'-'
                        ],

                        [
                            'number'=>'MLD202607230005',
                            'customer'=>'Rina Lestari',
                            'date'=>'21 Jul 2026',
                            'total'=>'Rp 3.150.000',
                            'payment'=>'Paid',
                            'status'=>'Paid',
                            'courier'=>'SiCepat'
                        ],

                    ];

                @endphp


                @foreach($orders as $order)

                    <x-admin.table.tr>

                        <x-admin.table.td>

                            <div class="font-semibold">

                                {{ $order['number'] }}

                            </div>

                        </x-admin.table.td>

                        <x-admin.table.td>

                            {{ $order['customer'] }}

                        </x-admin.table.td>

                        <x-admin.table.td>

                            {{ $order['date'] }}

                        </x-admin.table.td>

                        <x-admin.table.td class="text-right font-semibold">

                            {{ $order['total'] }}

                        </x-admin.table.td>

                        <x-admin.table.td>

                            @switch($order['payment'])

                                @case('Paid')

                                    <x-admin.badge variant="success">

                                        Paid

                                    </x-admin.badge>

                                @break

                                @case('Pending')

                                    <x-admin.badge variant="warning">

                                        Pending

                                    </x-admin.badge>

                                @break

                                @case('Expired')

                                    <x-admin.badge variant="danger">

                                        Expired

                                    </x-admin.badge>

                                @break

                            @endswitch

                        </x-admin.table.td>

                        <x-admin.table.td>

                            @switch($order['status'])

                                @case('Pending')

                                    <x-admin.badge>

                                        Pending

                                    </x-admin.badge>

                                @break

                                @case('Paid')

                                    <x-admin.badge variant="primary">

                                        Paid

                                    </x-admin.badge>

                                @break

                                @case('Picked Up')

                                    <x-admin.badge variant="purple">

                                        Picked Up

                                    </x-admin.badge>

                                @break

                                @case('Completed')

                                    <x-admin.badge variant="success">

                                        Completed

                                    </x-admin.badge>

                                @break

                                @case('Cancelled')

                                    <x-admin.badge variant="danger">

                                        Cancelled

                                    </x-admin.badge>

                                @break

                            @endswitch

                        </x-admin.table.td>

                        <x-admin.table.td>

                            {{ $order['courier'] }}

                        </x-admin.table.td>

                        <x-admin.table.td>

                            <x-admin.table.actions>

                                <x-admin.icon-button

                                    icon="eye"

                                />

                            </x-admin.table.actions>

                        </x-admin.table.td>

                    </x-admin.table.tr>

                @endforeach

            </x-admin.table.tbody>

        </x-admin.table.table>

    </x-admin.card>

</div>

@endsection