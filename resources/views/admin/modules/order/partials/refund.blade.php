@if($order->refund)

    @php
        $refund = $order->refund;

        $refundStatusColor = match ($refund->status) {
            'pending' => 'yellow',
            'processing' => 'blue',
            'completed' => 'green',
            'rejected' => 'red',
            default => 'gray',
        };

        $refundStatusLabel = match ($refund->status) {
            'pending' => 'Menunggu Proses',
            'processing' => 'Sedang Diproses',
            'completed' => 'Selesai',
            'rejected' => 'Ditolak',
            default => ucfirst($refund->status),
        };
    @endphp

    <x-admin.card>

        <x-admin.card-header
            title="Refund"
            description="Informasi dan proses pengembalian dana order."
        />

        <x-admin.card-body>

            <div class="space-y-5">

                {{-- Refund Number --}}
                <div>
                    <p class="mb-1 text-xs uppercase text-slate-500">
                        Refund Number
                    </p>

                    <p class="font-mono text-sm font-semibold text-slate-900">
                        {{ $refund->refund_number }}
                    </p>
                </div>


                {{-- Status --}}
                <div>
                    <p class="mb-1 text-xs uppercase text-slate-500">
                        Status Refund
                    </p>

                    <x-admin.badge :color="$refundStatusColor">
                        {{ $refundStatusLabel }}
                    </x-admin.badge>
                </div>


                {{-- Amount --}}
                <div>
                    <p class="mb-1 text-xs uppercase text-slate-500">
                        Jumlah Refund
                    </p>

                    <p class="text-xl font-bold text-slate-900">
                        Rp {{ number_format($refund->amount, 0, ',', '.') }}
                    </p>
                </div>


                {{-- Requested At --}}
                <div>
                    <p class="mb-1 text-xs uppercase text-slate-500">
                        Diajukan
                    </p>

                    <p class="text-sm text-slate-700">
                        {{ optional($refund->requested_at)->format('d M Y H:i') ?? '-' }}
                    </p>
                </div>


                {{-- Processed At --}}
                @if($refund->processed_at)
                    <div>
                        <p class="mb-1 text-xs uppercase text-slate-500">
                            Diproses
                        </p>

                        <p class="text-sm text-slate-700">
                            {{ $refund->processed_at->format('d M Y H:i') }}
                        </p>
                    </div>
                @endif


                {{-- Completed At --}}
                @if($refund->completed_at)
                    <div>
                        <p class="mb-1 text-xs uppercase text-slate-500">
                            Diselesaikan
                        </p>

                        <p class="text-sm text-slate-700">
                            {{ $refund->completed_at->format('d M Y H:i') }}
                        </p>
                    </div>
                @endif


                {{-- Processor --}}
                @if($refund->processor)
                    <div>
                        <p class="mb-1 text-xs uppercase text-slate-500">
                            Diproses Oleh
                        </p>

                        <p class="text-sm font-medium text-slate-700">
                            {{ $refund->processor->full_name
                                ?? $refund->processor->name
                                ?? '-' }}
                        </p>
                    </div>
                @endif


                {{-- Notes --}}
                @if($refund->notes)
                    <div>
                        <p class="mb-1 text-xs uppercase text-slate-500">
                            Catatan
                        </p>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-sm leading-6 text-slate-700">
                                {{ $refund->notes }}
                            </p>
                        </div>
                    </div>
                @endif


                {{-- Actions --}}
                @if(
                    $refund->status === 'pending'
                    || $refund->status === 'processing'
                )

                    <div class="border-t border-slate-200 pt-5">

                        <div class="flex flex-wrap gap-3">

                            {{-- Start --}}
                            @if($refund->status === 'pending')

                                <button
                                    type="button"
                                    id="refund-start"
                                    data-url="{{ route(
                                        'admin.refunds.start',
                                        $refund
                                    ) }}"
                                    class="
                                        inline-flex
                                        items-center
                                        justify-center
                                        rounded-lg
                                        bg-blue-600
                                        px-4
                                        py-2.5
                                        text-sm
                                        font-semibold
                                        text-white
                                        transition
                                        hover:bg-blue-700
                                        focus:outline-none
                                        focus:ring-2
                                        focus:ring-blue-200
                                        disabled:cursor-not-allowed
                                        disabled:opacity-50
                                    "
                                >
                                    Mulai Proses Refund
                                </button>


                                {{-- Reject --}}
                                <button
                                    type="button"
                                    id="refund-reject"
                                    data-url="{{ route(
                                        'admin.refunds.reject',
                                        $refund
                                    ) }}"
                                    class="
                                        inline-flex
                                        items-center
                                        justify-center
                                        rounded-lg
                                        border
                                        border-red-300
                                        bg-white
                                        px-4
                                        py-2.5
                                        text-sm
                                        font-semibold
                                        text-red-700
                                        transition
                                        hover:bg-red-50
                                        focus:outline-none
                                        focus:ring-2
                                        focus:ring-red-100
                                    "
                                >
                                    Tolak Refund
                                </button>

                            @endif


                            {{-- Complete --}}
                            @if($refund->status === 'processing')

                                <button
                                    type="button"
                                    id="refund-complete"
                                    data-url="{{ route(
                                        'admin.refunds.complete',
                                        $refund
                                    ) }}"
                                    class="
                                        inline-flex
                                        items-center
                                        justify-center
                                        rounded-lg
                                        bg-green-600
                                        px-4
                                        py-2.5
                                        text-sm
                                        font-semibold
                                        text-white
                                        transition
                                        hover:bg-green-700
                                        focus:outline-none
                                        focus:ring-2
                                        focus:ring-green-200
                                    "
                                >
                                    Selesaikan Refund
                                </button>

                            @endif

                        </div>

                    </div>

                @endif

            </div>

        </x-admin.card-body>

    </x-admin.card>

