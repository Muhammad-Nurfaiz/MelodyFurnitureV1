<div
    class="space-y-4 p-6">
    @foreach(range(1,6) as $row)
        <div
            class="flex animate-pulse items-center gap-4">
            <div class="h-5 w-48 rounded bg-gray-200"></div>
            <div class="h-5 w-28 rounded bg-gray-200"></div>
            <div class="h-5 w-20 rounded bg-gray-200"></div>
            <div class="ml-auto h-5 w-16 rounded bg-gray-200"></div>
        </div>
    @endforeach
</div>