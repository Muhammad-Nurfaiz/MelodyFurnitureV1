@extends('admin.layouts.app')

@section('title', 'Series')

@section('content')

<div
    x-data="seriesCrud(@js([
        'storeUrl'  => route('admin.series.store'),
        'updateUrl' => url('admin/series'),
    ]))"
    x-init="init()">

    {{-- ===================================================== --}}
    {{-- PAGE HEADER --}}
    {{-- ===================================================== --}}

    <x-admin.page-header
        title="Series"
        description="Kelola seluruh series produk Melody Furniture.">

        <x-slot:actions>

            @can('create', App\Models\Series::class)

                <x-admin.button
                    icon="plus"
                    x-on:click="openCreate()">

                    Tambah Series

                </x-admin.button>

            @endcan

        </x-slot:actions>

    </x-admin.page-header>

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
                        placeholder="Cari series..."
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
                        Nama
                    </x-admin.table.th>

                    <x-admin.table.th>
                        Deskripsi
                    </x-admin.table.th>

                    <x-admin.table.th>
                        Slug
                    </x-admin.table.th>

                    <x-admin.table.th>
                        Dibuat
                    </x-admin.table.th>

                    <x-admin.table.th class="w-32 text-right">
                        Aksi
                    </x-admin.table.th>

                </tr>

            </x-admin.table.thead>

            <x-admin.table.tbody>

                @forelse($series as $item)

                    <x-admin.table.tr>

                        <x-admin.table.td>

                            {{ $item->name }}

                        </x-admin.table.td>

                        <x-admin.table.td>

                            <div class="max-w-xs line-clamp-2 text-sm text-gray-600">

                                {{ $item->description ?: '-' }}

                            </div>

                        </x-admin.table.td>

                        <x-admin.table.td>

                            {{ $item->slug }}

                        </x-admin.table.td>

                        <x-admin.table.td>

                            {{ $item->created_at->format('d M Y') }}

                        </x-admin.table.td>

                        <x-admin.table.td>

                            <x-admin.table.actions>

                                @can('update', $item)

                                    <x-admin.icon-button
                                        icon="pencil-square"
                                        @click='openEdit({{ Js::from([
                                            "id"=>$item->id,
                                            "name"=>$item->name,
                                            "description"=>$item->description,
                                        ]) }})'/>

                                @endcan

                                @can('delete', $item)

                                    <x-admin.icon-button
                                        icon="trash"
                                        color="red"
                                        @click='openDelete({{ Js::from([
                                            "id"=>$item->id,
                                            "name"=>$item->name,
                                            "description"=>$item->description,
                                        ]) }})'/>

                                @endcan

                            </x-admin.table.actions>

                        </x-admin.table.td>

                    </x-admin.table.tr>

                @empty

                    <tr>

                        <td colspan="5">

                            <x-admin.state.empty
                                title="Belum ada series"
                                description="Tambahkan series pertama untuk mulai mengelola produk.">

                                <x-slot:action>

                                    @can('create', App\Models\Series::class)

                                        <x-admin.button
                                            x-on:click="openCreate()">

                                            <x-heroicon-o-plus class="h-5 w-5"/>

                                            Tambah Series

                                        </x-admin.button>

                                    @endcan

                                </x-slot:action>

                            </x-admin.state.empty>

                        </td>

                    </tr>

                @endforelse

            </x-admin.table.tbody>

        </x-admin.table.table>

        @if($series->hasPages())

            <x-admin.pagination.pagination
                :paginator="$series"/>

        @endif

    </x-admin.card>

    {{-- ===================================================== --}}
    {{-- CREATE / EDIT MODAL --}}
    {{-- ===================================================== --}}

    <x-admin.modal.form
        name="series-modal">

        <div class="space-y-5">

            <x-admin.form.group
                label="Nama Series"
                required>

                <x-admin.form.input
                    name="name"
                    placeholder="Masukkan nama series..."
                    x-model="modal.data.name"
                    @input="clearError('name')"
                    x-bind:class="
                        modal.errors.name
                            ? 'border-red-500 focus:border-red-500 focus:ring-red-200'
                            : ''
                    "/>

                <x-admin.form.validation-error
                    alpine="modal.errors.name"/>

            </x-admin.form.group>

            <x-admin.form.group
                label="Deskripsi"
                required>

                <x-admin.form.textarea
                    name="description"
                    rows="4"
                    placeholder="Masukkan deskripsi series..."
                    x-model="modal.data.description"
                    @input="clearError('description')"
                    x-bind:class="
                        modal.errors.description
                            ? 'border-red-500 focus:border-red-500 focus:ring-red-200'
                            : ''
                    "/>

                <x-admin.form.validation-error
                    alpine="modal.errors.description"/>

            </x-admin.form.group>

        </div>

    </x-admin.modal.form>

    {{-- ===================================================== --}}
    {{-- DELETE MODAL --}}
    {{-- ===================================================== --}}
    <x-admin.feedback.confirm-dialog
        name="delete-series">

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