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
{{-- Statistik akan kita hubungkan ke OrderAdminService     --}}
{{-- pada tahap berikutnya. Untuk sekarang jangan gunakan   --}}
{{-- angka dummy.                                          --}}
{{-- ===================================================== --}}

<x-admin.stats.grid class="mb-6">

    <x-admin.stats.card
        title="Total Pesanan"
        value="{{ number_format($stats['total'], 0, ',', '.') }}"
        icon="shopping-bag"
        color="blue"/>

    <x-admin.stats.card
        title="Menunggu Pembayaran"
        value="{{ number_format($stats['pending_payment'], 0, ',', '.') }}"
        icon="clock"
        color="yellow"/>

    <x-admin.stats.card
        title="Sedang Diproses"
        value="{{ number_format($stats['processing'], 0, ',', '.') }}"
        icon="truck"
        color="purple"/>

    <x-admin.stats.card
        title="Pesanan Selesai"
        value="{{ number_format($stats['completed'], 0, ',', '.') }}"
        icon="check-circle"
        color="green"/>

</x-admin.stats.grid>


{{-- ===================================================== --}}
{{-- STATUS FILTER --}}
{{-- ===================================================== --}}

<div class="mb-6 rounded-2xl border border-gray-200 bg-white p-3 shadow-sm">

    <div class="grid grid-cols-2 gap-2 md:grid-cols-4 lg:grid-cols-8">

        {{-- Semua --}}
        <a
            href="{{ route('admin.orders.index', request()->except(['status', 'page'])) }}"
            class="
                min-h-10
                justify-center
                inline-flex items-center
                rounded-full
                px-4 py-2
                text-sm font-medium
                transition
                {{ request('status', 'all') === 'all'
                    ? 'bg-blue-600 text-white shadow-sm'
                    : 'border border-gray-200 bg-white text-gray-700 hover:bg-gray-50'
                }}
            ">

            <span>Semua</span>

            <span
                class="
                    ml-2
                    inline-flex min-w-5 items-center justify-center
                    rounded-full
                    px-1.5 py-0.5
                    text-xs font-semibold
                    {{ request('status', 'all') === 'all'
                        ? 'bg-white/20 text-white'
                        : 'bg-gray-100 text-gray-600'
                    }}
                ">

                {{ $orders->total() }}

            </span>

        </a>


        {{-- Pending --}}
        <a
            href="{{ route('admin.orders.index', array_merge(request()->except('page'), ['status' => 'pending'])) }}"
            class="
                min-h-10
                justify-center
                inline-flex items-center
                rounded-full
                border
                px-4 py-2
                text-sm font-medium
                transition
                {{ request('status') === 'pending'
                    ? 'border-yellow-500 bg-yellow-500 text-white shadow-sm'
                    : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'
                }}
            ">

            Menunggu

        </a>


        {{-- Paid --}}
        <a
            href="{{ route('admin.orders.index', array_merge(request()->except('page'), ['status' => 'paid'])) }}"
            class="
                min-h-10
                justify-center
                inline-flex items-center
                rounded-full
                border
                px-4 py-2
                text-sm font-medium
                transition
                {{ request('status') === 'paid'
                    ? 'border-blue-600 bg-blue-600 text-white shadow-sm'
                    : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'
                }}
            ">

            Paid

        </a>


        {{-- Processing --}}
        <a
            href="{{ route('admin.orders.index', array_merge(request()->except('page'), ['status' => 'processing'])) }}"
            class="
                min-h-10
                justify-center
                inline-flex items-center
                rounded-full
                border
                px-4 py-2
                text-sm font-medium
                transition
                {{ request('status') === 'processing'
                    ? 'border-purple-600 bg-purple-600 text-white shadow-sm'
                    : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'
                }}
            ">

            Diproses

        </a>


        {{-- Picked Up --}}
        <a
            href="{{ route('admin.orders.index', array_merge(request()->except('page'), ['status' => 'picked_up'])) }}"
            class="
                min-h-10
                justify-center
                inline-flex items-center
                rounded-full
                border
                px-4 py-2
                text-sm font-medium
                transition
                {{ request('status') === 'picked_up'
                    ? 'border-indigo-600 bg-indigo-600 text-white shadow-sm'
                    : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'
                }}
            ">

            Picked Up

        </a>


        {{-- Shipped --}}
        <a
            href="{{ route('admin.orders.index', array_merge(request()->except('page'), ['status' => 'shipped'])) }}"
            class="
                min-h-10
                justify-center
                inline-flex items-center
                rounded-full
                border
                px-4 py-2
                text-sm font-medium
                transition
                {{ request('status') === 'shipped'
                    ? 'border-cyan-600 bg-cyan-600 text-white shadow-sm'
                    : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'
                }}
            ">

            Dikirim

        </a>


        {{-- Completed --}}
        <a
            href="{{ route('admin.orders.index', array_merge(request()->except('page'), ['status' => 'completed'])) }}"
            class="
                min-h-10
                justify-center
                inline-flex items-center
                rounded-full
                border
                px-4 py-2
                text-sm font-medium
                transition
                {{ request('status') === 'completed'
                    ? 'border-green-600 bg-green-600 text-white shadow-sm'
                    : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'
                }}
            ">

            Selesai

        </a>


        {{-- Cancelled --}}
        <a
            href="{{ route('admin.orders.index', array_merge(request()->except('page'), ['status' => 'cancelled'])) }}"
            class="
                min-h-10
                justify-center
                inline-flex items-center
                rounded-full
                border
                px-4 py-2
                text-sm font-medium
                transition
                {{ request('status') === 'cancelled'
                    ? 'border-red-600 bg-red-600 text-white shadow-sm'
                    : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'
                }}
            ">

            Cancelled

        </a>

    </div>

