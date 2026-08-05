<x-admin.card>
<x-admin.card-header
    title="Order Timeline"
    description="Riwayat perubahan status dan aktivitas penting pada pesanan."
/>

<x-admin.card-body>

    @if($order->statusHistories->isEmpty())

        <x-admin.empty-state
            title="Belum ada riwayat."
            description="Timeline order masih kosong."
        />

    @else

        @php
            /*
            |--------------------------------------------------------------------------
            | Status Label
            |--------------------------------------------------------------------------
            */

            $statusLabels = [
                'pending' => 'Menunggu Pembayaran',
                'paid' => 'Dibayar',
                'processing' => 'Diproses',
                'picked_up' => 'Diambil Kurir',
                'shipped' => 'Dikirim',
                'completed' => 'Selesai',
                'req_cancel' => 'Permintaan Pembatalan',
                'cancelled' => 'Dibatalkan',
            ];

            /*
            |--------------------------------------------------------------------------
            | Status Color
            |--------------------------------------------------------------------------
            */

            $statusColors = [
                'pending' => 'bg-gray-400',
                'paid' => 'bg-emerald-500',
                'processing' => 'bg-blue-500',
                'picked_up' => 'bg-indigo-500',
                'shipped' => 'bg-cyan-500',
                'completed' => 'bg-green-600',
                'req_cancel' => 'bg-amber-500',
                'cancelled' => 'bg-red-500',
            ];

            /*
            |--------------------------------------------------------------------------
            | Cancellation Request
            |--------------------------------------------------------------------------
            */

            $cancellationRequest = $order->cancellationRequest;
            $refund = $order->refund;
            $cancellationStatusLabels = [
                'pending' => 'Menunggu Keputusan Admin',
                'approved' => 'Disetujui Admin',
                'rejected' => 'Ditolak Admin',
            ];

            $cancellationStatusColors = [
                'pending' => 'warning',
                'approved' => 'success',
                'rejected' => 'danger',
            ];

            $refundStatusLabels = [
                'pending' => 'Refund Diajukan',
                'processing' => 'Refund Diproses',
                'completed' => 'Refund Selesai',
                'rejected' => 'Refund Ditolak',
            ];

            $refundStatusColors = [
                'pending' => 'warning',
                'processing' => 'primary',
                'completed' => 'success',
                'rejected' => 'danger',
            ];
        @endphp


        <div class="space-y-0">

            @foreach($order->statusHistories as $history)

                @php
                    $statusLabel =
                        $statusLabels[$history->status]
                        ?? ucwords(str_replace('_', ' ', $history->status));

                    $statusColor =
                        $statusColors[$history->status]
                        ?? 'bg-slate-400';
                @endphp

                <div class="flex gap-4">

                    {{-- =====================================================
                        TIMELINE DOT
                    ====================================================== --}}

                    <div class="flex flex-col items-center">

                        <div
                            class="
                                h-4
                                w-4
                                shrink-0
                                rounded-full
                                ring-4
                                ring-white
                                {{ $statusColor }}
                            "
                        ></div>

                        @if(!$loop->last)

                            <div
                                class="
                                    mt-1
                                    w-px
                                    flex-1
                                    bg-slate-300
                                "
                            ></div>

                        @endif

                    </div>


                    {{-- =====================================================
                        TIMELINE CONTENT
                    ====================================================== --}}

                    <div class="flex-1 pb-8">

                        {{-- Status + Date --}}

                        <div class="flex flex-wrap items-center gap-3">

                            <x-admin.badge
                                :color="
                                    $history->status === 'req_cancel'
                                        ? 'warning'
                                        : (
                                            $history->status === 'cancelled'
                                                ? 'danger'
                                                : 'gray'
                                        )
                                "
                            >
                                {{ $statusLabel }}
                            </x-admin.badge>

                            <span class="text-xs text-slate-500">

                                {{ $history->created_at->format('d M Y H:i') }}

                            </span>

                        </div>


                        {{-- Description --}}

                        @if($history->description)

                            <p class="mt-2 text-sm leading-6 text-slate-700">

                                {{ $history->description }}

                            </p>

                        @endif


                        {{-- Actor --}}

                        <p class="mt-1 text-xs text-slate-500">

                            Actor:

                            {{ ucfirst($history->actor) }}

                            @if($history->admin)

                                • {{ $history->admin->full_name }}

                            @endif

                        </p>


                        {{-- =================================================
                            CUSTOMER CANCELLATION REQUEST
                        ================================================== --}}

                        @if(
                            $history->status === 'req_cancel'
                            && $cancellationRequest
                        )

                            <div
                                class="
                                    mt-4
                                    rounded-xl
                                    border
                                    border-amber-200
                                    bg-amber-50
                                    p-4
                                "
                            >

                                {{-- Header --}}

                                <div
                                    class="
                                        flex
                                        flex-wrap
                                        items-center
                                        justify-between
                                        gap-2
                                    "
                                >

                                    <div class="flex items-center gap-2">

                                        <x-heroicon-o-exclamation-triangle
                                            class="h-5 w-5 text-amber-600"
                                        />

                                        <p
                                            class="
                                                text-sm
                                                font-semibold
                                                text-amber-900
                                            "
                                        >
                                            Permintaan Pembatalan Customer
                                        </p>

                                    </div>


                                    @php
                                        $requestStatus =
                                            $cancellationRequest->status;

                                        $requestStatusLabel =
                                            $cancellationStatusLabels[
                                                $requestStatus
                                            ]
                                            ?? ucwords(
                                                str_replace(
                                                    '_',
                                                    ' ',
                                                    $requestStatus
                                                )
                                            );

                                        $requestStatusColor =
                                            $cancellationStatusColors[
                                                $requestStatus
                                            ]
                                            ?? 'gray';
                                    @endphp

                                    <x-admin.badge
                                        :color="$requestStatusColor"
                                    >
                                        {{ $requestStatusLabel }}
                                    </x-admin.badge>

                                </div>


                                {{-- Reason --}}

                                <div class="mt-4">

                                    <p
                                        class="
                                            text-xs
                                            font-semibold
                                            uppercase
                                            tracking-wide
                                            text-amber-800
                                        "
                                    >
                                        Alasan Pembatalan
                                    </p>

                                    <div
                                        class="
                                            mt-2
                                            rounded-lg
                                            border
                                            border-amber-200
                                            bg-white
                                            p-3
                                        "
                                    >

                                        <p
                                            class="
                                                text-sm
                                                leading-6
                                                text-slate-700
                                            "
                                        >
                                            {{ $cancellationRequest->reason }}
                                        </p>

                                    </div>

                                </div>


                                {{-- Previous Status --}}

                                @if($cancellationRequest->previous_status)

                                    @php
                                        $previousStatusLabel =
                                            $statusLabels[
                                                $cancellationRequest
                                                    ->previous_status
                                            ]
                                            ?? ucwords(
                                                str_replace(
                                                    '_',
                                                    ' ',
                                                    $cancellationRequest
                                                        ->previous_status
                                                )
                                            );
                                    @endphp

                                    <div class="mt-3">

                                        <p class="text-xs text-amber-800">

                                            Status sebelum permintaan:

                                            <span class="font-semibold">
                                                {{ $previousStatusLabel }}
                                            </span>

                                        </p>

                                    </div>

                                @endif


                                {{-- =================================================
                                    ADMIN DECISION
                                ================================================== --}}

                                @if(
                                    $cancellationRequest->status !== 'pending'
                                )

                                    <div
                                        class="
                                            mt-4
                                            border-t
                                            border-amber-200
                                            pt-4
                                        "
                                    >

                                        <p
                                            class="
                                                text-xs
                                                font-semibold
                                                uppercase
                                                tracking-wide
                                                text-amber-800
                                            "
                                        >
                                            Keputusan Admin
                                        </p>


                                        @if(
                                            $cancellationRequest->admin_notes
                                        )

                                            <div class="mt-2">

                                                <p class="text-xs text-slate-500">
                                                    Catatan Admin
                                                </p>

                                                <p
                                                    class="
                                                        mt-1
                                                        text-sm
                                                        leading-6
                                                        text-slate-700
                                                    "
                                                >
                                                    {{ $cancellationRequest->admin_notes }}
                                                </p>

                                            </div>

                                        @endif


                                        <div
                                            class="
                                                mt-2
                                                flex
                                                flex-wrap
                                                items-center
                                                gap-x-3
                                                gap-y-1
                                                text-xs
                                                text-slate-500
                                            "
                                        >

                                            @if($cancellationRequest->approver)

                                                <span>

                                                    Diproses oleh:

                                                    <span
                                                        class="
                                                            font-medium
                                                            text-slate-700
                                                        "
                                                    >
                                                        {{ $cancellationRequest->approver->full_name }}
                                                    </span>

                                                </span>

                                            @endif


                                            @if($cancellationRequest->approved_at)

                                                <span>

                                                    •

                                                    {{
                                                        $cancellationRequest
                                                            ->approved_at
                                                            ->format(
                                                                'd M Y H:i'
                                                            )
                                                    }}

                                                </span>

                                            @endif

                                        </div>

                                    </div>

                                @endif

                            </div>

                        @endif

                    </div>

                </div>

            @endforeach

            @if($refund)

                @php
                    $refundStatusLabel =
                        $refundStatusLabels[$refund->status]
                        ?? ucwords(
                            str_replace('_', ' ', $refund->status)
                        );

                    $refundStatusColor =
                        $refundStatusColors[$refund->status]
                        ?? 'gray';

                    /*
                    |--------------------------------------------------------------------------
                    | Refund Activity Time
                    |--------------------------------------------------------------------------
                    */

                    $refundActivityAt = match ($refund->status) {
                        'pending' => $refund->requested_at,
                        'processing' => $refund->processed_at ?? $refund->requested_at,
                        'completed' => $refund->completed_at ?? $refund->processed_at ?? $refund->requested_at,
                        'rejected' => $refund->processed_at ?? $refund->requested_at,
                        default => $refund->updated_at ?? $refund->created_at,
                    };
                @endphp

                <div class="flex gap-4">

                    {{-- =====================================================
                        REFUND TIMELINE DOT
                    ====================================================== --}}

                    <div class="flex flex-col items-center">

                        <div
                            class="
                                h-4
                                w-4
                                shrink-0
                                rounded-full
                                ring-4
                                ring-white
                                bg-purple-500
                            "
                        ></div>

                    </div>


                    {{-- =====================================================
                        REFUND TIMELINE CONTENT
                    ====================================================== --}}

                    <div class="flex-1 pb-8">

                        {{-- Status + Date --}}

                        <div class="flex flex-wrap items-center gap-3">

                            <x-admin.badge :color="$refundStatusColor">
                                {{ $refundStatusLabel }}
                            </x-admin.badge>

                            <span class="text-xs text-slate-500">
                                {{ $refundActivityAt?->format('d M Y H:i') }}
                            </span>

                        </div>


                        {{-- Refund Number --}}

                        <p class="mt-2 text-sm font-semibold text-slate-800">
                            {{ $refund->refund_number }}
                        </p>


                        {{-- Amount --}}

                        <p class="mt-1 text-sm text-slate-600">
                            Jumlah refund:
                            <span class="font-semibold text-slate-800">
                                Rp {{ number_format((float) $refund->amount, 0, ',', '.') }}
                            </span>
                        </p>


                        {{-- Processing Admin --}}

                        @if($refund->processor)

                            <p class="mt-1 text-xs text-slate-500">

                                Diproses oleh:

                                <span class="font-medium text-slate-700">
                                    {{ $refund->processor->full_name }}
                                </span>

                            </p>

                        @endif


                        {{-- Completed --}}

                        @if($refund->status === 'completed')

                            @if($refund->completed_at)

                                <p class="mt-1 text-xs text-slate-500">
                                    Selesai pada:
                                    {{ $refund->completed_at->format('d M Y H:i') }}
                                </p>

                            @endif

                        @endif


                        {{-- Rejected --}}

                        @if($refund->status === 'rejected' && $refund->notes)

                            <div
                                class="
                                    mt-4
                                    rounded-xl
                                    border
                                    border-red-200
                                    bg-red-50
                                    p-4
                                "
                            >

                                <p
                                    class="
                                        text-xs
                                        font-semibold
                                        uppercase
                                        tracking-wide
                                        text-red-800
                                    "
                                >
                                    Alasan Penolakan Refund
                                </p>

                                <div
                                    class="
                                        mt-2
                                        rounded-lg
                                        border
                                        border-red-200
                                        bg-white
                                        p-3
                                    "
                                >

                                    <p
                                        class="
                                            text-sm
                                            leading-6
                                            text-slate-700
                                        "
                                    >
                                        {{ $refund->notes }}
                                    </p>

                                </div>

                            </div>

                        @endif


                        {{-- Complete Notes --}}

                        @if(
                            $refund->status === 'completed'
                            && $refund->notes
                        )

                            <div
                                class="
                                    mt-4
                                    rounded-xl
                                    border
                                    border-green-200
                                    bg-green-50
                                    p-4
                                "
                            >

                                <p
                                    class="
                                        text-xs
                                        font-semibold
                                        uppercase
                                        tracking-wide
                                        text-green-800
                                    "
                                >
                                    Catatan Refund
                                </p>

                                <p
                                    class="
                                        mt-2
                                        text-sm
                                        leading-6
                                        text-slate-700
                                    "
                                >
                                    {{ $refund->notes }}
                                </p>

                            </div>

                        @endif

                    </div>

                </div>

            @endif

        </div>

    @endif

</x-admin.card-body>
</x-admin.card>