<x-admin.card>
<x-admin.card-header
    title="Action"
    description="Workflow dan tindakan untuk pesanan ini."
/>

<x-admin.card-body>

    @php
        $action = $order->action;
        $cancellationRequest = $order->cancellationRequest;
    @endphp

    <div class="flex flex-wrap items-center gap-3">

        {{-- =====================================================
            WORKFLOW ACTION
        ====================================================== --}}

        @if($action['route'])

            <x-admin.button
                type="button"
                :variant="$action['type']"
                id="workflow-action"
                :data-url="$action['route']"
                :data-method="$action['method']"
            >
                {{ $action['label'] }}
            </x-admin.button>

        @else

            <x-admin.badge
                :color="$action['type']"
            >
                {{ $action['label'] }}
            </x-admin.badge>

        @endif


        {{-- =====================================================
            CANCELLATION REQUEST ACTION
        ====================================================== --}}

        @if(
            $order->status === 'req_cancel'
            && $cancellationRequest
            && $cancellationRequest->status === 'pending'
        )

            {{-- Reject --}}

            <button
                type="button"
                id="reject-cancellation"
                class="
                    inline-flex
                    items-center
                    justify-center
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
                    hover:bg-gray-50
                    focus:outline-none
                    focus:ring-2
                    focus:ring-gray-200
                "
            >
                <x-heroicon-o-x-mark class="mr-2 h-4 w-4"/>

                Tolak Pembatalan
            </button>


            {{-- Approve --}}

            <button
                type="button"
                id="approve-cancellation"
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
                "
            >
                <x-heroicon-o-check class="mr-2 h-4 w-4"/>

                Setujui Pembatalan
            </button>

        @endif


        {{-- =====================================================
            ADMIN CANCEL
        ====================================================== --}}

        @if(
            in_array(
                $order->status,
                ['pending', 'paid', 'processing'],
                true
            )
        )

            <button
                type="button"
                id="admin-cancel-order"
                class="
                    inline-flex
                    items-center
                    justify-center
                    rounded-lg
                    border
                    border-red-200
                    bg-white
                    px-4
                    py-2
                    text-sm
                    font-semibold
                    text-red-600
                    transition
                    hover:bg-red-50
                    focus:outline-none
                    focus:ring-2
                    focus:ring-red-100
                "
            >
                Batalkan Order
            </button>

        @endif

    </div>

</x-admin.card-body>

</x-admin.card>

{{-- ================================================================
CUSTOMER CANCELLATION REQUEST
================================================================ --}}

@if(
    $order->status === 'req_cancel'
    && $cancellationRequest
    && $cancellationRequest->status === 'pending'
)

{{-- =============================================================
    APPROVE MODAL
============================================================= --}}

<div
    id="approve-cancellation-modal"
    class="fixed inset-0 z-50 hidden"
    role="dialog"
    aria-modal="true"
    aria-labelledby="approve-cancellation-modal-title"
