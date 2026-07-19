@props([
    'type' => session('success') ? 'success' : 'error',
])
@if(session('success') || session('error'))
<div
    x-data="{ show:true }"
    x-init="setTimeout(()=>show=false,4000)"
    x-show="show"
    x-transition
    class="fixed right-6 top-6 z-[9999]">
    <div
        class="flex items-start gap-3 rounded-xl border bg-white p-4 shadow-xl
        {{ $type=='success'
            ? 'border-green-200'
            : 'border-red-200'
        }}">
        @if($type=='success')
            <x-heroicon-o-check-circle
                class="h-6 w-6 text-green-500"/>
        @else
            <x-heroicon-o-x-circle
                class="h-6 w-6 text-red-500"/>
        @endif
        <div>
            <p
                class="font-semibold">
                {{ $type=='success' ? 'Berhasil' : 'Terjadi Kesalahan' }}
            </p>
            <p
                class="mt-1 text-sm text-gray-600">
                {{ session('success') ?? session('error') }}
            </p>
        </div>
    </div>
</div>
@endif