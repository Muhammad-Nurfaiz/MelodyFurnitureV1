@extends('admin.layouts.app')

@section('title', 'Manajemen Voucher')

@section('content')

{{-- ===================================================== --}}
{{-- PAGE HEADER --}}
{{-- ===================================================== --}}

<x-admin.page-header
    title="Manajemen Voucher"
    description="Kelola voucher dan promo yang tersedia untuk pelanggan Melody Furniture.">

    <x-slot:actions>

        <x-admin.button
            icon="plus"
            href="{{ route('admin.vouchers.create') }}">
            Tambah Voucher
        </x-admin.button>

    </x-slot:actions>

</x-admin.page-header>


{{-- ===================================================== --}}
{{-- STATS --}}
{{-- ===================================================== --}}

<x-admin.stats.grid class="mb-6">

    <x-admin.stats.card
        title="Total Voucher"
        value="{{ number_format($stats['total'], 0, ',', '.') }}"
        icon="ticket"
        color="blue"/>

    <x-admin.stats.card
        title="Voucher Aktif"
        value="{{ number_format($stats['active'], 0, ',', '.') }}"
        icon="check-circle"
        color="green"/>

    <x-admin.stats.card
        title="Tidak Aktif"
        value="{{ number_format($stats['inactive'], 0, ',', '.') }}"
        icon="pause-circle"
        color="gray"/>

    <x-admin.stats.card
        title="Expired"
        value="{{ number_format($stats['expired'], 0, ',', '.') }}"
        icon="clock"
        color="red"/>

</x-admin.stats.grid>


{{-- ===================================================== --}}
{{-- VOUCHER TABLE --}}
{{-- ===================================================== --}}

