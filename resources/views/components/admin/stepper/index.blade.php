@props([
    'steps' => [],
    'current' => 1,
])

<div class="mb-8">

    <div class="flex items-center gap-3">

        @foreach($steps as $index => $step)

            <x-admin.stepper.item
                :title="$step"
                :number="$index + 1"
                :current="$current"
                :last="$loop->last"/>

        @endforeach

    </div>

</div>