@endif

{{-- ================================================================
    COMPLETE REFUND MODAL
================================================================ --}}

@if(
    $order->refund
    && $order->refund->status === 'processing'
)

    <div
        id="refund-complete-modal"
        class="fixed inset-0 z-50 hidden"
        role="dialog"
        aria-modal="true"
        aria-labelledby="refund-complete-modal-title"
    >

        {{-- Overlay --}}

        <div
            id="refund-complete-overlay"
            class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm"
        ></div>


        {{-- Wrapper --}}

        <div
            class="
                relative
                flex
                min-h-full
                items-center
                justify-center
                p-4
            "
        >

            {{-- Modal --}}

            <div
                class="
                    relative
                    w-full
                    max-w-lg
                    overflow-hidden
                    rounded-2xl
                    bg-white
                    shadow-2xl
                "
            >

                {{-- Header --}}

                <div
                    class="
                        flex
                        items-start
                        justify-between
                        border-b
                        border-gray-200
                        px-6
                        py-5
                    "
                >

                    <div>

                        <h2
                            id="refund-complete-modal-title"
                            class="text-lg font-bold text-gray-900"
                        >
                            Selesaikan Refund
                        </h2>

                        <p class="mt-1 text-sm text-gray-500">
                            {{ $order->refund->refund_number }}
                        </p>

                    </div>


                    <button
                        type="button"
                        id="refund-complete-close"
                        class="
                            rounded-lg
                            p-2
                            text-gray-400
                            transition
                            hover:bg-gray-100
                            hover:text-gray-600
                        "
                        aria-label="Tutup"
                    >
                        <x-heroicon-o-x-mark class="h-5 w-5"/>
                    </button>

                </div>


                {{-- Body --}}

                <div class="space-y-5 px-6 py-6">

                    {{-- Confirmation --}}

                    <div
                        class="
                            rounded-xl
                            border
                            border-green-200
                            bg-green-50
                            p-4
                        "
                    >

                        <div class="flex gap-3">

                            <x-heroicon-o-check-circle
                                class="
                                    mt-0.5
                                    h-5
                                    w-5
                                    shrink-0
                                    text-green-600
                                "
                            />

                            <div>

                                <p
                                    class="
                                        text-sm
                                        font-semibold
                                        text-green-900
                                    "
                                >
                                    Konfirmasi Penyelesaian Refund
                                </p>

                                <p
                                    class="
                                        mt-1
                                        text-sm
                                        leading-6
                                        text-green-800
                                    "
                                >
                                    Pastikan proses pengembalian dana
                                    sudah selesai dilakukan sebelum
                                    menyelesaikan refund ini.
                                </p>

                            </div>

                        </div>

                    </div>


                    {{-- Refund Amount --}}

                    <div>

                        <p
                            class="
                                mb-1
                                text-xs
                                font-semibold
                                uppercase
                                tracking-wide
                                text-gray-500
                            "
                        >
                            Jumlah Refund
                        </p>

                        <p class="text-xl font-bold text-gray-900">
                            Rp {{ number_format(
                                $order->refund->amount,
                                0,
                                ',',
                                '.'
                            ) }}
                        </p>

                    </div>


                    {{-- Notes --}}

                    <div>

                        <label
                            for="refund-complete-notes"
                            class="
                                mb-2
                                block
                                text-sm
                                font-semibold
                                text-gray-700
                            "
                        >
                            Catatan
                            <span class="font-normal text-gray-400">
                                (Opsional)
                            </span>
                        </label>

                        <textarea
                            id="refund-complete-notes"
                            rows="4"
                            maxlength="1000"
                            placeholder="Tambahkan catatan penyelesaian refund jika diperlukan."
                            class="
                                block
                                w-full
                                resize-none
                                rounded-xl
                                border
                                border-gray-300
                                bg-white
                                px-4
                                py-3
                                text-sm
                                text-gray-900
                                placeholder:text-gray-400
                                transition
                                focus:border-green-500
                                focus:outline-none
                                focus:ring-2
                                focus:ring-green-100
                            "
                        ></textarea>


                        <p
                            id="refund-complete-error"
                            class="
                                mt-1
                                hidden
                                text-xs
                                font-medium
                                text-red-600
                            "
                        ></p>

                    </div>

                </div>


                {{-- Footer --}}

                <div
                    class="
                        flex
                        items-center
                        justify-end
                        gap-3
                        border-t
                        border-gray-200
                        bg-gray-50
                        px-6
                        py-4
                    "
                >

                    <button
                        type="button"
                        id="refund-complete-cancel"
                        class="
                            rounded-lg
                            border
                            border-gray-300
                            bg-white
                            px-4
                            py-2
                            text-sm
                            font-semibold
                            text-gray-700
                            transition
                            hover:bg-gray-100
                        "
                    >
                        Batal
                    </button>


                    <button
                        type="button"
                        id="refund-complete-confirm"
                        data-url="{{ route(
                            'admin.refunds.complete',
                            $order->refund
                        ) }}"
                        class="
                            inline-flex
                            items-center
                            justify-center
                            rounded-lg
                            bg-green-600
                            px-4
                            py-2
                            text-sm
                            font-semibold
                            text-white
                            transition
                            hover:bg-green-700
                            focus:outline-none
                            focus:ring-2
                            focus:ring-green-200
                            disabled:cursor-not-allowed
                            disabled:opacity-50
                        "
                    >
                        Selesaikan Refund
                    </button>

                </div>

            </div>

        </div>

    </div>

