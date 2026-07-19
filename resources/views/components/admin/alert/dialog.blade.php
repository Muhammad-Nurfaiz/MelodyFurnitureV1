@props([
    'name',
    'maxWidth' => 'md',
])
@php
$widths = [
    'sm' => 'max-w-md',
    'md' => 'max-w-lg',
    'lg' => 'max-w-2xl',
];
@endphp
<div
    x-data="{ open:false }"
    x-cloak
    x-on:open-alert.window="
        if($event.detail.name === '{{ $name }}'){
            open = true;
        }"
    x-on:close-alert.window="
        if($event.detail === '{{ $name }}'){
            open = false;
        }"
    x-on:keydown.escape.window="open=false"
>
    {{-- Overlay --}}
    <div
        x-show="open"
        x-transition.opacity
        class="fixed inset-0 z-50 bg-black/40"
        @click="open=false">
    </div>
    {{-- Dialog --}}
    <div
        x-show="open"
        x-transition
        class="fixed inset-0 z-50 flex items-center justify-center p-6">
        <div
            @click.stop
            class="w-full {{ $widths[$maxWidth] }} rounded-2xl bg-white shadow-2xl">
            <form
                method="POST"
                x-bind:action="alert.action">
                @csrf
                @method('DELETE')
                <div class="p-6">
                    {{-- Icon --}}
                    <div class="mb-5 flex justify-center">
                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-red-100">
                            <x-heroicon-o-trash
                                class="h-7 w-7 text-red-600"/>
                        </div>
                    </div>
                    {{-- Title --}}
                    <h2
                        class="text-center text-lg font-semibold text-gray-900"
                        x-text="alert.title">
                    </h2>
                    {{-- Message --}}
                    <p
                        class="mt-2 text-center text-sm text-gray-500"
                        x-text="alert.message">
                    </p>
                </div>
                <div
                    class="flex justify-end gap-3 border-t px-6 py-4">
                    <x-admin.button
                        type="button"
                        variant="secondary"
                        x-on:click="$dispatch('close-alert','{{ $name }}')">

                        Batal
                    </x-admin.button>
                    <x-admin.button
                        type="submit"
                        variant="danger">
                        Hapus
                    </x-admin.button>
                </div>
            </form>
        </div>
    </div>
</div>