>

    {{-- Overlay --}}

    <div
        id="approve-cancellation-overlay"
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
                        id="approve-cancellation-modal-title"
                        class="text-lg font-bold text-gray-900"
                    >
                        Setujui Pembatalan
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        {{ $order->order_number }}
                    </p>

                </div>


                <button
                    type="button"
                    id="approve-cancellation-close"
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
                            class="mt-0.5 h-5 w-5 shrink-0 text-red-500"
                        />

                        <div>

                            <p class="text-sm font-semibold text-red-800">
                                Konfirmasi Pembatalan
                            </p>

                            <p class="mt-1 text-sm leading-6 text-red-700">

                                Permintaan pembatalan customer akan
                                disetujui. Order akan menjadi
                                <strong>dibatalkan</strong>, stok produk
                                akan dikembalikan ke inventory, dan refund
                                akan dibuat untuk pembayaran yang telah
                                diterima.

                            </p>

                        </div>

                    </div>

                </div>


                {{-- Customer Reason --}}

                <div>

                    <p
                        class="
                            text-xs
                            font-semibold
                            uppercase
                            tracking-wide
                            text-gray-500
                        "
                    >
                        Alasan Customer
                    </p>

                    <div
                        class="
                            mt-2
                            rounded-xl
                            border
                            border-gray-200
                            bg-gray-50
                            p-4
                        "
                    >

                        <p
                            class="
                                text-sm
                                leading-6
                                text-gray-700
                            "
                        >
                            {{ $cancellationRequest->reason }}
                        </p>

                    </div>

                </div>


                {{-- Admin Notes --}}

                <div>

                    <label
                        for="approve-cancellation-notes"
                        class="
                            mb-2
                            block
                            text-sm
                            font-semibold
                            text-gray-700
                        "
                    >
                        Catatan Admin
                        <span class="font-normal text-gray-400">
                            (Opsional)
                        </span>
                    </label>

                    <textarea
                        id="approve-cancellation-notes"
                        rows="3"
                        maxlength="1000"
                        placeholder="Tambahkan catatan jika diperlukan."
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
                        id="approve-cancellation-error"
                        class="mt-1 hidden text-xs font-medium text-red-600"
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
                    id="approve-cancellation-cancel"
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
                    id="approve-cancellation-confirm"
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
                    Setujui Pembatalan
                </button>

            </div>

        </div>

    </div>

</div>


{{-- =============================================================
    REJECT MODAL
============================================================= --}}

<div
    id="reject-cancellation-modal"
    class="fixed inset-0 z-50 hidden"
    role="dialog"
    aria-modal="true"
    aria-labelledby="reject-cancellation-modal-title"
>

    {{-- Overlay --}}

    <div
        id="reject-cancellation-overlay"
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
                        id="reject-cancellation-modal-title"
                        class="text-lg font-bold text-gray-900"
                    >
                        Tolak Pembatalan
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        {{ $order->order_number }}
                    </p>

                </div>


                <button
                    type="button"
                    id="reject-cancellation-close"
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

                {{-- Information --}}

                <div
                    class="
                        rounded-xl
                        border
                        border-amber-200
                        bg-amber-50
                        p-4
                    "
                >

                    <div class="flex gap-3">

                        <x-heroicon-o-information-circle
                            class="mt-0.5 h-5 w-5 shrink-0 text-amber-600"
                        />

                        <div>

                            <p class="text-sm font-semibold text-amber-900">
                                Permintaan Pembatalan
                            </p>

                            <p class="mt-1 text-sm leading-6 text-amber-800">

                                Jika ditolak, order akan dikembalikan ke
                                status sebelum customer mengajukan
                                pembatalan.

                            </p>

                        </div>

                    </div>

                </div>


                {{-- Customer Reason --}}

                <div>

                    <p
                        class="
                            text-xs
                            font-semibold
                            uppercase
                            tracking-wide
                            text-gray-500
                        "
                    >
                        Alasan Customer
                    </p>

                    <div
                        class="
                            mt-2
                            rounded-xl
                            border
                            border-gray-200
                            bg-gray-50
                            p-4
                        "
                    >

                        <p
                            class="
                                text-sm
                                leading-6
                                text-gray-700
                            "
                        >
                            {{ $cancellationRequest->reason }}
                        </p>

                    </div>

                </div>


                {{-- Admin Notes --}}

                <div>

                    <label
                        for="reject-cancellation-notes"
                        class="
                            mb-2
                            block
                            text-sm
                            font-semibold
                            text-gray-700
                        "
                    >
                        Alasan / Catatan Penolakan
                        <span class="text-red-500">*</span>
                    </label>

                    <textarea
                        id="reject-cancellation-notes"
                        rows="4"
                        maxlength="1000"
                        placeholder="Contoh: Pesanan sudah diproses dan tidak dapat dibatalkan."
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
                            focus:border-gray-500
                            focus:outline-none
                            focus:ring-2
                            focus:ring-gray-200
                        "
                    ></textarea>

                    <p
                        id="reject-cancellation-error"
                        class="mt-1 hidden text-xs font-medium text-red-600"
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
                    id="reject-cancellation-cancel"
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
                    id="reject-cancellation-confirm"
                    class="
                        inline-flex
                        items-center
                        justify-center
                        rounded-lg
                        bg-gray-800
                        px-4
                        py-2
                        text-sm
                        font-semibold
                        text-white
                        transition
                        hover:bg-gray-900
                        focus:outline-none
                        focus:ring-2
                        focus:ring-gray-200
                        disabled:cursor-not-allowed
                        disabled:opacity-50
                    "
                >
                    Tolak Pembatalan
                </button>

            </div>

        </div>

    </div>