@endif

{{-- ================================================================
    REJECT REFUND MODAL
================================================================ --}}

@if(
    $order->refund
    && $order->refund->status === 'pending'
)

    <div
        id="refund-reject-modal"
        class="fixed inset-0 z-50 hidden"
        role="dialog"
        aria-modal="true"
        aria-labelledby="refund-reject-modal-title"
    >

        {{-- Overlay --}}
        <div
            id="refund-reject-overlay"
            class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm"
        ></div>


        {{-- Wrapper --}}
        <div
            class="
                relative
                flex
                min-h-full
                items-center
                justify-center
                p-4
            "
        >

            {{-- Modal --}}
            <div
                class="
                    relative
                    w-full
                    max-w-lg
                    overflow-hidden
                    rounded-2xl
                    bg-white
                    shadow-2xl
                "
            >

                {{-- Header --}}
                <div
                    class="
                        flex
                        items-start
                        justify-between
                        border-b
                        border-gray-200
                        px-6
                        py-5
                    "
                >

                    <div>

                        <h2
                            id="refund-reject-modal-title"
                            class="
                                text-lg
                                font-bold
                                text-gray-900
                            "
                        >
                            Tolak Refund
                        </h2>

                        <p class="mt-1 text-sm text-gray-500">
                            {{ $order->refund->refund_number }}
                        </p>

                    </div>


                    <button
                        type="button"
                        id="refund-reject-close"
                        class="
                            rounded-lg
                            p-2
                            text-gray-400
                            transition
                            hover:bg-gray-100
                            hover:text-gray-600
                        "
                        aria-label="Tutup"
                    >
                        <x-heroicon-o-x-mark class="h-5 w-5"/>
                    </button>

                </div>


                {{-- Body --}}
                <div class="space-y-5 px-6 py-6">

                    {{-- Warning --}}
                    <div
                        class="
                            rounded-xl
                            border
                            border-red-200
                            bg-red-50
                            p-4
                        "
                    >

                        <div class="flex gap-3">

                            <x-heroicon-o-exclamation-triangle
                                class="
                                    mt-0.5
                                    h-5
                                    w-5
                                    shrink-0
                                    text-red-600
                                "
                            />

                            <div>

                                <p
                                    class="
                                        text-sm
                                        font-semibold
                                        text-red-900
                                    "
                                >
                                    Konfirmasi Penolakan Refund
                                </p>

                                <p
                                    class="
                                        mt-1
                                        text-sm
                                        leading-6
                                        text-red-800
                                    "
                                >
                                    Refund akan ditolak dan tidak dapat
                                    diproses kembali dari status ini.
                                    Pastikan alasan penolakan sudah benar.
                                </p>

                            </div>

                        </div>

                    </div>


                    {{-- Refund Amount --}}
                    <div>

                        <p
                            class="
                                mb-1
                                text-xs
                                font-semibold
                                uppercase
                                tracking-wide
                                text-gray-500
                            "
                        >
                            Jumlah Refund
                        </p>

                        <p class="text-xl font-bold text-gray-900">
                            Rp {{ number_format(
                                $order->refund->amount,
                                0,
                                ',',
                                '.'
                            ) }}
                        </p>

                    </div>


                    {{-- Reject Reason --}}
                    <div>

                        <label
                            for="refund-reject-notes"
                            class="
                                mb-2
                                block
                                text-sm
                                font-semibold
                                text-gray-700
                            "
                        >
                            Alasan Penolakan
                            <span class="text-red-500">
                                *
                            </span>
                        </label>

                        <textarea
                            id="refund-reject-notes"
                            rows="4"
                            maxlength="1000"
                            placeholder="Masukkan alasan penolakan refund."
                            class="
                                block
                                w-full
                                resize-none
                                rounded-xl
                                border
                                border-gray-300
                                bg-white
                                px-4
                                py-3
                                text-sm
                                text-gray-900
                                placeholder:text-gray-400
                                transition
                                focus:border-red-500
                                focus:outline-none
                                focus:ring-2
                                focus:ring-red-100
                            "
                        ></textarea>

                        <p
                            id="refund-reject-error"
                            class="
                                mt-1
                                hidden
                                text-xs
                                font-medium
                                text-red-600
                            "
                        ></p>

                    </div>

                </div>


                {{-- Footer --}}
                <div
                    class="
                        flex
                        items-center
                        justify-end
                        gap-3
                        border-t
                        border-gray-200
                        bg-gray-50
                        px-6
                        py-4
                    "
                >

                    <button
                        type="button"
                        id="refund-reject-cancel"
                        class="
                            rounded-lg
                            border
                            border-gray-300
                            bg-white
                            px-4
                            py-2
                            text-sm
                            font-semibold
                            text-gray-700
                            transition
                            hover:bg-gray-100
                        "
                    >
                        Batal
                    </button>


                    <button
                        type="button"
                        id="refund-reject-confirm"
                        data-url="{{ route(
                            'admin.refunds.reject',
                            $order->refund
                        ) }}"
                        class="
                            inline-flex
                            items-center
                            justify-center
                            rounded-lg
                            bg-red-600
                            px-4
                            py-2
                            text-sm
                            font-semibold
                            text-white
                            transition
                            hover:bg-red-700
                            focus:outline-none
                            focus:ring-2
                            focus:ring-red-200
                            disabled:cursor-not-allowed
                            disabled:opacity-50
                        "
                    >
                        Tolak Refund
                    </button>

                </div>

            </div>

        </div>

    </div>

