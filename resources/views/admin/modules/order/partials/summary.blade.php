<x-admin.card>

    <x-admin.card-header
        title="Ringkasan Order"
        description="Informasi utama pesanan."
    >
    
            <div class="flex gap-2">

                <x-admin.badge
                    :color="$order->status === 'completed'
                        ? 'green'
                        : ($order->status === 'cancelled'
                            ? 'red'
                            : 'blue')"
                >
                    {{ strtoupper($order->status) }}
                </x-admin.badge>

                <x-admin.badge
                    :color="$order->payment_status === 'paid'
                        ? 'green'
                        : ($order->payment_status === 'expired'
                            ? 'red'
                            : 'yellow')"
                >
                    {{ strtoupper($order->payment_status) }}
                </x-admin.badge>

            </div>

        </div>

    </x-admin.card-header>

    <x-admin.card-body>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">

            <div>

                <p class="text-xs uppercase text-slate-500 mb-1">
                    Order Number
                </p>

                <p class="font-semibold">
                    {{ $order->order_number }}
                </p>

            </div>

            <div>

                <p class="text-xs uppercase text-slate-500 mb-1">
                    Tracking Token
                </p>

                <p class="font-mono text-sm break-all">
                    {{ $order->tracking_token }}
                </p>

            </div>

            <div>

                <p class="text-xs uppercase text-slate-500 mb-1">
                    Dibuat
                </p>

                <p>
                    {{ $order->created_at->format('d M Y H:i') }}
                </p>

            </div>

            <div>

                <p class="text-xs uppercase text-slate-500 mb-1">
                    Pembayaran
                </p>

                <p>
                    {{ optional($order->paid_at)?->format('d M Y H:i') ?? '-' }}
                </p>

            </div>

        </div>

        <div class="border-t mt-6 pt-6">

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">

                <div>

                    <p class="text-xs uppercase text-slate-500 mb-1">
                        Subtotal
                    </p>

                    <x-admin.price
                        :original="$order->total_product_price"
                    />

                </div>

                <div>

                    <p class="text-xs uppercase text-slate-500 mb-1">
                        Voucher
                    </p>

                    <x-admin.price
                        :original="$order->voucher_discount_amount"
                    />

                </div>

                <div>

                    <p class="text-xs uppercase text-slate-500 mb-1">
                        Ongkir
                    </p>

                    <x-admin.price
                        :original="$order->shipping_fee"
                    />

                </div>

                <div>

                    <p class="text-xs uppercase text-slate-500 mb-1">
                        Total
                    </p>

                    <x-admin.price
                        :original="$order->total_payment"
                    />

                </div>

            </div>

        </div>

    </x-admin.card-body>

</x-admin.card>