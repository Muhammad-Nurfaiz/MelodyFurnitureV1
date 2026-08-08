@extends('admin.layouts.app')

@section('title', 'Customer')

@section('content')


    {{-- ===================================================== --}}
    {{-- CUSTOMER MANAGEMENT --}}
    {{-- ===================================================== --}}

    <x-admin.card>

        <x-admin.card-header
            title="Customer Management"
            description="Lihat informasi customer dan riwayat pemesanan."
        />

        <x-admin.card-body>

            {{-- ================================================= --}}
            {{-- SEARCH --}}
            {{-- ================================================= --}}

            <form
                method="GET"
                action="{{ route('admin.customers.index') }}"
                class="mb-6"
            >

                <div class="flex flex-col gap-3 sm:flex-row">

                    <div class="relative flex-1">

                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-5 w-5 text-gray-400"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="m21 21-4.35-4.35m1.35-5.15a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"
                                />
                            </svg>

                        </div>

                        <input
                            type="search"
                            name="search"
                            value="{{ $search }}"
                            placeholder="Cari nama, email, atau nomor telepon..."
                            class="block w-full rounded-xl border border-gray-300 bg-white py-2.5 pl-10 pr-4 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                        >

                    </div>

                    <x-admin.button
                        type="submit"
                        color="primary"
                    >
                        Cari
                    </x-admin.button>

                    @if($search !== '')

                        <a
                            href="{{ route('admin.customers.index') }}"
                            class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                        >
                            Reset
                        </a>

                    @endif

                </div>

            </form>


            {{-- ================================================= --}}
            {{-- CUSTOMER TABLE --}}
            {{-- ================================================= --}}

            @if($customers->count())

                <div class="overflow-x-auto">

                    <table class="min-w-full">

                        <thead>

                            <tr class="border-b border-gray-200">

                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                                >
                                    Customer
                                </th>

                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                                >
                                    Kontak
                                </th>

                                <th
                                    class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500"
                                >
                                    Total Order
                                </th>

                                <th
                                    class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500"
                                >
                                    Total Spending
                                </th>

                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                                >
                                    Last Order
                                </th>

                                <th
                                    class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500"
                                >
                                    Aksi
                                </th>

                            </tr>

                        </thead>

                        <tbody class="divide-y divide-gray-100">

                            @foreach($customers as $customer)

                                <tr class="transition hover:bg-gray-50">

                                    {{-- Customer --}}

                                    <td class="whitespace-nowrap px-4 py-4">

                                        <div class="flex items-center gap-3">

                                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-50 text-sm font-semibold text-blue-600">

                                                {{ strtoupper(substr($customer->name, 0, 1)) }}

                                            </div>

                                            <div class="min-w-0">

                                                <p class="truncate text-sm font-semibold text-gray-900">
                                                    {{ $customer->name }}
                                                </p>

                                                <p class="text-xs text-gray-500">
                                                    Customer
                                                </p>

                                            </div>

                                        </div>

                                    </td>


                                    {{-- Kontak --}}

                                    <td class="px-4 py-4">

                                        <div class="space-y-1">

                                            <p class="text-sm text-gray-700">
                                                {{ $customer->phone }}
                                            </p>

                                            @if($customer->email)

                                                <p class="max-w-[220px] truncate text-xs text-gray-500">
                                                    {{ $customer->email }}
                                                </p>

                                            @endif

                                        </div>

                                    </td>


                                    {{-- Total Order --}}

                                    <td class="whitespace-nowrap px-4 py-4 text-center">

                                        <span class="inline-flex min-w-10 items-center justify-center rounded-lg bg-gray-100 px-2.5 py-1 text-sm font-semibold text-gray-700">

                                            {{ $customer->orders_count }}

                                        </span>

                                    </td>


                                    {{-- Total Spending --}}

                                    <td class="whitespace-nowrap px-4 py-4 text-right">

                                        <p class="text-sm font-semibold text-gray-900">

                                            Rp {{ number_format(
                                                $customer->total_spending ?? 0,
                                                0,
                                                ',',
                                                '.'
                                            ) }}

                                        </p>

                                    </td>


                                    {{-- Last Order --}}

                                    <td class="whitespace-nowrap px-4 py-4">

                                        @if($customer->orders_max_created_at)

                                            <p class="text-sm text-gray-700">

                                                {{ \Carbon\Carbon::parse(
                                                    $customer->orders_max_created_at
                                                )->translatedFormat('d M Y') }}

                                            </p>

                                            <p class="mt-0.5 text-xs text-gray-400">

                                                {{ \Carbon\Carbon::parse(
                                                    $customer->orders_max_created_at
                                                )->diffForHumans() }}

                                            </p>

                                        @else

                                            <span class="text-sm text-gray-400">
                                                Belum ada order
                                            </span>

                                        @endif

                                    </td>


                                    {{-- Aksi --}}

                                    <td class="whitespace-nowrap px-4 py-4 text-right">

                                        <x-admin.icon-button
                                            icon="eye"
                                            :href="route('admin.customers.show', $customer)"
                                        />

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>


                {{-- ================================================= --}}
                {{-- PAGINATION --}}
                {{-- ================================================= --}}

                @if($customers->hasPages())

                    <div class="mt-6 border-t border-gray-100 pt-5">

                        {{ $customers->links() }}

                    </div>

                @endif

            @else

                {{-- ================================================= --}}
                {{-- EMPTY STATE --}}
                {{-- ================================================= --}}

                @if($search !== '')

                    <x-admin.state.empty
                        title="Customer tidak ditemukan"
                        description="Tidak ada customer yang sesuai dengan pencarian Anda."
                    >

                        <x-slot:action>

                            <a
                                href="{{ route('admin.customers.index') }}"
                                class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                            >
                                Reset Pencarian
                            </a>

                        </x-slot:action>

                    </x-admin.state.empty>

                @else

                    <x-admin.state.empty
                        title="Belum ada customer"
                        description="Customer akan muncul secara otomatis setelah melakukan pemesanan."
                    />

                @endif

            @endif

        </x-admin.card-body>

    </x-admin.card>

@endsection