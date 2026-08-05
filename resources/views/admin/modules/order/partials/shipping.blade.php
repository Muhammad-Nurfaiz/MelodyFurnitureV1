<x-admin.card>

    <x-admin.card-header
        title="Shipping"
        description="Informasi pengiriman."
    >
    </x-admin.card-header>

    <x-admin.card-body>

        @php
            $shipment = $order->shipment;
            $address = $order->shipping_address ?? [];
        @endphp

        <div class="space-y-5">

            {{-- Courier --}}
            <div>

                <p class="text-xs uppercase text-slate-500 mb-1">
                    Courier
                </p>

                <p class="font-medium uppercase">
                    {{ $order->courier ?? '-' }}
                </p>

            </div>

            {{-- Service --}}
            <div>

                <p class="text-xs uppercase text-slate-500 mb-1">
                    Service
                </p>

                <p>
                    {{ $order->shipping_method ?? '-' }}
                </p>

            </div>

            {{-- Total Weight --}}
            <div>

                <p class="text-xs uppercase text-slate-500 mb-1">
                    Total Weight
                </p>

                <p>
                    {{ number_format($order->total_weight, 2) }} Kg
                </p>

            </div>

            {{-- Tracking Number --}}
            <div>

                <p class="text-xs uppercase text-slate-500 mb-1">
                    Tracking Number
                </p>

                <p class="font-mono">
                    {{ $order->tracking_number ?? '-' }}
                </p>

            </div>

            {{-- Shipment Status --}}
            <div>

                <p class="text-xs uppercase text-slate-500 mb-1">
                    Shipment Status
                </p>

                @if($shipment)

                    <x-admin.badge
                        :color="$shipment->status === 'delivered'
                            ? 'green'
                            : ($shipment->status === 'cancelled'
                                ? 'red'
                                : 'blue')"
                    >
                        {{ strtoupper($shipment->status) }}
                    </x-admin.badge>

                @else

                    <x-admin.badge color="gray">
                        BELUM DIBUAT
                    </x-admin.badge>

                @endif

            </div>

            <hr>

            {{-- Recipient --}}
            <div>

                <p class="text-xs uppercase text-slate-500 mb-2">
                    Recipient
                </p>

                <div class="space-y-1">

                    <p class="font-medium">
                        {{ $address['recipient_name'] ?? '-' }}
                    </p>

                    <p>
                        {{ $address['phone'] ?? '-' }}
                    </p>

                </div>

            </div>

            {{-- Address --}}
            <div>

                <p class="text-xs uppercase text-slate-500 mb-2">
                    Shipping Address
                </p>

                <div class="space-y-1 text-sm">

                    <p>
                        {{ $address['address'] ?? '-' }}
                    </p>

                    <p>
                        {{ $address['city'] ?? '-' }},
                        {{ $address['province'] ?? '-' }}
                    </p>

                    <p>
                        {{ $address['postal_code'] ?? '-' }}
                    </p>

                </div>

            </div>

        </div>

    </x-admin.card-body>

</x-admin.card>