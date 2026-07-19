@props([
    'placeholder' => 'Cari data...',
])
<div
    x-data="{
        value: $el.querySelector('input')?.value || ''
    }"
    class="relative w-full lg:max-w-sm">
    {{-- Search Icon --}}
    <div
        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
        <x-heroicon-o-magnifying-glass
            class="h-5 w-5 text-gray-400"/>
    </div>
    {{-- Input --}}
    <input
        x-model="value"
        type="text"
        {{ $attributes->merge([
            'class' => '
                w-full
                rounded-xl
                border
                border-gray-300
                bg-white
                py-2.5
                pl-11
                pr-10
                text-sm
                text-gray-900
                placeholder:text-gray-400
                transition
                focus:border-blue-500
                focus:ring-2
                focus:ring-blue-100'
        ]) }}
        placeholder="{{ $placeholder }}"
    >
    {{-- Clear Button --}}
    <button
        x-show="value"
        x-transition
        type="button"
        @click="
            value='';
            $el.parentElement.querySelector('input').value='';
            $el.parentElement.querySelector('input').dispatchEvent(new Event('input'));"
        class="absolute inset-y-0 right-0 flex items-center pr-3">
        <x-heroicon-o-x-mark
            class="h-5 w-5 text-gray-400 hover:text-gray-600"/>
    </button>
</div>