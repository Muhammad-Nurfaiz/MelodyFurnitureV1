@extends('admin.layouts.app')

@section('title', 'WhatsApp Automation')

@section('content')

<div
    x-data="whatsappAutomation()"
    x-init="init()"
    class="space-y-6"
>

    {{-- ===================================================== --}}
    {{-- PAGE HEADER --}}
    {{-- ===================================================== --}}

    <x-admin.page-header
        title="WhatsApp Automation"
        description="Kelola koneksi WhatsApp dan pantau seluruh proses pengiriman pesan otomatis."
    />

    {{-- ===================================================== --}}
    {{-- CONNECTION --}}
    {{-- ===================================================== --}}

    <x-admin.card>

        <x-admin.card-header
            title="WhatsApp Connection"
            description="Hubungkan akun WhatsApp Melody Furniture melalui QR Code."
        />

        <x-admin.card-body>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                {{-- STATUS --}}
                <div class="lg:col-span-2">

                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-5">

                        <div class="flex items-start justify-between gap-4">

                            <div>

                                <p class="text-sm font-medium text-gray-500">
                                    Connection Status
                                </p>

                                <div class="mt-2 flex items-center gap-2">

                                    <span
                                        class="h-3 w-3 rounded-full"
                                        :class="statusColor"
                                    ></span>

                                    <span
                                        class="text-lg font-bold text-gray-900"
                                        x-text="statusLabel"
                                    ></span>

                                </div>

                                <p
                                    class="mt-2 text-sm text-gray-500"
                                    x-text="statusDescription"
                                ></p>

                            </div>

                            <div
                                class="rounded-xl px-3 py-1.5 text-xs font-semibold"
                                :class="statusBadgeClass"
                                x-text="status"
                            ></div>

                        </div>

                        {{-- ACCOUNT --}}
                        <div
                            x-show="me"
                            x-cloak
                            class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2"
                        >

                            <div class="rounded-lg border border-gray-200 bg-white p-4">

                                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                    WhatsApp Account
                                </p>

                                <p
                                    class="mt-1 font-semibold text-gray-900"
                                    x-text="me?.id ?? '-'"
                                ></p>

                            </div>

                            <div class="rounded-lg border border-gray-200 bg-white p-4">

                                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                    Profile
                                </p>

                                <p
                                    class="mt-1 font-semibold text-gray-900"
                                    x-text="me?.pushName ?? '-'"
                                ></p>

                            </div>

                        </div>

                        {{-- ACTIONS --}}
                        <div class="mt-6 flex flex-wrap gap-3">

                            <button
                                type="button"
                                x-show="canConnect"
                                x-on:click="connect()"
                                x-bind:disabled="loading"
                                class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <span
                                    class="animate-spin"
                                    x-show="loading"
                                >
                                    ↻
                                </span>

                                Hubungkan WhatsApp
                            </button>

                            <button
                                type="button"
                                x-show="status === 'WORKING'"
                                x-on:click="restart()"
                                x-bind:disabled="loading"
                                class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 disabled:opacity-50"
                            >
                                Restart
                            </button>

                            <button
                                type="button"
                                x-show="status !== 'STOPPED' && exists"
                                x-on:click="stop()"
                                x-bind:disabled="loading"
                                class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 disabled:opacity-50"
                            >
                                Stop
                            </button>

                            <button
                                type="button"
                                x-show="exists && status !== 'STOPPED'"
                                x-on:click="logout()"
                                x-bind:disabled="loading"
                                class="rounded-lg border border-red-200 bg-white px-4 py-2.5 text-sm font-medium text-red-600 transition hover:bg-red-50 disabled:opacity-50"
                            >
                                Logout
                            </button>

                        </div>

                    </div>

                </div>

                {{-- QR --}}
                <div>

                    <div
                        class="flex min-h-[280px] flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 bg-white p-5"
                    >

                        <template x-if="qr">

                            <div class="text-center">

                                <img
                                    :src="qr"
                                    alt="WhatsApp QR Code"
                                    class="mx-auto h-56 w-56 rounded-lg border border-gray-200"
                                >

                                <p class="mt-3 text-xs text-gray-500">
                                    Scan QR Code menggunakan WhatsApp
                                </p>

                            </div>

                        </template>

                        <template x-if="!qr && status === 'SCAN_QR_CODE'">

                            <div class="text-center">

                                <div class="text-4xl">
                                    📱
                                </div>

                                <p class="mt-3 text-sm font-semibold text-gray-900">
                                    QR Code tersedia
                                </p>

                                <button
                                    type="button"
                                    x-on:click="loadQr()"
                                    class="mt-3 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                                >
                                    Tampilkan QR
                                </button>

                            </div>

                        </template>

                        <template x-if="!qr && status !== 'SCAN_QR_CODE'">

                            <div class="text-center">

                                <div class="text-4xl">
                                    💬
                                </div>

                                <p class="mt-3 text-sm font-semibold text-gray-900">
                                    WhatsApp
                                </p>

                                <p class="mt-1 text-xs text-gray-500">
                                    <span x-text="statusDescription"></span>
                                </p>

                            </div>

                        </template>

                    </div>

                </div>

            </div>

        </x-admin.card-body>

    </x-admin.card>


    {{-- ===================================================== --}}
    {{-- QUEUE STATISTICS --}}
    {{-- ===================================================== --}}

    <x-admin.stats.grid>

        <x-admin.stats.card
            title="Total Pesan"
            :value="$stats['total']"
            icon="chat-bubble-left-right"
            color="blue"
        />

        <x-admin.stats.card
            title="Pending"
            :value="$stats['pending']"
            icon="clock"
            color="yellow"
        />

        <x-admin.stats.card
            title="Berhasil"
            :value="$stats['success']"
            icon="check-circle"
            color="green"
        />

        <x-admin.stats.card
            title="Gagal"
            :value="$stats['failed']"
            icon="x-circle"
            color="red"
        />

    </x-admin.stats.grid>


    {{-- ===================================================== --}}
    {{-- OUTBOX / QUEUE --}}
    {{-- ===================================================== --}}

    <x-admin.card>

        <x-admin.card-header
            title="Outbox / Queue"
            description="Daftar pesan WhatsApp yang dibuat oleh sistem."
        />

        <div class="border-b border-gray-200 px-5 py-4">

            <div class="flex flex-wrap gap-2">

                <a
                    href="{{ route('admin.whatsapp.index') }}"
                    class="rounded-full px-4 py-2 text-sm font-medium transition
                    {{ !request('status')
                        ? 'bg-blue-600 text-white'
                        : 'border border-gray-300 text-gray-700 hover:bg-gray-50'
                    }}"
                >
                    Semua
                </a>

                @foreach([
                    'pending' => 'Pending',
                    'processing' => 'Processing',
                    'success' => 'Success',
                    'failed' => 'Failed',
                ] as $key => $label)

                    <a
                        href="{{ route('admin.whatsapp.index', ['status' => $key]) }}"
                        class="rounded-full px-4 py-2 text-sm font-medium transition
                        {{ request('status') === $key
                            ? 'bg-blue-600 text-white'
                            : 'border border-gray-300 text-gray-700 hover:bg-gray-50'
                        }}"
                    >
                        {{ $label }}
                    </a>

                @endforeach

            </div>

        </div>

        <div class="overflow-x-auto">

            <x-admin.table.table>

                <x-admin.table.thead>

                    <tr>

                        <x-admin.table.th>
                            Tujuan
                        </x-admin.table.th>

                        <x-admin.table.th>
                            Pesan
                        </x-admin.table.th>

                        <x-admin.table.th>
                            Status
                        </x-admin.table.th>

                        <x-admin.table.th>
                            Dibuat
                        </x-admin.table.th>

                        <x-admin.table.th>
                            Error
                        </x-admin.table.th>

                        <x-admin.table.th>
                            Aksi
                        </x-admin.table.th>

                    </tr>

                </x-admin.table.thead>

                <x-admin.table.tbody>

                    @forelse($queues as $queue)

                        <x-admin.table.tr>

                            <x-admin.table.td>

                                <span class="font-medium text-gray-900">
                                    {{ $queue->phone_target }}
                                </span>

                            </x-admin.table.td>

                            <x-admin.table.td>

                                <div class="max-w-md whitespace-pre-line text-sm text-gray-600">
                                    {{ $queue->message_text }}
                                </div>

                            </x-admin.table.td>

                            <x-admin.table.td>

                                @php

                                    $statusClasses = [
                                        'pending' => 'bg-yellow-50 text-yellow-700 border-yellow-200',
                                        'processing' => 'bg-blue-50 text-blue-700 border-blue-200',
                                        'success' => 'bg-green-50 text-green-700 border-green-200',
                                        'failed' => 'bg-red-50 text-red-700 border-red-200',
                                    ];

                                    $statusLabels = [
                                        'pending' => 'Pending',
                                        'processing' => 'Processing',
                                        'success' => 'Success',
                                        'failed' => 'Failed',
                                    ];

                                @endphp

                                <span
                                    class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold {{ $statusClasses[$queue->status] ?? 'bg-gray-50 text-gray-700 border-gray-200' }}"
                                >
                                    {{ $statusLabels[$queue->status] ?? ucfirst($queue->status) }}
                                </span>

                            </x-admin.table.td>

                            <x-admin.table.td>

                                <span class="whitespace-nowrap text-sm text-gray-500">
                                    {{ $queue->created_at?->format('d M Y, H:i') }}
                                </span>

                            </x-admin.table.td>

                            <x-admin.table.td>

                                @if($queue->error_log)

                                    <span
                                        title="{{ $queue->error_log }}"
                                        class="block max-w-xs truncate text-sm text-red-600"
                                    >
                                        {{ $queue->error_log }}
                                    </span>

                                @else

                                    <span class="text-gray-400">
                                        —
                                    </span>

                                @endif

                            </x-admin.table.td>

                            <x-admin.table.td>

                                @if($queue->status === 'failed')

                                    <button
                                        type="button"
                                        x-on:click="retryQueue('{{ $queue->id }}')"
                                        x-bind:disabled="retryingId === '{{ $queue->id }}'"
                                        class="inline-flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-100 disabled:cursor-not-allowed disabled:opacity-50"
                                    >

                                        <span
                                            x-show="retryingId === '{{ $queue->id }}'"
                                            class="animate-spin"
                                        >
                                            ↻
                                        </span>

                                        <span
                                            x-text="
                                                retryingId === '{{ $queue->id }}'
                                                    ? 'Retrying...'
                                                    : 'Retry'
                                            "
                                        ></span>

                                    </button>

                                @else

                                    <span class="text-gray-400">
                                        —
                                    </span>

                                @endif

                            </x-admin.table.td>

                        </x-admin.table.tr>

                    @empty

                        <tr>

                            <td colspan="6">

                                <x-admin.state.empty
                                    title="Belum ada pesan WhatsApp"
                                    description="Pesan WhatsApp yang dibuat oleh sistem akan muncul di sini."
                                />

                            </td>

                        </tr>

                    @endforelse

                </x-admin.table.tbody>

            </x-admin.table.table>

        </div>

        @if($queues->hasPages())

            <div class="border-t border-gray-200 px-5 py-4">

                <x-admin.pagination.pagination
                    :paginator="$queues"
                />

            </div>

        @endif

    </x-admin.card>


