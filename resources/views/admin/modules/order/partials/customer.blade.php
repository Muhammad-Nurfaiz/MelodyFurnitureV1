<x-admin.card>

    <x-admin.card-header
        title="Customer"
        description="Informasi customer."
    >
    </x-admin.card-header>

    <x-admin.card-body>

        <div class="space-y-5">

            <div>

                <p class="text-xs uppercase text-slate-500 mb-1">
                    Nama
                </p>

                <p class="font-semibold">
                    {{ $order->customer->name }}
                </p>

            </div>

            <div>

                <p class="text-xs uppercase text-slate-500 mb-1">
                    Email
                </p>

                <p>
                    {{ $order->customer->email }}
                </p>

            </div>

            <div>

                <p class="text-xs uppercase text-slate-500 mb-1">
                    Nomor Telepon
                </p>

                <p>
                    {{ $order->customer->phone ?? '-' }}
                </p>

            </div>

            <hr>

            <div>

                <p class="text-xs uppercase text-slate-500 mb-2">
                    Alamat Pengiriman
                </p>

                @php
                    $address = $order->shipping_address ?? [];
                @endphp

                <div class="space-y-1 text-sm">

                    <p class="font-medium">
                        {{ $address['recipient_name'] ?? '-' }}
                    </p>

                    <p>
                        {{ $address['phone'] ?? '-' }}
                    </p>

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