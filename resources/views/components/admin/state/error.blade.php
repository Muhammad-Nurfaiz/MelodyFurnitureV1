@props([
    'title' => 'Terjadi kesalahan',
    'description' => 'Silakan coba beberapa saat lagi.',
])
<div
    class="flex flex-col items-center justify-center px-6 py-20 text-center">
    <div
        class="mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-red-100">
        <x-heroicon-o-exclamation-triangle
            class="h-10 w-10 text-red-500"/>
    </div>
    <h3
        class="text-lg font-semibold text-gray-900">
        {{ $title }}
    </h3>
    <p
        class="mt-2 text-sm text-gray-500">
        {{ $description }}
    </p>
</div>