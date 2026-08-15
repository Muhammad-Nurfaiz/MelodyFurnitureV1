@extends('admin.layouts.app')

@section('title', 'Tarif Shipping')

@section('content')

<div 
    x-data="shippingRateCrud(@js([
            'updateUrl' => url('admin/shipping-rates'),
        ]))"
    x-init="init()">

    {{-- ===================================================== --}}
    {{-- PAGE HEADER --}}
    {{-- ===================================================== --}}

    <x-admin.page-header
        title="Tarif Shipping"
        description="Kelola tarif pengiriman berdasarkan courier dan kabupaten/kota.">
    </x-admin.page-header>

    {{-- ===================================================== --}}
    {{-- TABLE --}}
    {{-- ===================================================== --}}

    <x-admin.card>

        <x-admin.table.toolbar>
            <x-slot:left>
                <form
                    method="GET"
                    class="grid grid-cols-1 md:grid-cols-3 gap-4">

                    {{-- Courier --}}

                    <div>

                        <label
                            class="block text-sm font-medium text-gray-700 mb-2">

                            Courier

                        </label>

                        <select
                            name="courier"
                            class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">

                            <option value="">
                                Semua Courier
                            </option>

                            @foreach($couriers as $courier)

                                <option
                                    value="{{ $courier->id }}"
                                    @selected(request('courier') == $courier->id)
                                >
                                    {{ $courier->name }}
                                </option>

                            @endforeach

                        </select>

                    </div>

                    <div>

                        <label
                            class="block text-sm font-medium text-gray-700 mb-2">

                            Provinsi

                        </label>

                        <select
                            name="province"
                            class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">

                            <option value="">
                                Semua Provinsi
                            </option>

                            @foreach($provinces as $province)

                                <option
                                    value="{{ $province->id }}"
                                    @selected(request('province') == $province->id)
                                >
                                    {{ $province->name }}
                                </option>

                            @endforeach

                        </select>

                    </div>

                    {{-- Search --}}

                    <div>

                        <label
                            class="block text-sm font-medium text-gray-700 mb-2">

                            Kabupaten/Kota

                        </label>

                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Cari kabupaten/kota..."
                            class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">

                    </div>

                    <x-slot:right>
                        {{-- Action --}}
    
                        <div class="flex items-end gap-2">
    
                            <x-admin.button
                                type="submit">
    
                                Filter
    
                            </x-admin.button>
    
                            @if(request()->hasAny(['courier', 'province', 'search']))
    
                                <x-admin.button
                                    variant="secondary"
                                    href="{{ route('admin.shipping-rates.index') }}">
    
                                    Reset
    
                                </x-admin.button>

                            @endif

                        </div>

                        
                    </form>
                    
                </x-slot:left>
                
            </x-slot:right>
        </x-admin.table.toolbar>

        <x-admin.table.table>

            <x-admin.table.thead>

                <tr>

                    <x-admin.table.th>
                        Courier
                    </x-admin.table.th>

                    <x-admin.table.th>
                        Kabupaten/Kota
                    </x-admin.table.th>

                    <x-admin.table.th>
                        Provinsi
                    </x-admin.table.th>

                    <x-admin.table.th>
                        Tipe Tarif
                    </x-admin.table.th>

                    <x-admin.table.th>
                        Tarif
                    </x-admin.table.th>

                    <x-admin.table.th class="text-right w-24">
                        Aksi
                    </x-admin.table.th>

                </tr>

            </x-admin.table.thead>


            <x-admin.table.tbody>

                @forelse($rates as $rate)

                    <x-admin.table.tr>

                        {{-- Courier --}}

                        <x-admin.table.td>

                            <div class="font-semibold text-gray-900">

                                {{ $rate->courier->name }}

                            </div>

                            <div class="text-xs text-gray-500 mt-1">

                                {{ $rate->courier->code }}

                            </div>

                        </x-admin.table.td>


                        {{-- Regency --}}

                        <x-admin.table.td>

                            <div class="font-medium text-gray-900">

                                {{ $rate->regency->name }}

                            </div>

                            <div class="text-xs text-gray-500 mt-1">

                                {{ $rate->regency->id }}

                            </div>

                        </x-admin.table.td>


                        {{-- Province --}}

                        <x-admin.table.td>

                            {{ $rate->regency->province?->name ?? '-' }}

                        </x-admin.table.td>


                        {{-- Rate Type --}}

                        <x-admin.table.td>

                            @if($rate->rate_type === 'per_kg')

                                <x-admin.badge variant="primary">
                                    Per KG
                                </x-admin.badge>

                            @else

                                <x-admin.badge variant="purple">
                                    Tiered
                                </x-admin.badge>

                            @endif

                        </x-admin.table.td>


                        {{-- Rate --}}

                        <x-admin.table.td>

                            @if($rate->rate_type === 'per_kg')

                                @if($rate->price_per_kg !== null)

                                    <div class="font-semibold text-gray-900">
                                        Rp {{ number_format($rate->price_per_kg, 0, ',', '.') }}
                                    </div>

                                    <div class="text-xs text-gray-500 mt-1">
                                        per kg
                                    </div>

                                @else

                                    <x-admin.badge variant="warning">
                                        Belum diatur
                                    </x-admin.badge>

                                @endif

                            @else

                                @if(
                                    $rate->first_price !== null &&
                                    $rate->additional_price_per_kg !== null
                                )

                                    <div class="font-semibold text-gray-900">
                                        Rp {{ number_format($rate->first_price, 0, ',', '.') }}
                                    </div>

                                    <div class="text-xs text-gray-500 mt-1">
                                        1–10 kg
                                    </div>

                                    <div class="text-xs text-gray-500">
                                        + Rp {{ number_format($rate->additional_price_per_kg, 0, ',', '.') }}/kg
                                    </div>

                                @else

                                    <x-admin.badge variant="warning">
                                        Belum diatur
                                    </x-admin.badge>

                                @endif

                            @endif

                        </x-admin.table.td>


                        {{-- Action --}}

                        <x-admin.table.td>

                            <x-admin.table.actions>

                                <x-admin.icon-button
                                    icon="pencil-square"
                                    @click='openEdit({{ Js::from([
                                        "id" => $rate->id,
                                        "courier" => $rate->courier->name,
                                        "courier_code" => $rate->courier->code,
                                        "regency" => $rate->regency->name,
                                        "regency_id" => $rate->regency->id,
                                        "province" => $rate->regency->province?->name,
                                        "rate_type" => $rate->rate_type,
                                        "price_per_kg" => $rate->price_per_kg,
                                        "first_price" => $rate->first_price,
                                        "additional_price_per_kg" => $rate->additional_price_per_kg,
                                    ]) }})'
                                    />

                            </x-admin.table.actions>

                        </x-admin.table.td>

                    </x-admin.table.tr>

                @empty

                    <tr>

                        <td colspan="6">

                            <x-admin.state.empty
                                title="Tarif shipping tidak ditemukan"
                                description="Belum ada tarif shipping yang sesuai dengan filter.">

                            </x-admin.state.empty>

                        </td>

                    </tr>

                @endforelse

            </x-admin.table.tbody>

        </x-admin.table.table>


        {{-- Pagination --}}

        @if($rates->hasPages())

            <x-admin.pagination.pagination
                :paginator="$rates"/>

        @endif

    </x-admin.card>

        {{-- ===================================================== --}}
    {{-- EDIT MODAL --}}
    {{-- ===================================================== --}}

    <x-admin.modal.form
        name="shipping-rate-modal">

        {{-- Courier --}}

        <x-admin.form.group
            label="Courier">

            <x-admin.form.input
                name="courier"
                x-model="modal.data.courier"
                disabled/>

        </x-admin.form.group>


        {{-- Kabupaten/Kota --}}

        <x-admin.form.group
            label="Kabupaten/Kota">

            <x-admin.form.input
                name="regency"
                x-model="modal.data.regency"
                disabled/>

        </x-admin.form.group>


        {{-- Provinsi --}}

        <x-admin.form.group
            label="Provinsi">

            <x-admin.form.input
                name="province"
                x-model="modal.data.province"
                disabled/>

        </x-admin.form.group>


        {{-- Rate Type --}}

        <x-admin.form.group
            label="Tipe Tarif">

            <x-admin.form.input
                name="rate_type"
                x-model="modal.data.rate_type"
                disabled/>

        </x-admin.form.group>


        {{-- Per KG --}}

        <template x-if="modal.data.rate_type === 'per_kg'">

            <x-admin.form.group
                label="Harga per KG"
                required>

                <x-admin.form.input
                    name="price_per_kg"
                    type="number"
                    min="0"
                    step="0.01"
                    placeholder="Masukkan harga per kg..."
                    x-model="modal.data.price_per_kg"
                    @input="clearError('price_per_kg')"
                    x-bind:class="
                        modal.errors.price_per_kg
                            ? 'border-red-500 focus:border-red-500 focus:ring-red-200'
                            : ''
                    "/>

                <x-admin.form.validation-error
                    alpine="modal.errors.price_per_kg"/>

            </x-admin.form.group>

        </template>


        {{-- Tiered --}}

        <template x-if="modal.data.rate_type === 'tiered'">

            <div class="space-y-4">

                <x-admin.form.group
                    label="Tarif 1–10 KG"
                    required>

                    <x-admin.form.input
                        name="first_price"
                        type="number"
                        min="0"
                        step="0.01"
                        placeholder="Masukkan tarif 1–10 kg..."
                        x-model="modal.data.first_price"
                        @input="clearError('first_price')"
                        x-bind:class="
                            modal.errors.first_price
                                ? 'border-red-500 focus:border-red-500 focus:ring-red-200'
                                : ''
                        "/>

                    <x-admin.form.validation-error
                        alpine="modal.errors.first_price"/>

                </x-admin.form.group>


                <x-admin.form.group
                    label="Tarif Tambahan / KG"
                    required>

                    <x-admin.form.input
                        name="additional_price_per_kg"
                        type="number"
                        min="0"
                        step="0.01"
                        placeholder="Masukkan tarif tambahan per kg..."
                        x-model="modal.data.additional_price_per_kg"
                        @input="clearError('additional_price_per_kg')"
                        x-bind:class="
                            modal.errors.additional_price_per_kg
                                ? 'border-red-500 focus:border-red-500 focus:ring-red-200'
                                : ''
                        "/>

                    <x-admin.form.validation-error
                        alpine="modal.errors.additional_price_per_kg"/>

                </x-admin.form.group>

            </div>

        </template>

    </x-admin.modal.form>
</div>

@endsection