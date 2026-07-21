@extends('admin.layouts.app')

@section('title', 'Produk')

@section('content')
<div>
    {{-- ===================================================== --}}
    {{-- PAGE HEADER --}}
    {{-- ===================================================== --}}
    <x-admin.page-header
        title="Produk"
        description="Kelola seluruh produk Melody Furniture.">
        <x-slot:actions>
            @can('create', App\Models\Product::class)
                <x-admin.button
                    icon="plus"
                    href="{{ route('admin.products.create') }}">
                    Tambah Produk
                </x-admin.button>
            @endcan
        </x-slot:actions>
    </x-admin.page-header>
    {{-- ===================================================== --}}
    {{-- STATS --}}
    {{-- ===================================================== --}}
    <x-admin.stats.grid class="mb-6">
        <x-admin.stats.card
            title="Total Produk"
            :value="$stats['total']"
            icon="cube"
            color="blue"/>
        <x-admin.stats.card
            title="Sedang Sale"
            :value="$stats['sale']"
            icon="tag"
            color="red"/>
        <x-admin.stats.card
            title="Ready Stock"
            :value="$stats['stock']"
            icon="check-circle"
            color="green"/>
        <x-admin.stats.card
            title="Kategori"
            :value="$stats['categories']"
            icon="squares-2x2"
            color="purple"/>
    </x-admin.stats.grid>
    {{-- ===================================================== --}}
    {{-- TABLE --}}
    {{-- ===================================================== --}}
    <x-admin.card>
        <x-admin.table.toolbar>
            <x-slot:left>
                <form
                    method="GET"
                    class="w-full">
                    <x-admin.form.search-input
                        name="search"
                        :value="request('search')"
                        placeholder="Cari produk..."
                        oninput="
                            clearTimeout(this.delay);
                            this.delay = setTimeout(() => {
                                this.form.submit()
                            },400);
                        "/>
                </form>
            </x-slot:left>
        </x-admin.table.toolbar>
        <x-admin.table.table>
            <x-admin.table.thead>
                <tr>
                    <x-admin.table.th>
                        Produk
                    </x-admin.table.th>
                    <x-admin.table.th>
                        Harga
                    </x-admin.table.th>
                    <x-admin.table.th>
                        Kategori
                    </x-admin.table.th>
                    <x-admin.table.th>
                        Series
                    </x-admin.table.th>
                    <x-admin.table.th>
                        Status
                    </x-admin.table.th>
                    <x-admin.table.th>
                        Terjual
                    </x-admin.table.th>
                    <x-admin.table.th class="text-right w-32">
                        Aksi
                    </x-admin.table.th>
                </tr>
            </x-admin.table.thead>
            <x-admin.table.tbody>
                @forelse($products as $product)
                    <x-admin.table.tr>
                        {{-- ===================================== --}}
                        {{-- PRODUCT --}}
                        {{-- ===================================== --}}
                        <x-admin.table.td>
                            <div class="flex items-center gap-4">
                                <x-admin.avatar
                                    :src="$product->thumbnail?->url"
                                    :alt="$product->name"
                                    size="lg"/>
                                <div>
                                    <div class="font-semibold text-gray-900">
                                        {{ $product->name }}
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        {{ Str::limit($product->slug,40) }}
                                    </div>
                                </div>
                            </div>
                        </x-admin.table.td>
                        {{-- ===================================== --}}
                        {{-- PRICE --}}
                        {{-- ===================================== --}}
                        <x-admin.table.td>
                            <x-admin.price
                                :original="$product->original_price"
                                :discount="$product->discount_price"
                                :sale="$product->is_sale"/>
                        </x-admin.table.td>
                        {{-- ===================================== --}}
                        {{-- CATEGORY --}}
                        {{-- ===================================== --}}
                        <x-admin.table.td>
                            <x-admin.badge
                                variant="primary">
                                {{ $product->category?->name ?: 'Tidak ada' }}
                            </x-admin.badge>
                        </x-admin.table.td>
                        {{-- ===================================== --}}
                        {{-- SERIES --}}
                        {{-- ===================================== --}}
                        <x-admin.table.td>
                            <x-admin.badge
                                variant="purple">
                                {{ $product->series?->name ?? '-' }}
                            </x-admin.badge>
                        </x-admin.table.td>
                        {{-- ===================================== --}}
                        {{-- STATUS --}}
                        {{-- ===================================== --}}
                        <x-admin.table.td>
                            <div class="flex flex-wrap items-center gap-2">
                                @if($product->is_sale)
                                    <x-admin.badge
                                        variant="danger">
                                        Sale
                                    </x-admin.badge>
                                @else
                                    <x-admin.badge>
                                        Normal
                                    </x-admin.badge>
                                @endif
                                @if($product->ready_stock)
                                    <x-admin.badge
                                        variant="success">
                                        Ready
                                    </x-admin.badge>
                                @else
                                    <x-admin.badge
                                        variant="warning">
                                        Habis
                                    </x-admin.badge>
                                @endif
                            </div>
                        </x-admin.table.td>
                        {{-- ===================================== --}}
                        {{-- SOLD --}}
                        {{-- ===================================== --}}
                        <x-admin.table.td class="text-right font-medium">
                            {{ number_format($product->total_sold) }}
                        </x-admin.table.td>
                        {{-- ===================================== --}}
                        {{-- ACTION --}}
                        {{-- ===================================== --}}
                        <x-admin.table.td>
                            <x-admin.table.actions>
                                @can('update',$product)
                                    <x-admin.icon-button
                                        icon="pencil-square"
                                        href="{{ route('admin.products.edit',$product) }}"/>
                                @endcan
                                @can('delete',$product)
                                    <x-admin.icon-button
                                        icon="trash"
                                        color="red"
                                        @click="
                                            $dispatch('open-alert',{
                                                name:'delete-product',
                                                title:'Hapus Produk',
                                                message:'Apakah Anda yakin ingin menghapus produk {{ addslashes($product->name) }}?',
                                                action:'{{ route('admin.products.destroy',$product) }}'
                                            })
                                        "
                                    />
                                @endcan
                            </x-admin.table.actions>
                        </x-admin.table.td>
                    </x-admin.table.tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <x-admin.state.empty
                                title="Belum ada produk"
                                description="Tambahkan produk pertama untuk mulai mengelola katalog Melody Furniture.">
                                <x-slot:action>
                                    @can('create', App\Models\Product::class)
                                        <x-admin.button
                                            icon="plus"
                                            href="{{ route('admin.products.create') }}">
                                            Tambah Produk
                                        </x-admin.button>
                                    @endcan
                                </x-slot:action>
                            </x-admin.state.empty>
                        </td>
                    </tr>
                @endforelse
            </x-admin.table.tbody>
        </x-admin.table.table>
        @if($products->hasPages())
            <x-admin.pagination.pagination
                :paginator="$products"/>
        @endif
    </x-admin.card>
    <x-admin.feedback.confirm-dialog
        name="delete-product">

        <form
            method="POST"
            x-bind:action="alert.action">

            @csrf
            @method('DELETE')

            <x-admin.button
                type="submit"
                variant="danger">

                Ya, Hapus

            </x-admin.button>

        </form>

    </x-admin.feedback.confirm-dialog>

</div>

@endsection