@extends('admin.layouts.app')

@section('title', 'Detail Order')

@section('content')

@php
    $cancellationRequest = $order->cancellationRequest;
@endphp


<div class="space-y-6">

    {{-- Header --}}
    <x-admin.page-header
        title="Detail Order"
        :description="$order->order_number"
    >
        <x-slot:actions>

            <a
                href="{{ route('admin.orders.index') }}"
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

        </x-slot:actions>
    </x-admin.page-header>

    @include('admin.modules.order.partials.summary')

    @include('admin.modules.order.partials.customer')

    @include('admin.modules.order.partials.payment')

    @include('admin.modules.order.partials.refund')

    @include('admin.modules.order.partials.shipping')

    @include('admin.modules.order.partials.items')

    @include('admin.modules.order.partials.timeline')

    @include('admin.modules.order.partials.actions')

</div>

@endsection