</div>

@endif

{{-- ================================================================
ADMIN CANCEL MODAL
================================================================ --}}

@if(
in_array(
$order->status,
['pending', 'paid', 'processing'],
true
)
)

<div
    id="admin-cancel-modal"
    class="fixed inset-0 z-50 hidden"
    aria-labelledby="admin-cancel-modal-title"
    role="dialog"
    aria-modal="true"
>


{{-- Overlay --}}

<div
    id="admin-cancel-overlay"
    class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm"
></div>


{{-- Modal Wrapper --}}

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
                    id="admin-cancel-modal-title"
                    class="text-lg font-bold text-gray-900"
                >
                    Batalkan Order
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    {{ $order->order_number }}
                </p>

            </div>


            {{-- Close --}}

            <button
                type="button"
                id="admin-cancel-close"
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
                        class="mt-0.5 h-5 w-5 shrink-0 text-red-500"
                    />

                    <div>

                        <p class="text-sm font-semibold text-red-800">
                            Perhatian
                        </p>

                        <p class="mt-1 text-sm leading-6 text-red-700">

                            Order akan dibatalkan dan stok produk akan
                            dikembalikan ke inventory.

                            @if($order->payment_status === 'paid')
                                Pembayaran yang sudah diterima juga akan
                                diproses untuk refund.
                            @endif

                        </p>

                    </div>

                </div>

            </div>


            {{-- Reason --}}

            <div>

                <label
                    for="admin-cancel-reason"
                    class="mb-2 block text-sm font-semibold text-gray-700"
                >
                    Alasan Pembatalan
                    <span class="text-red-500">*</span>
                </label>

                <textarea
                    id="admin-cancel-reason"
                    rows="4"
                    maxlength="500"
                    placeholder="Contoh: Stok fisik produk tidak tersedia di gudang."
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

                <div class="mt-1 flex justify-between">

                    <p
                        id="admin-cancel-error"
                        class="hidden text-xs font-medium text-red-600"
                    ></p>

                    <p class="ml-auto text-xs text-gray-400">
                        Maks. 500 karakter
                    </p>

                </div>

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
                id="admin-cancel-cancel"
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
                id="admin-cancel-confirm"
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
                Batalkan Order
            </button>

        </div>

    </div>

</div>


</div>

