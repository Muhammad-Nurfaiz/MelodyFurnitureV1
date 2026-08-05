@php

$paymentColor = match ($order->payment_status) {

    'pending' => 'yellow',

    'paid' => 'green',

    'expired' => 'red',

    'failed' => 'red',

    'cancelled' => 'red',

    'refund' => 'blue',

    default => 'gray',

};

$paymentLabel = match ($order->payment_status) {

    'pending' => 'Pending',

    'paid' => 'Paid',

    'expired' => 'Expired',

    'failed' => 'Failed',

    'cancelled' => 'Cancelled',

    'refund' => 'Refund',

    default => ucfirst($order->payment_status),

};

@endphp

<x-admin.card>

    <x-admin.card-header
        title="Payment"
        description="Informasi pembayaran."
    >

    </x-admin.card-header>

    <x-admin.card-body>

        @php
            $payment = $order->payment;
        @endphp

        <div class="space-y-5">

            <div>

                <p class="text-xs uppercase text-slate-500 mb-1">
                    Payment Type
                </p>

                <p class="font-medium">
                    {{ $payment?->payment_type ?? '-' }}
                </p>

            </div>

            <div>

                <p class="text-xs uppercase text-slate-500 mb-1">
                    Transaction Status
                </p>

                <x-admin.badge
                    :color="$paymentColor"
                >
                    {{ $paymentLabel }}
                </x-admin.badge>

            </div>

            <div>

                <p class="text-xs uppercase text-slate-500 mb-1">
                    Transaction ID
                </p>

                <p class="font-mono text-sm break-all">
                    {{ $payment?->transaction_id ?? '-' }}
                </p>

            </div>

            <div>

                <p class="text-xs uppercase text-slate-500 mb-1">
                    Bank
                </p>

                <p>
                    {{ strtoupper($payment?->bank ?? '-') }}
                </p>

            </div>

            <div>

                <p class="text-xs uppercase text-slate-500 mb-1">
                    Virtual Account
                </p>

                <p class="font-mono">
                    {{ $payment?->va_number ?? '-' }}
                </p>

            </div>

            <hr>

            <div>

                <p class="text-xs uppercase text-slate-500 mb-1">
                    Snap Token
                </p>

                <p class="font-mono text-xs break-all">
                    {{ $payment?->snap_token ?? '-' }}
                </p>

            </div>

            <div>

                <p class="text-xs uppercase text-slate-500 mb-1">
                    Expired At
                </p>

                <p>
                    {{ optional($order->payment_expired_at)->format('d M Y H:i') ?? '-' }}
                </p>

            </div>

            <div>

                <p class="text-xs uppercase text-slate-500 mb-1">
                    Paid At
                </p>

                <p>
                    {{ optional($payment?->paid_at)->format('d M Y H:i') ?? '-' }}
                </p>

            </div>

        </div>

    </x-admin.card-body>

</x-admin.card>