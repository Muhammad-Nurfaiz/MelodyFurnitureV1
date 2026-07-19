@extends('admin.layouts.app')

@section('title', 'Kategori')

@section('content')

<div
    x-data="categoryCrud(@js([
        'storeUrl'  => route('admin.categories.store'),
        'updateUrl' => url('admin/categories'),
        'deleteUrl' => url('admin/categories'),
    ]))"
    x-init="init()">

    {{-- ===================================================== --}}
    {{-- PAGE HEADER --}}
    {{-- ===================================================== --}}

    <x-admin.page-header
        title="Kategori"
        description="Kelola seluruh kategori produk Melody Furniture.">

        <x-slot:actions>

            @can('create', App\Models\Category::class)

                <x-admin.button
                    icon="plus"
                    x-on:click="openCreate()">

                    Tambah Kategori

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
                        placeholder="Cari kategori..."
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

                @forelse($categories as $category)

                    <x-admin.table.tr>

                        <x-admin.table.td>

                            {{ $category->name }}

                        </x-admin.table.td>

                        <x-admin.table.td>

                            {{ $category->slug }}

                        </x-admin.table.td>

                        <x-admin.table.td>

                            {{ $category->created_at->format('d M Y') }}

                        </x-admin.table.td>

                        <x-admin.table.td>

                            <x-admin.table.actions>

                                @can('update', $category)

                                    <x-admin.icon-button
                                        icon="pencil-square"
                                        @click='openEdit({{ Js::from([
                                            "id"=>$category->id,
                                            "name"=>$category->name,
                                        ]) }})'
                                    />

                                @endcan

                                @can('delete', $category)

                                    <x-admin.icon-button
                                        icon="trash"
                                        color="red"
                                        @click='openDelete({{ Js::from([
                                            "id"=>$category->id,
                                            "name"=>$category->name,
                                        ]) }})'
                                    />

                                @endcan

                            </x-admin.table.actions>

                        </x-admin.table.td>

                    </x-admin.table.tr>

                @empty

                    <tr>

                        <td colspan="4">

                            <x-admin.state.empty
                                title="Belum ada kategori"
                                description="Tambahkan kategori pertama untuk mulai mengelola produk.">

                                <x-slot:action>

                                    @can('create', App\Models\Category::class)

                                        <x-admin.button
                                            x-on:click="openCreate()">

                                            <x-heroicon-o-plus class="h-5 w-5"/>

                                            Tambah Kategori

                                        </x-admin.button>

                                    @endcan

                                </x-slot:action>

                            </x-admin.state.empty>

                        </td>

                    </tr>

                @endforelse

            </x-admin.table.tbody>

        </x-admin.table.table>

        @if($categories->hasPages())

            <div class="mx-2 p-2">

                {{ $categories->links() }}

            </div>

        @endif

    </x-admin.card>

    {{-- ===================================================== --}}
    {{-- MODAL --}}
    {{-- ===================================================== --}}

    <x-admin.modal.form
        name="category-modal">

        <x-admin.form.group
            label="Nama Kategori"
            required>

            <x-admin.form.input
                name="name"
                placeholder="Masukkan nama kategori..."
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

    </x-admin.modal.form>

    {{-- ===================================================== --}}
    {{-- Delete --}}
    {{-- ===================================================== --}}
    <x-admin.feedback.confirm-dialog
        name="delete-category">

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