</div>


<script>

function whatsappAutomation() {

    return {

        exists: false,

        status: 'UNKNOWN',

        me: null,

        qr: null,

        loading: false,

        initialized: false,

        retryingId: null,

        queues: [],

        queueStats: {
            total: 0,
            pending: 0,
            processing: 0,
            success: 0,
            failed: 0,
        },

        queuePagination: {
            current_page: 1,
            last_page: 1,
            per_page: 15,
            total: 0,
        },

        queueLoading: false,

        get statusLabel() {

            const labels = {
                WORKING: 'Terhubung',
                SCAN_QR_CODE: 'Menunggu Scan QR',
                STARTING: 'Memulai',
                STOPPED: 'Terhenti',
                FAILED: 'Gagal',
                UNKNOWN: 'Tidak Diketahui',
            };

            return labels[this.status] ?? this.status;
        },

        get statusDescription() {

            const descriptions = {
                WORKING:
                    'WhatsApp siap digunakan untuk mengirim pesan otomatis.',

                SCAN_QR_CODE:
                    'Silakan scan QR Code menggunakan aplikasi WhatsApp.',

                STARTING:
                    'Session WhatsApp sedang dimulai.',

                STOPPED:
                    'Session WhatsApp sedang tidak aktif.',

                FAILED:
                    'Session mengalami masalah. Coba restart atau login ulang.',

                UNKNOWN:
                    'Status WhatsApp belum diketahui.',
            };

            return descriptions[this.status] ?? '';
        },

        get statusColor() {

            return {
                WORKING: 'bg-green-500',
                SCAN_QR_CODE: 'bg-yellow-500',
                STARTING: 'bg-blue-500',
                STOPPED: 'bg-gray-400',
                FAILED: 'bg-red-500',
            }[this.status] ?? 'bg-gray-400';
        },

        get statusBadgeClass() {

            return {
                WORKING:
                    'bg-green-50 text-green-700',

                SCAN_QR_CODE:
                    'bg-yellow-50 text-yellow-700',

                STARTING:
                    'bg-blue-50 text-blue-700',

                STOPPED:
                    'bg-gray-100 text-gray-600',

                FAILED:
                    'bg-red-50 text-red-700',
            }[this.status] ?? 'bg-gray-100 text-gray-600';
        },

        get canConnect() {

            return !this.exists ||
                this.status === 'STOPPED' ||
                this.status === 'FAILED';
        },

        async init() {

            await Promise.all([
                this.loadStatus(),
                this.loadQueues(),
            ]);

            this.initialized = true;

            this.startPolling();
        },

        startPolling() {

            setTimeout(async () => {

                await Promise.all([
                    this.loadStatus(),
                    this.loadQueues(),
                ]);

                this.startPolling();

            }, 3000);

        },

        async loadStatus() {

            try {

                const response = await fetch(
                    @js(route('admin.whatsapp.connection.status')),
                    {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        }
                    }
                );

                const result = await response.json();

                if (!result.success) {

                    throw new Error(
                        result.message ?? 'Gagal mendapatkan status WhatsApp.'
                    );

                }

                const previousStatus = this.status;

                this.exists =
                    result.data.exists ?? false;

                this.status =
                    result.data.status ?? 'UNKNOWN';

                this.me =
                    result.data.me ?? null;

                /*
                * Jika WhatsApp sudah terhubung,
                * QR tidak diperlukan lagi.
                */
                if (this.status === 'WORKING') {

                    this.qr = null;

                    return;

                }

                /*
                * Jika session berhenti/gagal,
                * QR juga tidak diperlukan.
                */
                if (
                    this.status === 'STOPPED' ||
                    this.status === 'FAILED' ||
                    this.status === 'UNKNOWN'
                ) {

                    this.qr = null;

                    return;

                }

                /*
                * Hanya ambil QR ketika benar-benar
                * membutuhkan scan.
                */
                if (this.status === 'SCAN_QR_CODE') {

                    /*
                    * Jangan terus-menerus request QR
                    * jika QR sudah tersedia.
                    */
                    if (!this.qr) {

                        await this.loadQr();

                    }

                    return;

                }

            } catch (error) {

                console.error(
                    'WhatsApp Status Error:',
                    error
                );

                this.status = 'UNKNOWN';

            }

        },

        async loadQueues() {

            try {

                const response = await fetch(
                    @js(route('admin.whatsapp.queues')),
                    {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        }
                    }
                );

                const result = await response.json();

                if (!result.success) {

                    throw new Error(
                        result.message ??
                        'Gagal mendapatkan data WhatsApp queue.'
                    );

                }

                const data = result.data ?? {};

                this.queues =
                    data.items ?? [];

                this.queueStats =
                    data.stats ?? {
                        total: 0,
                        pending: 0,
                        processing: 0,
                        success: 0,
                        failed: 0,
                    };

                this.queuePagination =
                    data.pagination ?? {
                        current_page: 1,
                        last_page: 1,
                        per_page: 15,
                        total: 0,
                    };

            } catch (error) {

                console.error(
                    'WhatsApp Queue Error:',
                    error
                );

            }

        },

        async connect() {

            this.loading = true;

            try {

                const response = await fetch(
                    @js(route('admin.whatsapp.connection.connect')),
                    {
                        method: 'POST',

                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN':
                                document
                                    .querySelector('meta[name="csrf-token"]')
                                    .getAttribute('content'),
                        },
                    }
                );

                const result = await response.json();

                if (!result.success) {
                    throw new Error(
                        result.message ??
                        'Gagal menghubungkan WhatsApp.'
                    );
                }

                /*
                * Tunggu sampai WAHA benar-benar
                * siap menampilkan QR.
                */

                const qrReady = await this.waitForQrReady();

                if (!qrReady) {

                    if (this.status === 'WORKING') {
                        return;
                    }

                    throw new Error(
                        'WhatsApp belum siap menampilkan QR Code. Silakan coba lagi.'
                    );
                }

                /*
                * loadStatus() sudah otomatis memanggil
                * loadQr() ketika status SCAN_QR_CODE.
                */

            } catch (error) {

                alert(error.message);

            } finally {

                this.loading = false;
            }
        },

        async loadQr() {

            try {

                const response = await fetch(
                    @js(route('admin.whatsapp.connection.qr')),
                    {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        }
                    }
                );

                const result =
                    await response.json();

                if (!result.success) {

                    throw new Error(
                        result.message ?? 'QR Code gagal diambil.'
                    );

                }

                const data = result.data;

                if (
                    data &&
                    data.data
                ) {

                    this.qr =
                        `data:${data.mimetype ?? 'image/png'};base64,${data.data}`;

                } else {

                    this.qr = null;

                }

            } catch (error) {

                console.error(
                    'QR Error:',
                    error
                );

            }

        },

        async stop() {

            await this.connectionAction(
                @js(route('admin.whatsapp.connection.stop'))
            );

        },

        async restart() {

            await this.connectionAction(
                @js(route('admin.whatsapp.connection.restart'))
            );

        },

        async logout() {

            if (
                !confirm(
                    'Yakin ingin logout dari WhatsApp?'
                )
            ) {

                return;

            }

            await this.connectionAction(
                @js(route('admin.whatsapp.connection.logout'))
            );

        },

        async connectionAction(url) {

            this.loading = true;

            try {

                const response = await fetch(
                    url,
                    {
                        method: 'POST',

                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN':
                                document
                                    .querySelector('meta[name="csrf-token"]')
                                    .getAttribute('content'),
                        },
                    }
                );

                const result =
                    await response.json();

                if (!result.success) {

                    throw new Error(
                        result.message ??
                        'Operasi WhatsApp gagal.'
                    );

                }

                this.qr = null;

                await this.loadStatus();

            } catch (error) {

                alert(error.message);

            } finally {

                this.loading = false;

            }

        },

        async retryQueue(id) {

            if (this.retryingId) {
                return;
            }

            const confirmed = confirm(
                'Yakin ingin mengirim ulang pesan WhatsApp ini?'
            );

            if (!confirmed) {
                return;
            }

            this.retryingId = id;

            try {

                const response = await fetch(
                    @js(route('admin.whatsapp.retry', ['id' => '__ID__'])).replace('__ID__', id),
                    {
                        method: 'POST',

                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN':
                                document
                                    .querySelector('meta[name="csrf-token"]')
                                    .getAttribute('content'),
                        },
                    }
                );

                const result = await response.json();

                if (!result.success) {

                    throw new Error(
                        result.message ??
                        'Pesan WhatsApp gagal di-retry.'
                    );

                }

                /*
                * Setelah retry berhasil,
                * status database sudah menjadi pending.
                *
                * Karena tabel masih berasal dari server-side Blade,
                * refresh halaman diperlukan untuk mendapatkan
                * status row terbaru.
                */

                window.location.reload();

            } catch (error) {

                console.error(
                    'Retry WhatsApp Error:',
                    error
                );

                alert(error.message);

            } finally {

                this.retryingId = null;

            }

        },

        async waitForQrReady(maxAttempts = 15) {

            for (let attempt = 0; attempt < maxAttempts; attempt++) {

                await this.loadStatus();

                if (this.status === 'SCAN_QR_CODE') {
                    return true;
                }

                if (this.status === 'WORKING') {
                    return false;
                }

                await new Promise(resolve => {
                    setTimeout(resolve, 1000);
                });
            }

            return false;
        },

    };

}

</script>

@endsection