@props([
    'title' => 'Belum ada data',
    'description' => 'Data akan muncul setelah Anda menambahkannya.',
])
<tr>
    <td
        colspan="100"
        class="px-6 py-20 text-center">
        <div
            class="mx-auto flex max-w-sm flex-col items-center">
            <div
                class="mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100">
                <x-heroicon-o-inbox
                    class="h-8 w-8 text-gray-400"/>
            </div>
            <h3
                class="text-lg font-semibold text-gray-800">
                {{ $title }}
            </h3>
            <p
                class="mt-2 text-sm leading-6 text-gray-500">
                {{ $description }}
            </p>
        </div>
    </td>
</tr>