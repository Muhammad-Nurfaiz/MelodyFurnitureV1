@props([
    'steps' => [],
])

<div class="mb-8">

    <div class="flex items-center">

        @foreach($steps as $index => $label)

            @php
                $stepNumber = $index + 1;
                $isLast = $loop->last;
            @endphp

            <div class="flex items-center">

                {{-- Circle --}}
                <div
                    class="flex items-center gap-3">

                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-full border text-sm font-semibold transition-all"

                        :class="step >= {{ $stepNumber }}
                            ? 'border-blue-600 bg-blue-600 text-white'
                            : 'border-gray-300 bg-white text-gray-500'">

                        {{ $stepNumber }}

                    </div>

                    <div class="hidden md:block">

                        <p
                            class="text-sm font-medium"

                            :class="step >= {{ $stepNumber }}
                                ? 'text-blue-600'
                                : 'text-gray-500'">

                            {{ $label }}

                        </p>

                    </div>

                </div>

                {{-- Line --}}
                @unless($isLast)

                    <div
                        class="mx-4 h-[2px] w-12 bg-gray-200 md:w-20">

                    </div>

                @endunless

            </div>

        @endforeach

    </div>

</div>