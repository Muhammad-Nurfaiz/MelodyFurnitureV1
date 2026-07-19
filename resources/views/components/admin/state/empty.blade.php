@props([
    'title' => 'Belum ada data',
    'description' => 'Data akan muncul di sini setelah ditambahkan.',
])
<div class="flex flex-col items-center justify-center px-6 py-20 text-center">
    <div
        class="mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-gray-100">
        <x-heroicon-o-inbox
            class="h-10 w-10 text-gray-400"/>
    </div>
    <h3
        class="text-lg font-semibold text-gray-900">
        {{ $title }}
    </h3>
    <p
        class="mt-2 max-w-md text-sm text-gray-500">
        {{ $description }}
    </p>
    @isset($action)
        <div
            class="mt-8">
            {{ $action }}
        </div>
    @endisset
</div>