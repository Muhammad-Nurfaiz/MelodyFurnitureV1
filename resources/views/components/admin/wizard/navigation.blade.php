@props([
    'cancelUrl' => null,
    'lastStep' => 4,
    'submitText' => 'Simpan Produk',
])

<div class="mt-8 flex items-center justify-between border-t pt-6">

    <div>

        {{-- STEP 1 --}}
        <template x-if="step === 1">

            <x-admin.button
                href="{{ $cancelUrl }}"
                color="secondary">

                Batal

            </x-admin.button>

        </template>

        {{-- STEP 2-4 --}}
        <template x-if="step > 1">

            <x-admin.button
                type="button"
                color="secondary"
                @click="prevStep()">

                Kembali

            </x-admin.button>

        </template>

    </div>

    <div class="flex items-center gap-3">

        <template x-if="step < {{ $lastStep }}">

            <x-admin.button
                type="button"
                @click="nextStep()">

                Lanjut

            </x-admin.button>

        </template>

        <template x-if="step === {{ $lastStep }}">

            <x-admin.button
                type="submit">

                {{ $submitText }}

            </x-admin.button>

        </template>

    </div>

</div>