@endif
@if(
    in_array(
        $order->status,
        ['pending', 'paid', 'processing'],
        true
    )
)
    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', () => {

            /*
            |--------------------------------------------------------------------------
            | ADMIN CANCEL MODAL
            |--------------------------------------------------------------------------
            */

            const openButton =
                document.getElementById('admin-cancel-order');

            const modal =
                document.getElementById('admin-cancel-modal');

            const overlay =
                document.getElementById('admin-cancel-overlay');

            const closeButton =
                document.getElementById('admin-cancel-close');

            const cancelButton =
                document.getElementById('admin-cancel-cancel');

            const confirmButton =
                document.getElementById('admin-cancel-confirm');

            const reasonInput =
                document.getElementById('admin-cancel-reason');

            const errorMessage =
                document.getElementById('admin-cancel-error');


            /*
            |--------------------------------------------------------------------------
            | Guard
            |--------------------------------------------------------------------------
            */

            if (!openButton || !modal) {
                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Open Modal
            |--------------------------------------------------------------------------
            */

            const openModal = () => {

                modal.classList.remove('hidden');

                document.body.classList.add(
                    'overflow-hidden'
                );

                requestAnimationFrame(() => {
                    reasonInput?.focus();
                });
            };


            /*
            |--------------------------------------------------------------------------
            | Close Modal
            |--------------------------------------------------------------------------
            */

            const closeModal = () => {

                modal.classList.add('hidden');

                document.body.classList.remove(
                    'overflow-hidden'
                );

                if (reasonInput) {
                    reasonInput.value = '';
                }

                if (errorMessage) {

                    errorMessage.textContent = '';

                    errorMessage.classList.add(
                        'hidden'
                    );
                }
            };


            /*
            |--------------------------------------------------------------------------
            | Events
            |--------------------------------------------------------------------------
            */

            openButton.addEventListener(
                'click',
                openModal
            );

            closeButton?.addEventListener(
                'click',
                closeModal
            );

            cancelButton?.addEventListener(
                'click',
                closeModal
            );

            overlay?.addEventListener(
                'click',
                closeModal
            );


            /*
            |--------------------------------------------------------------------------
            | Escape
            |--------------------------------------------------------------------------
            */

            document.addEventListener(
                'keydown',
                (event) => {

                    if (
                        event.key === 'Escape'
                        && !modal.classList.contains('hidden')
                    ) {
                        closeModal();
                    }

                }
            );


            /*
            |--------------------------------------------------------------------------
            | Confirm
            |--------------------------------------------------------------------------
            */

            confirmButton?.addEventListener(
                'click',
                async () => {

                    const reason =
                        reasonInput?.value.trim() ?? '';


                    /*
                    |--------------------------------------------------------------------------
                    | Validate
                    |--------------------------------------------------------------------------
                    */

                    if (!reason) {

                        errorMessage.textContent =
                            'Alasan pembatalan wajib diisi.';

                        errorMessage.classList.remove(
                            'hidden'
                        );

                        reasonInput?.focus();

                        return;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Clear Error
                    |--------------------------------------------------------------------------
                    */

                    errorMessage.textContent = '';

                    errorMessage.classList.add(
                        'hidden'
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Loading
                    |--------------------------------------------------------------------------
                    */

                    const originalText =
                        confirmButton.innerHTML;

                    confirmButton.disabled = true;

                    confirmButton.innerHTML = `
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


                    /*
                    |--------------------------------------------------------------------------
                    | Request
                    |--------------------------------------------------------------------------
                    */

                    try {

                        const csrfToken =
                            document
                                .querySelector(
                                    'meta[name="csrf-token"]'
                                )
                                ?.getAttribute('content');


                        if (!csrfToken) {
                            throw new Error(
                                'CSRF token tidak ditemukan.'
                            );
                        }


                        const formData =
                            new FormData();

                        formData.append(
                            '_method',
                            'PATCH'
                        );

                        formData.append(
                            'reason',
                            reason
                        );


                        const response =
                            await fetch(
                                @json(
                                    route(
                                        'admin.orders.cancel',
                                        $order
                                    )
                                ),
                                {
                                    method: 'POST',

                                    headers: {
                                        'Accept':
                                            'application/json',

                                        'X-CSRF-TOKEN':
                                            csrfToken,

                                        'X-Requested-With':
                                            'XMLHttpRequest',
                                    },

                                    body: formData,
                                }
                            );


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
                            data =
                                await response.json();
                        }


                        if (!response.ok) {

                            throw new Error(
                                data?.message
                                ??
                                'Gagal membatalkan order.'
                            );
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Redirect
                        |--------------------------------------------------------------------------
                        */

                        window.location.href =
                            data?.redirect
                            ??
                            @json(
                                route(
                                    'admin.orders.show',
                                    $order
                                )
                            );

                    } catch (error) {

                        console.error(
                            'Admin cancel order error:',
                            error
                        );


                        confirmButton.disabled =
                            false;

                        confirmButton.innerHTML =
                            originalText;


                        errorMessage.textContent =
                            error.message
                            ??
                            'Terjadi kesalahan saat membatalkan order.';

                        errorMessage.classList.remove(
                            'hidden'
                        );
                    }

                }
            );

        });
        </script>
    @endpush
@endif

@if(
    $order->status === 'req_cancel'
    && $cancellationRequest
    && $cancellationRequest->status === 'pending'
)
    @push('scripts')

        <script>
        document.addEventListener('DOMContentLoaded', () => {

            const csrfToken =
                document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute('content');

            if (!csrfToken) {
                console.error('CSRF token tidak ditemukan.');
                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Cancellation URLs
            |--------------------------------------------------------------------------
            */

            const approveUrl = @json(
                route(
                    'admin.orders.cancellation.approve',
                    $cancellationRequest
                )
            );

            const rejectUrl = @json(
                route(
                    'admin.orders.cancellation.reject',
                    $cancellationRequest
                )
            );

            /*
            |--------------------------------------------------------------------------
            | Elements
            |--------------------------------------------------------------------------
            */

            const approveButton =
                document.getElementById('approve-cancellation');

            const approveModal =
                document.getElementById('approve-cancellation-modal');

            const approveOverlay =
                document.getElementById('approve-cancellation-overlay');

            const approveClose =
                document.getElementById('approve-cancellation-close');

            const approveCancel =
                document.getElementById('approve-cancellation-cancel');

            const approveConfirm =
                document.getElementById('approve-cancellation-confirm');

            const approveNotes =
                document.getElementById('approve-cancellation-notes');

            const approveError =
                document.getElementById('approve-cancellation-error');


            const rejectButton =
                document.getElementById('reject-cancellation');

            const rejectModal =
                document.getElementById('reject-cancellation-modal');

            const rejectOverlay =
                document.getElementById('reject-cancellation-overlay');

            const rejectClose =
                document.getElementById('reject-cancellation-close');

            const rejectCancel =
                document.getElementById('reject-cancellation-cancel');

            const rejectConfirm =
                document.getElementById('reject-cancellation-confirm');

            const rejectNotes =
                document.getElementById('reject-cancellation-notes');

            const rejectError =
                document.getElementById('reject-cancellation-error');


            /*
            |--------------------------------------------------------------------------
            | Modal Helper
            |--------------------------------------------------------------------------
            */

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

                const openModal =
                    document.querySelector(
                        '[role="dialog"]:not(.hidden)'
                    );

                if (!openModal) {
                    document.body.classList.remove('overflow-hidden');
                }
            };


            /*
            |--------------------------------------------------------------------------
            | APPROVE
            |--------------------------------------------------------------------------
            */

            const openApproveModal = () => {

                showModal(approveModal);

                requestAnimationFrame(() => {
                    approveNotes?.focus();
                });
            };


            const closeApproveModal = () => {

                hideModal(approveModal);

                if (approveNotes) {
                    approveNotes.value = '';
                }

                if (approveError) {
                    approveError.textContent = '';
                    approveError.classList.add('hidden');
                }
            };


            approveButton?.addEventListener(
                'click',
                openApproveModal
            );

            approveClose?.addEventListener(
                'click',
                closeApproveModal
            );

            approveCancel?.addEventListener(
                'click',
                closeApproveModal
            );

            approveOverlay?.addEventListener(
                'click',
                closeApproveModal
            );


            approveConfirm?.addEventListener(
                'click',
                async () => {

                    const originalText =
                        approveConfirm.innerHTML;

                    approveConfirm.disabled = true;

                    approveConfirm.innerHTML = `
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

                        const formData = new FormData();

                        formData.append(
                            '_method',
                            'PATCH'
                        );

                        const notes =
                            approveNotes?.value.trim() ?? '';

                        if (notes) {

                            formData.append(
                                'admin_notes',
                                notes
                            );

                        }


                        const response = await fetch(
                            approveUrl,
                            {
                                method: 'POST',

                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'X-Requested-With': 'XMLHttpRequest',
                                },

                                body: formData,
                            }
                        );


                        if (response.redirected) {

                            window.location.href =
                                response.url;

                            return;
                        }


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


                        if (!response.ok) {

                            throw new Error(
                                data?.message
                                ?? 'Gagal menyetujui pembatalan.'
                            );
                        }


                        window.location.href =
                            data?.redirect
                            ?? @json(
                                route(
                                    'admin.orders.show',
                                    $order
                                )
                            );

                    } catch (error) {

                        console.error(
                            'Approve cancellation error:',
                            error
                        );

                        approveConfirm.disabled = false;

                        approveConfirm.innerHTML =
                            originalText;

                        if (approveError) {

                            approveError.textContent =
                                error.message
                                ?? 'Terjadi kesalahan saat memproses pembatalan.';

                            approveError.classList.remove(
                                'hidden'
                            );
                        }

                    }

                }
            );


            /*
            |--------------------------------------------------------------------------
            | REJECT
            |--------------------------------------------------------------------------
            */

            const openRejectModal = () => {

                showModal(rejectModal);

                requestAnimationFrame(() => {
                    rejectNotes?.focus();
                });
            };


            const closeRejectModal = () => {

                hideModal(rejectModal);

                if (rejectNotes) {
                    rejectNotes.value = '';
                }

                if (rejectError) {
                    rejectError.textContent = '';
                    rejectError.classList.add('hidden');
                }
            };


            rejectButton?.addEventListener(
                'click',
                openRejectModal
            );

            rejectClose?.addEventListener(
                'click',
                closeRejectModal
            );

            rejectCancel?.addEventListener(
                'click',
                closeRejectModal
            );

            rejectOverlay?.addEventListener(
                'click',
                closeRejectModal
            );


            rejectConfirm?.addEventListener(
                'click',
                async () => {

                    const notes =
                        rejectNotes?.value.trim() ?? '';


                    if (!notes) {

                        rejectError.textContent =
                            'Alasan atau catatan penolakan wajib diisi.';

                        rejectError.classList.remove(
                            'hidden'
                        );

                        rejectNotes?.focus();

                        return;
                    }


                    const originalText =
                        rejectConfirm.innerHTML;

                    rejectConfirm.disabled = true;

                    rejectConfirm.innerHTML = `
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
                                d="M4 12a8 8 0 018-8v4a4 4 0 01-4 4H4z"
                            ></path>
                        </svg>

                        Memproses...
                    `;


                    try {

                        const formData = new FormData();

                        formData.append(
                            '_method',
                            'PATCH'
                        );

                        formData.append(
                            'admin_notes',
                            notes
                        );


                        const response = await fetch(
                            rejectUrl,
                            {
                                method: 'POST',

                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'X-Requested-With': 'XMLHttpRequest',
                                },

                                body: formData,
                            }
                        );


                        if (response.redirected) {

                            window.location.href =
                                response.url;

                            return;
                        }


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


                        if (!response.ok) {

                            throw new Error(
                                data?.message
                                ?? 'Gagal menolak pembatalan.'
                            );
                        }


                        window.location.href =
                            data?.redirect
                            ?? @json(
                                route(
                                    'admin.orders.show',
                                    $order
                                )
                            );

                    } catch (error) {

                        console.error(
                            'Reject cancellation error:',
                            error
                        );

                        rejectConfirm.disabled = false;

                        rejectConfirm.innerHTML =
                            originalText;

                        rejectError.textContent =
                            error.message
                            ?? 'Terjadi kesalahan saat memproses penolakan.';

                        rejectError.classList.remove(
                            'hidden'
                        );

                    }

                }
            );


            /*
            |--------------------------------------------------------------------------
            | Escape
            |--------------------------------------------------------------------------
            */

            document.addEventListener(
                'keydown',
                (event) => {

                    if (event.key !== 'Escape') {
                        return;
                    }

                    closeApproveModal();
                    closeRejectModal();

                }
            );

        });
        </script>

    @endpush
@endif