</div>


{{-- ===================================================== --}}
{{-- ORDER TABLE --}}
{{-- ===================================================== --}}

<x-admin.card>

    {{-- ================================================= --}}
    {{-- TOOLBAR --}}
    {{-- ================================================= --}}

    <x-admin.table.toolbar>

        <x-slot:left>

            <form
                method="GET"
                action="{{ route('admin.orders.index') }}"
                class="w-full">

                {{-- Pertahankan filter --}}
                @if(request('status'))
                    <input
                        type="hidden"
                        name="status"
                        value="{{ request('status') }}">
                @endif

                @if(request('payment_status'))
                    <input
                        type="hidden"
                        name="payment_status"
                        value="{{ request('payment_status') }}">
                @endif

                @if(request('courier'))
                    <input
                        type="hidden"
                        name="courier"
                        value="{{ request('courier') }}">
                @endif

                <x-admin.form.search-input
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Cari nomor pesanan, customer, tracking..."
                />

            </form>

        </x-slot:left>


        <x-slot:right>

            <form
                method="GET"
                action="{{ route('admin.orders.index') }}"
                class="flex flex-wrap gap-3">

                {{-- Pertahankan search --}}
                @if(request('search'))
                    <input
                        type="hidden"
                        name="search"
                        value="{{ request('search') }}">
                @endif

                {{-- Status --}}
                <select
                    name="status"
                    onchange="this.form.submit()"
                    class="
                        rounded-lg
                        border-gray-300
                        bg-white
                        text-sm
                        focus:border-blue-500
                        focus:ring-blue-500
                    ">

                    <option value="all">
                        Semua Status
                    </option>

                    <option
                        value="pending"
                        @selected(request('status') === 'pending')>
                        Pending
                    </option>

                    <option
                        value="paid"
                        @selected(request('status') === 'paid')>
                        Paid
                    </option>

                    <option
                        value="processing"
                        @selected(request('status') === 'processing')>
                        Diproses
                    </option>

                    <option
                        value="picked_up"
                        @selected(request('status') === 'picked_up')>
                        Picked Up
                    </option>

                    <option
                        value="shipped"
                        @selected(request('status') === 'shipped')>
                        Dikirim
                    </option>

                    <option
                        value="completed"
                        @selected(request('status') === 'completed')>
                        Completed
                    </option>

                    <option
                        value="cancelled"
                        @selected(request('status') === 'cancelled')>
                        Cancelled
                    </option>

                </select>


                {{-- Payment --}}
                <select
                    name="payment_status"
                    onchange="this.form.submit()"
                    class="
                        rounded-lg
                        border-gray-300
                        bg-white
                        text-sm
                        focus:border-blue-500
                        focus:ring-blue-500
                    ">

                    <option value="all">
                        Semua Payment
                    </option>

                    <option
                        value="pending"
                        @selected(request('payment_status') === 'pending')>
                        Pending
                    </option>

                    <option
                        value="paid"
                        @selected(request('payment_status') === 'paid')>
                        Paid
                    </option>

                    <option
                        value="expired"
                        @selected(request('payment_status') === 'expired')>
                        Expired
                    </option>

                    <option
                        value="failed"
                        @selected(request('payment_status') === 'failed')>
                        Failed
                    </option>

                </select>

            </form>

        </x-slot:right>

    </x-admin.table.toolbar>


    {{-- ================================================= --}}
    {{-- ACTIVE FILTER --}}
    {{-- ================================================= --}}

    @if(
        request()->filled('search') ||
        (request()->filled('status') && request('status') !== 'all') ||
        (request()->filled('payment_status') && request('payment_status') !== 'all') ||
        request()->filled('courier')
    )

        <div
            class="
                flex
                flex-wrap
                items-center
                gap-2
                border-b
                border-gray-200
                bg-gray-50
                px-6
                py-3
            ">

            <span class="text-sm text-gray-500">
                Filter aktif:
            </span>


            @if(request('search'))

                <span
                    class="
                        rounded-full
                        bg-blue-100
                        px-3
                        py-1
                        text-xs
                        font-medium
                        text-blue-700
                    ">

                    Pencarian:
                    {{ request('search') }}

                </span>

            @endif


            @if(request('status') && request('status') !== 'all')

                <span
                    class="
                        rounded-full
                        bg-purple-100
                        px-3
                        py-1
                        text-xs
                        font-medium
                        text-purple-700
                    ">

                    Status:
                    {{ ucfirst(str_replace('_', ' ', request('status'))) }}

                </span>

            @endif


            @if(request('payment_status') && request('payment_status') !== 'all')

                <span
                    class="
                        rounded-full
                        bg-yellow-100
                        px-3
                        py-1
                        text-xs
                        font-medium
                        text-yellow-700
                    ">

                    Payment:
                    {{ ucfirst(request('payment_status')) }}

                </span>

            @endif


            <a
                href="{{ route('admin.orders.index') }}"
                class="
                    ml-auto
                    text-xs
                    font-medium
                    text-gray-500
                    hover:text-gray-900
                ">

                Reset Filter

            </a>

        </div>

    @endif


    {{-- ================================================= --}}
    {{-- TABLE --}}
    {{-- ================================================= --}}

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

            {{-- ================================================= --}}
            {{-- EMPTY --}}
            {{-- ================================================= --}}

            @if($orders->isEmpty())

                <tr>

                    <td
                        colspan="8"
                        class="px-6 py-16 text-center">

                        <x-admin.empty-state
                            title="Tidak ada pesanan"
                            description="Belum ada pesanan yang sesuai dengan filter yang dipilih."
                        />

                    </td>

                </tr>

            @else

                {{-- ================================================= --}}
                {{-- ORDERS --}}
                {{-- ================================================= --}}

                @foreach($orders as $order)

                    <x-admin.table.tr>

                        {{-- ========================================= --}}
                        {{-- ORDER NUMBER --}}
                        {{-- ========================================= --}}

                        <x-admin.table.td>

                            <a
                                href="{{ route('admin.orders.show', $order) }}"
                                class="
                                    font-semibold
                                    text-gray-900
                                    transition
                                    hover:text-blue-600
                                ">

                                {{ $order->order_number }}

                            </a>

                            @if($order->tracking_token)

                                <div class="mt-1 text-xs text-gray-400">

                                    Tracking:
                                    {{ \Illuminate\Support\Str::limit($order->tracking_token, 16) }}

                                </div>

                            @endif

                        </x-admin.table.td>


                        {{-- ========================================= --}}
                        {{-- CUSTOMER --}}
                        {{-- ========================================= --}}

                        <x-admin.table.td>

                            @if($order->customer)

                                <div class="font-medium text-gray-900">

                                    {{ $order->customer_name ?: '-' }}

                                </div>

                                @if($order->customer_email)

                                    <div class="mt-1 text-xs text-gray-500">
                                        {{ $order->customer_email }}
                                    </div>

                                @endif

                                @if($order->customer_phone)

                                    <div class="mt-1 text-xs text-gray-500">
                                        {{ $order->customer_phone }}
                                    </div>

                                @endif

                            @else

                                <span class="text-gray-400">
                                    Customer tidak tersedia
                                </span>

                            @endif

                        </x-admin.table.td>


                        {{-- ========================================= --}}
                        {{-- DATE --}}
                        {{-- ========================================= --}}

                        <x-admin.table.td>

                            <div class="text-sm text-gray-900">

                                {{ $order->created_at?->format('d M Y') }}

                            </div>

                            <div class="mt-1 text-xs text-gray-500">

                                {{ $order->created_at?->format('H:i') }}

                            </div>

                        </x-admin.table.td>


                        {{-- ========================================= --}}
                        {{-- TOTAL --}}
                        {{-- ========================================= --}}

                        <x-admin.table.td class="text-right">

                            <span class="font-semibold text-gray-900">

                                Rp {{ number_format($order->total_payment, 0, ',', '.') }}

                            </span>

                        </x-admin.table.td>


                        {{-- ========================================= --}}
                        {{-- PAYMENT --}}
                        {{-- ========================================= --}}

                        <x-admin.table.td>

                            @switch($order->payment_status)

                                @case('paid')

                                    <x-admin.badge variant="success">
                                        Paid
                                    </x-admin.badge>

                                @break


                                @case('pending')

                                    <x-admin.badge variant="warning">
                                        Pending
                                    </x-admin.badge>

                                @break


                                @case('expired')

                                    <x-admin.badge variant="danger">
                                        Expired
                                    </x-admin.badge>

                                @break


                                @case('failed')

                                    <x-admin.badge variant="danger">
                                        Failed
                                    </x-admin.badge>

                                @break


                                @default

                                    <x-admin.badge>
                                        {{ ucfirst($order->payment_status ?? '-') }}
                                    </x-admin.badge>

                            @endswitch

                        </x-admin.table.td>


                        {{-- ========================================= --}}
                        {{-- ORDER STATUS --}}
                        {{-- ========================================= --}}

                        <x-admin.table.td>

                            @switch($order->status)

                                @case('pending')

                                    <x-admin.badge variant="warning">
                                        Pending
                                    </x-admin.badge>

                                @break


                                @case('paid')

                                    <x-admin.badge variant="primary">
                                        Paid
                                    </x-admin.badge>

                                @break


                                @case('processing')

                                    <x-admin.badge variant="purple">
                                        Diproses
                                    </x-admin.badge>

                                @break


                                @case('picked_up')

                                    <x-admin.badge variant="purple">
                                        Picked Up
                                    </x-admin.badge>

                                @break


                                @case('shipped')

                                    <x-admin.badge variant="primary">
                                        Dikirim
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


                                @case('req_cancel')

                                    <x-admin.badge variant="warning">
                                        Permintaan Cancel
                                    </x-admin.badge>

                                @break


                                @default

                                    <x-admin.badge>
                                        {{ ucfirst(str_replace('_', ' ', $order->status ?? '-')) }}
                                    </x-admin.badge>

                            @endswitch

                        </x-admin.table.td>


                        {{-- ========================================= --}}
                        {{-- COURIER --}}
                        {{-- ========================================= --}}

                        <x-admin.table.td>

                            @if($order->courier)

                                <div class="font-medium uppercase text-gray-700">

                                    {{ $order->courier }}

                                </div>

                                @if($order->tracking_number)

                                    <div class="mt-1 text-xs text-gray-500">

                                        {{ $order->tracking_number }}

                                    </div>

                                @endif

                            @else

                                <span class="text-gray-400">
                                    —
                                </span>

                            @endif

                        </x-admin.table.td>


                        {{-- ========================================= --}}
                        {{-- ACTION --}}
                        {{-- ========================================= --}}

                        <x-admin.table.td>

                            <x-admin.table.actions>

                                <x-admin.icon-button
                                    icon="eye"
                                    href="{{ route('admin.orders.show', $order) }}"
                                    title="Lihat detail"
                                />

                            </x-admin.table.actions>

                        </x-admin.table.td>

                    </x-admin.table.tr>

                @endforeach

            @endif

        </x-admin.table.tbody>

    </x-admin.table.table>


    {{-- ================================================= --}}
    {{-- PAGINATION --}}
    {{-- ================================================= --}}

    <x-admin.pagination.pagination
        :paginator="$orders"
    />

</x-admin.card>


</div>

@endsection