<x-admin.card>

    {{-- ================================================= --}}
    {{-- TOOLBAR --}}
    {{-- ================================================= --}}

    <x-admin.table.toolbar>

        <x-slot:left>

            <form
                method="GET"
                action="{{ route('admin.vouchers.index') }}"
                class="w-full">

                {{-- Pertahankan filter status --}}
                @if(request('status'))
                    <input
                        type="hidden"
                        name="status"
                        value="{{ request('status') }}">
                @endif

                {{-- Pertahankan discount type --}}
                @if(request('discount_type'))
                    <input
                        type="hidden"
                        name="discount_type"
                        value="{{ request('discount_type') }}">
                @endif

                <x-admin.form.search-input
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Cari kode voucher..."
                />

            </form>

        </x-slot:left>


        <x-slot:right>

            <form
                method="GET"
                action="{{ route('admin.vouchers.index') }}"
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
                        value="active"
                        @selected(request('status') === 'active')>
                        Aktif
                    </option>

                    <option
                        value="inactive"
                        @selected(request('status') === 'inactive')>
                        Tidak Aktif
                    </option>

                    <option
                        value="expired"
                        @selected(request('status') === 'expired')>
                        Expired
                    </option>

                </select>


                {{-- Discount Type --}}
                <select
                    name="discount_type"
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
                        Semua Diskon
                    </option>

                    <option
                        value="percentage"
                        @selected(request('discount_type') === 'percentage')>
                        Persentase
                    </option>

                    <option
                        value="fixed"
                        @selected(request('discount_type') === 'fixed')>
                        Nominal Tetap
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
        (request()->filled('discount_type') && request('discount_type') !== 'all')
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
                    {{ ucfirst(request('status')) }}

                </span>

            @endif


            @if(request('discount_type') && request('discount_type') !== 'all')

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

                    Diskon:
                    {{ request('discount_type') === 'percentage'
                        ? 'Persentase'
                        : 'Nominal Tetap'
                    }}

                </span>

            @endif


            <a
                href="{{ route('admin.vouchers.index') }}"
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
                    Voucher
                </x-admin.table.th>

                <x-admin.table.th>
                    Diskon
                </x-admin.table.th>

                <x-admin.table.th>
                    Minimal Belanja
                </x-admin.table.th>

                <x-admin.table.th>
                    Periode
                </x-admin.table.th>

                <x-admin.table.th>
                    Penggunaan
                </x-admin.table.th>

                <x-admin.table.th>
                    Status
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

            @if($vouchers->isEmpty())

                <tr>

                    <td
                        colspan="7"
                        class="px-6 py-16 text-center">

                        <x-admin.empty-state
                            title="Tidak ada voucher"
                            description="Belum ada voucher yang sesuai dengan filter yang dipilih."
                        />

                    </td>

                </tr>

            @else

                {{-- ================================================= --}}
                {{-- VOUCHERS --}}
                {{-- ================================================= --}}

                @foreach($vouchers as $voucher)

                    <x-admin.table.tr>

                        {{-- ========================================= --}}
                        {{-- VOUCHER --}}
                        {{-- ========================================= --}}

                        <x-admin.table.td>

                            <a
                                href="{{ route('admin.vouchers.show', $voucher->id) }}"
                                class="
                                    font-semibold
                                    text-gray-900
                                    transition
                                    hover:text-blue-600
                                ">

                                {{ $voucher->code }}

                            </a>

                            <div class="mt-1 text-xs text-gray-500">

                                {{ $voucher->discount_type === 'percentage'
                                    ? 'Diskon Persentase'
                                    : 'Diskon Nominal'
                                }}

                            </div>

                        </x-admin.table.td>


                        {{-- ========================================= --}}
                        {{-- DISCOUNT --}}
                        {{-- ========================================= --}}

                        <x-admin.table.td>

                            <span class="font-semibold text-gray-900">

                                @if($voucher->discount_type === 'percentage')

                                    {{ number_format($voucher->discount_value, 0, ',', '.') }}%

                                @else

                                    Rp {{ number_format($voucher->discount_value, 0, ',', '.') }}

                                @endif

                            </span>

                            @if(
                                $voucher->discount_type === 'percentage' &&
                                $voucher->max_discount_amount
                            )

                                <div class="mt-1 text-xs text-gray-500">

                                    Maks.
                                    Rp {{ number_format($voucher->max_discount_amount, 0, ',', '.') }}

                                </div>

                            @endif

                        </x-admin.table.td>


                        {{-- ========================================= --}}
                        {{-- MINIMUM ORDER --}}
                        {{-- ========================================= --}}

                        <x-admin.table.td>

                            @if($voucher->min_order_amount > 0)

                                Rp {{ number_format($voucher->min_order_amount, 0, ',', '.') }}

                            @else

                                <span class="text-gray-400">
                                    Tidak ada minimum
                                </span>

                            @endif

                        </x-admin.table.td>


                        {{-- ========================================= --}}
                        {{-- PERIOD --}}
                        {{-- ========================================= --}}

                        <x-admin.table.td>

                            @if($voucher->start_date)

                                <div class="text-sm text-gray-900">

                                    {{ \Carbon\Carbon::parse($voucher->start_date)->format('d M Y') }}

                                </div>

                            @else

                                <div class="text-sm text-gray-500">

                                    Langsung aktif

                                </div>

                            @endif

                            <div class="mt-1 text-xs text-gray-500">

                                s/d
                                {{ \Carbon\Carbon::parse($voucher->expiry_date)->format('d M Y H:i') }}

                            </div>

                        </x-admin.table.td>


                        {{-- ========================================= --}}
                        {{-- USAGE --}}
                        {{-- ========================================= --}}

                        <x-admin.table.td>

                            <div class="text-sm font-medium text-gray-900">

                                {{ number_format($voucher->used_count, 0, ',', '.') }}

                                @if($voucher->usage_limit !== null)

                                    /
                                    {{ number_format($voucher->usage_limit, 0, ',', '.') }}

                                @else

                                    / ∞

                                @endif

                            </div>


                            @if($voucher->usage_limit !== null)

                                <div class="mt-1">

                                    <div
                                        class="
                                            h-1.5
                                            w-24
                                            overflow-hidden
                                            rounded-full
                                            bg-gray-200
                                        ">

                                        @php
                                            $usagePercentage = $voucher->usage_limit > 0
                                                ? min(
                                                    ($voucher->used_count / $voucher->usage_limit) * 100,
                                                    100
                                                )
                                                : 0;
                                        @endphp

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
                                            style="width: {{ $usagePercentage }}%">
                                        </div>

                                    </div>

                                </div>

                            @endif

                        </x-admin.table.td>


                        {{-- ========================================= --}}
                        {{-- STATUS --}}
                        {{-- ========================================= --}}

                        <x-admin.table.td>

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

                        </x-admin.table.td>


                        {{-- ========================================= --}}
                        {{-- ACTION --}}
                        {{-- ========================================= --}}

                        <x-admin.table.td>

                            <x-admin.table.actions>

                                {{-- Detail --}}
                                <x-admin.icon-button
                                    icon="eye"
                                    href="{{ route('admin.vouchers.show', $voucher->id) }}"
                                    title="Lihat detail"
                                />


                                {{-- Edit --}}
                                <x-admin.icon-button
                                    icon="pencil"
                                    href="{{ route('admin.vouchers.edit', $voucher->id) }}"
                                    title="Edit voucher"
                                />


                                {{-- Toggle Active --}}
                                <form
                                    method="POST"
                                    action="{{ route('admin.vouchers.toggle-active', $voucher->id) }}"
                                >

                                    @csrf
                                    @method('PATCH')

                                    <button
                                        type="submit"
                                        title="{{ $voucher->is_active ? 'Nonaktifkan voucher' : 'Aktifkan voucher' }}"
                                        class="
                                            inline-flex
                                            h-9
                                            w-9
                                            items-center
                                            justify-center
                                            rounded-lg
                                            text-gray-500
                                            transition
                                            hover:bg-gray-100
                                            hover:text-gray-900
                                        "
                                    >

                                        @if($voucher->is_active)

                                            <x-dynamic-component
                                                component="heroicon-o-pause"
                                                class="h-5 w-5"
                                            />

                                        @else

                                            <x-dynamic-component
                                                component="heroicon-o-play"
                                                class="h-5 w-5"
                                            />

                                        @endif

                                    </button>

                                </form>


                                {{-- Delete --}}
                                <form
                                    method="POST"
                                    action="{{ route('admin.vouchers.destroy', $voucher->id) }}"
                                    onsubmit="return confirm('Yakin ingin menghapus voucher {{ $voucher->code }}?')"
                                >

                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        title="Hapus voucher"
                                        class="
                                            inline-flex
                                            h-9
                                            w-9
                                            items-center
                                            justify-center
                                            rounded-lg
                                            text-gray-500
                                            transition
                                            hover:bg-red-50
                                            hover:text-red-600
                                        "
                                    >

                                        <x-dynamic-component
                                            component="heroicon-o-trash"
                                            class="h-5 w-5"
                                        />

                                    </button>

                                </form>

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
        :paginator="$vouchers"
    />

</x-admin.card>

@endsection