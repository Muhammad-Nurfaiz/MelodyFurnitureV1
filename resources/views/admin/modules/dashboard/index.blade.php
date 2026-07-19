@extends('admin.layouts.app')

@section('title','Dashboard')

@section('content')

<div class="space-y-8">

    <div>

        <h2 class="text-3xl font-bold text-gray-900">

            Dashboard

        </h2>

        <p class="mt-1 text-gray-500">

            Selamat datang di Admin Control Center Melody Furniture.

        </p>

    </div>

    <x-admin.stats.grid>

        <x-admin.stats.card

            title="Kategori"

            value="12"

            description="Kategori aktif"

            icon="squares-2x2"

            color="blue"/>

        <x-admin.stats.card

            title="Produk"

            value="250"

            description="Produk tersedia"

            icon="cube"

            color="green"/>

        <x-admin.stats.card

            title="Customer"

            value="530"

            description="Customer terdaftar"

            icon="users"

            color="purple"/>

        <x-admin.stats.card

            title="Voucher"

            value="18"

            description="Voucher aktif"

            icon="ticket"

            color="yellow"/>

    </x-admin.stats.grid>

</div>

@endsection