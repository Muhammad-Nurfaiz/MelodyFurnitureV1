@props([
    'name',

    'maxWidth' => 'lg',

    'bodyPadding' => 'md',

    'scrollable' => true,

    'stickyFooter' => false,

    'footerAlign' => 'end',

    'cancelText' => 'Batal',
])

<x-admin.modal.modal
    :name="$name"
    :maxWidth="$maxWidth">

    <form
        method="POST"
        x-bind:action="modal.action"
        @submit="submit($event)"
        class="flex max-h-[90vh] flex-col">

        @csrf

        {{-- Method Spoofing --}}
        <template x-if="modal.mode === 'edit'">
            <input
                type="hidden"
                name="_method"
                value="PATCH">
        </template>

        {{-- =============================== --}}
        {{-- Header --}}
        {{-- =============================== --}}

        <x-admin.modal.header>

            <span x-text="modal.title || 'Form'"></span>

        </x-admin.modal.header>

        {{-- =============================== --}}
        {{-- Body --}}
        {{-- =============================== --}}

        <x-admin.modal.body
            :padding="$bodyPadding"
            :scrollable="$scrollable">

            {{ $slot }}

        </x-admin.modal.body>

        {{-- =============================== --}}
        {{-- Footer --}}
        {{-- =============================== --}}

        <x-admin.modal.footer
            :align="$footerAlign"
            :sticky="$stickyFooter">

            <x-admin.button
                type="button"
                variant="secondary"
                x-bind:disabled="modal.loading"
                x-on:click="$dispatch('close-modal','{{ $name }}')">

                {{ $cancelText }}

            </x-admin.button>

            <x-admin.button
                type="submit"
                x-bind:disabled="modal.loading">

                {{-- Normal --}}
                <span
                    x-show="!modal.loading"
                    x-text="modal.submitText || 'Simpan'">
                </span>

                {{-- Loading --}}
                <span
                    x-show="modal.loading"
                    class="inline-flex items-center gap-2">

                    <svg
                        class="h-4 w-4 animate-spin"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24">

                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"/>

                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>

                    </svg>

                    <span>Memproses...</span>

                </span>

            </x-admin.button>

        </x-admin.modal.footer>

    </form>

</x-admin.modal.modal>