@endif

@push('scripts')
    <script>
        /*
        |--------------------------------------------------------------------------
        | Refund - Start Processing
        |--------------------------------------------------------------------------
        */
       document.addEventListener('DOMContentLoaded', () => {

            const csrfToken =
                document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute('content');

            if (!csrfToken) {
                console.error('CSRF token tidak ditemukan.');
                return;
            }
            const refundStartButton =
                document.getElementById('refund-start');


            refundStartButton?.addEventListener(
                'click',
                async () => {

                    const url =
                        refundStartButton.dataset.url;

                    if (!url) {
                        console.error(
                            'URL start refund tidak ditemukan.'
                        );

                        return;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Confirmation
                    |--------------------------------------------------------------------------
                    */

                    const confirmed = window.confirm(
                        'Apakah Anda yakin ingin memulai proses refund ini?'
                    );

                    if (!confirmed) {
                        return;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Loading
                    |--------------------------------------------------------------------------
                    */

                    const originalText =
                        refundStartButton.innerHTML;

                    refundStartButton.disabled = true;

                    refundStartButton.innerHTML = `
                        <svg
                            class="mr-2 h-4 w-4 animate-spin"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            ></circle>

                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                            ></path>
                        </svg>

                        Memproses...
                    `;


                    try {

                        /*
                        |--------------------------------------------------------------------------
                        | Request
                        |--------------------------------------------------------------------------
                        */

                        const response = await fetch(
                            url,
                            {
                                method: 'PATCH',

                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                            }
                        );


                        /*
                        |--------------------------------------------------------------------------
                        | Parse Response
                        |--------------------------------------------------------------------------
                        */

                        const contentType =
                            response.headers.get(
                                'content-type'
                            ) || '';

                        let data = null;

                        if (
                            contentType.includes(
                                'application/json'
                            )
                        ) {
                            data = await response.json();
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Error
                        |--------------------------------------------------------------------------
                        */

                        if (!response.ok) {

                            throw new Error(
                                data?.message
                                ?? 'Gagal memulai proses refund.'
                            );
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Success
                        |--------------------------------------------------------------------------
                        */

                        console.log(
                            'Refund berhasil dimulai:',
                            data
                        );


                        /*
                        |--------------------------------------------------------------------------
                        | Reload Detail Order
                        |--------------------------------------------------------------------------
                        */

                        window.location.reload();

                    } catch (error) {

                        console.error(
                            'Start refund error:',
                            error
                        );


                        /*
                        |--------------------------------------------------------------------------
                        | Restore Button
                        |--------------------------------------------------------------------------
                        */

                        refundStartButton.disabled = false;

                        refundStartButton.innerHTML =
                            originalText;


                        /*
                        |--------------------------------------------------------------------------
                        | Error Message
                        |--------------------------------------------------------------------------
                        */

                        window.alert(
                            error.message
                            ?? 'Terjadi kesalahan saat memulai proses refund.'
                        );

                    }

                }
            );

            /*
            |--------------------------------------------------------------------------
            | Refund - Complete
            |--------------------------------------------------------------------------
            */

            const refundCompleteButton =
                document.getElementById('refund-complete');

            const refundCompleteModal =
                document.getElementById('refund-complete-modal');

            const refundCompleteOverlay =
                document.getElementById('refund-complete-overlay');

            const refundCompleteClose =
                document.getElementById('refund-complete-close');

            const refundCompleteCancel =
                document.getElementById('refund-complete-cancel');

            const refundCompleteConfirm =
                document.getElementById('refund-complete-confirm');

            const refundCompleteNotes =
                document.getElementById('refund-complete-notes');

            const refundCompleteError =
                document.getElementById('refund-complete-error');

            const showModal = (modal) => {
                if (!modal) {
                    return;
                }

                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            };

            const hideModal = (modal) => {
                if (!modal) {
                    return;
                }

                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            };


            /*
            |--------------------------------------------------------------------------
            | Open Modal
            |--------------------------------------------------------------------------
            */

            const openRefundCompleteModal = () => {

                showModal(refundCompleteModal);

                requestAnimationFrame(() => {
                    refundCompleteNotes?.focus();
                });

            };


            /*
            |--------------------------------------------------------------------------
            | Close Modal
            |--------------------------------------------------------------------------
            */

            const closeRefundCompleteModal = () => {

                hideModal(refundCompleteModal);

                if (refundCompleteNotes) {
                    refundCompleteNotes.value = '';
                }

                if (refundCompleteError) {
                    refundCompleteError.textContent = '';
                    refundCompleteError.classList.add('hidden');
                }

            };


            /*
            |--------------------------------------------------------------------------
            | Modal Events
            |--------------------------------------------------------------------------
            */

            refundCompleteButton?.addEventListener(
                'click',
                openRefundCompleteModal
            );

            refundCompleteClose?.addEventListener(
                'click',
                closeRefundCompleteModal
            );

            refundCompleteCancel?.addEventListener(
                'click',
                closeRefundCompleteModal
            );

            refundCompleteOverlay?.addEventListener(
                'click',
                closeRefundCompleteModal
            );


            /*
            |--------------------------------------------------------------------------
            | Complete Refund Submit
            |--------------------------------------------------------------------------
            */

            refundCompleteConfirm?.addEventListener(
                'click',
                async () => {

                    /*
                    |--------------------------------------------------------------------------
                    | Reset Error
                    |--------------------------------------------------------------------------
                    */

                    if (refundCompleteError) {
                        refundCompleteError.textContent = '';
                        refundCompleteError.classList.add('hidden');
                    }


                    const url =
                        refundCompleteConfirm.dataset.url;

                    if (!url) {

                        console.error(
                            'URL complete refund tidak ditemukan.'
                        );

                        return;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Loading
                    |--------------------------------------------------------------------------
                    */

                    const originalText =
                        refundCompleteConfirm.innerHTML;

                    refundCompleteConfirm.disabled = true;

                    refundCompleteConfirm.innerHTML = `
                        <svg
                            class="mr-2 h-4 w-4 animate-spin"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            ></circle>

                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                            ></path>
                        </svg>

                        Memproses...
                    `;


                    try {

                        /*
                        |--------------------------------------------------------------------------
                        | Request
                        |--------------------------------------------------------------------------
                        */

                        const response = await fetch(
                            url,
                            {
                                method: 'PATCH',

                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'X-Requested-With': 'XMLHttpRequest',

                                    'Content-Type':
                                        'application/json',
                                },

                                body: JSON.stringify({
                                    notes:
                                        refundCompleteNotes?.value.trim()
                                        || null,
                                }),
                            }
                        );


                        /*
                        |--------------------------------------------------------------------------
                        | Parse Response
                        |--------------------------------------------------------------------------
                        */

                        const contentType =
                            response.headers.get(
                                'content-type'
                            ) || '';

                        let data = null;

                        if (
                            contentType.includes(
                                'application/json'
                            )
                        ) {
                            data = await response.json();
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Handle Error
                        |--------------------------------------------------------------------------
                        */

                        if (!response.ok) {

                            throw new Error(
                                data?.message
                                ?? 'Gagal menyelesaikan refund.'
                            );
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Success
                        |--------------------------------------------------------------------------
                        */

                        console.log(
                            'Refund berhasil diselesaikan:',
                            data
                        );


                        /*
                        |--------------------------------------------------------------------------
                        | Reload Detail Order
                        |--------------------------------------------------------------------------
                        */

                        window.location.reload();

                    } catch (error) {

                        console.error(
                            'Complete refund error:',
                            error
                        );


                        /*
                        |--------------------------------------------------------------------------
                        | Restore Button
                        |--------------------------------------------------------------------------
                        */

                        refundCompleteConfirm.disabled = false;

                        refundCompleteConfirm.innerHTML =
                            originalText;


                        /*
                        |--------------------------------------------------------------------------
                        | Show Error
                        |--------------------------------------------------------------------------
                        */

                        if (refundCompleteError) {

                            refundCompleteError.textContent =
                                error.message
                                ?? 'Terjadi kesalahan saat menyelesaikan refund.';

                            refundCompleteError.classList.remove(
                                'hidden'
                            );

                        }

                    }

                }
            );
            /*
            |--------------------------------------------------------------------------
            | Refund - Reject
            |--------------------------------------------------------------------------
            */

            const refundRejectButton =
                document.getElementById('refund-reject');

            const refundRejectModal =
                document.getElementById('refund-reject-modal');

            const refundRejectOverlay =
                document.getElementById('refund-reject-overlay');

            const refundRejectClose =
                document.getElementById('refund-reject-close');

            const refundRejectCancel =
                document.getElementById('refund-reject-cancel');

            const refundRejectConfirm =
                document.getElementById('refund-reject-confirm');

            const refundRejectNotes =
                document.getElementById('refund-reject-notes');

            const refundRejectError =
                document.getElementById('refund-reject-error');


            /*
            |--------------------------------------------------------------------------
            | Open Modal
            |--------------------------------------------------------------------------
            */

            const openRefundRejectModal = () => {

                /*
                |--------------------------------------------------------------------------
                | Reset State
                |--------------------------------------------------------------------------
                */

                if (refundRejectNotes) {
                    refundRejectNotes.value = '';
                }

                if (refundRejectError) {
                    refundRejectError.textContent = '';
                    refundRejectError.classList.add('hidden');
                }


                /*
                |--------------------------------------------------------------------------
                | Show Modal
                |--------------------------------------------------------------------------
                */

                if (typeof showModal === 'function') {
                    showModal(refundRejectModal);
                } else {
                    refundRejectModal?.classList.remove('hidden');
                }


                /*
                |--------------------------------------------------------------------------
                | Focus
                |--------------------------------------------------------------------------
                */

                requestAnimationFrame(() => {
                    refundRejectNotes?.focus();
                });
            };


            /*
            |--------------------------------------------------------------------------
            | Close Modal
            |--------------------------------------------------------------------------
            */

            const closeRefundRejectModal = () => {

                if (typeof hideModal === 'function') {
                    hideModal(refundRejectModal);
                } else {
                    refundRejectModal?.classList.add('hidden');
                }


                /*
                |--------------------------------------------------------------------------
                | Reset
                |--------------------------------------------------------------------------
                */

                if (refundRejectNotes) {
                    refundRejectNotes.value = '';
                }

                if (refundRejectError) {
                    refundRejectError.textContent = '';
                    refundRejectError.classList.add('hidden');
                }
            };


            /*
            |--------------------------------------------------------------------------
            | Modal Events
            |--------------------------------------------------------------------------
            */

            refundRejectButton?.addEventListener(
                'click',
                openRefundRejectModal
            );

            refundRejectClose?.addEventListener(
                'click',
                closeRefundRejectModal
            );

            refundRejectCancel?.addEventListener(
                'click',
                closeRefundRejectModal
            );

            refundRejectOverlay?.addEventListener(
                'click',
                closeRefundRejectModal
            );


            /*
            |--------------------------------------------------------------------------
            | Submit Reject Refund
            |--------------------------------------------------------------------------
            */

            refundRejectConfirm?.addEventListener(
                'click',
                async () => {

                    /*
                    |--------------------------------------------------------------------------
                    | Reset Error
                    |--------------------------------------------------------------------------
                    */

                    if (refundRejectError) {
                        refundRejectError.textContent = '';
                        refundRejectError.classList.add('hidden');
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Get URL
                    |--------------------------------------------------------------------------
                    */

                    const url =
                        refundRejectConfirm.dataset.url;

                    if (!url) {

                        console.error(
                            'URL reject refund tidak ditemukan.'
                        );

                        return;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Validate Notes
                    |--------------------------------------------------------------------------
                    */

                    const notes =
                        refundRejectNotes?.value.trim() ?? '';

                    if (!notes) {

                        if (refundRejectError) {

                            refundRejectError.textContent =
                                'Alasan penolakan refund wajib diisi.';

                            refundRejectError.classList.remove(
                                'hidden'
                            );

                        }

                        refundRejectNotes?.focus();

                        return;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Loading
                    |--------------------------------------------------------------------------
                    */

                    const originalText =
                        refundRejectConfirm.innerHTML;

                    refundRejectConfirm.disabled = true;

                    refundRejectConfirm.innerHTML = `
                        <svg
                            class="mr-2 h-4 w-4 animate-spin"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            ></circle>

                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                            ></path>
                        </svg>

                        Memproses...
                    `;


                    try {

                        /*
                        |--------------------------------------------------------------------------
                        | Request
                        |--------------------------------------------------------------------------
                        */

                        const response = await fetch(
                            url,
                            {
                                method: 'PATCH',

                                headers: {
                                    'Accept': 'application/json',

                                    'Content-Type':
                                        'application/json',

                                    'X-CSRF-TOKEN':
                                        csrfToken,

                                    'X-Requested-With':
                                        'XMLHttpRequest',
                                },

                                body: JSON.stringify({
                                    notes: notes,
                                }),
                            }
                        );


                        /*
                        |--------------------------------------------------------------------------
                        | Parse Response
                        |--------------------------------------------------------------------------
                        */

                        const contentType =
                            response.headers.get(
                                'content-type'
                            ) || '';

                        let data = null;

                        if (
                            contentType.includes(
                                'application/json'
                            )
                        ) {
                            data = await response.json();
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Handle Error
                        |--------------------------------------------------------------------------
                        */

                        if (!response.ok) {

                            throw new Error(
                                data?.message
                                ?? 'Gagal menolak refund.'
                            );
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Success
                        |--------------------------------------------------------------------------
                        */

                        console.log(
                            'Refund berhasil ditolak:',
                            data
                        );


                        /*
                        |--------------------------------------------------------------------------
                        | Reload Detail Order
                        |--------------------------------------------------------------------------
                        */

                        window.location.reload();

                    } catch (error) {

                        console.error(
                            'Reject refund error:',
                            error
                        );


                        /*
                        |--------------------------------------------------------------------------
                        | Restore Button
                        |--------------------------------------------------------------------------
                        */

                        refundRejectConfirm.disabled = false;

                        refundRejectConfirm.innerHTML =
                            originalText;


                        /*
                        |--------------------------------------------------------------------------
                        | Show Error
                        |--------------------------------------------------------------------------
                        */

                        if (refundRejectError) {

                            refundRejectError.textContent =
                                error.message
                                ?? 'Terjadi kesalahan saat menolak refund.';

                            refundRejectError.classList.remove(
                                'hidden'
                            );
                        }
                    }

                }
            );

       });
    </script>

@endpush