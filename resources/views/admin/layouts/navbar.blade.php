@php
    $admin = Auth::user();

    $unreadNotificationCount = $admin
        ->unreadNotifications()
        ->count();

    $notifications = $admin
        ->notifications()
        ->latest()
        ->take(10)
        ->get();
@endphp

<header
    x-data="{
        profileOpen: false,
        notificationOpen: false,

        whatsappStatus: 'checking',
        whatsappLabel: 'WhatsApp',
        whatsappDotClass: 'bg-gray-400',
        whatsappBadgeClass: 'bg-gray-50',
        whatsappTextClass: 'text-gray-600',
        lastWhatsappStatus: null,
        whatsappTimer: null,

        init() {
            this.checkWhatsappStatus();

            this.whatsappTimer = setInterval(() => {
                if (!document.hidden) {
                    this.checkWhatsappStatus();
                }
            }, 10000);

            window.addEventListener('beforeunload', () => {
                if (this.whatsappTimer) {
                    clearInterval(this.whatsappTimer);
                }
            });
        },

        async checkWhatsappStatus() {
            try {
                const response = await fetch(
                    '{{ route('admin.whatsapp.connection.status') }}',
                    {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                        cache: 'no-store',
                    }
                );

                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }

                const result = await response.json();

                if (!result.success) {
                    throw new Error(
                        result.message ?? 'Status WhatsApp tidak tersedia.'
                    );
                }

                const status = result.data?.status ?? 'UNKNOWN';

                // Tidak melakukan perubahan jika status sama.
                if (status === this.lastWhatsappStatus) {
                    return;
                }

                this.lastWhatsappStatus = status;

                this.updateWhatsappBadge(status);

            } catch (error) {

                // Jangan membuat error WAHA mengganggu navbar.
                if (this.lastWhatsappStatus === 'OFFLINE') {
                    return;
                }

                this.lastWhatsappStatus = 'OFFLINE';

                this.updateWhatsappBadge('OFFLINE');
            }
        },

        updateWhatsappBadge(status) {

            switch (status) {

                case 'WORKING':
                case 'CONNECTED':

                    this.whatsappStatus = 'online';
                    this.whatsappLabel = 'WhatsApp Online';
                    this.whatsappDotClass = 'bg-green-500';
                    this.whatsappBadgeClass = 'bg-green-50';
                    this.whatsappTextClass = 'text-green-700';

                    break;

                case 'STARTING':

                    this.whatsappStatus = 'starting';
                    this.whatsappLabel = 'WhatsApp Starting';
                    this.whatsappDotClass = 'bg-yellow-500';
                    this.whatsappBadgeClass = 'bg-yellow-50';
                    this.whatsappTextClass = 'text-yellow-700';

                    break;

                case 'SCAN_QR_CODE':

                    this.whatsappStatus = 'qr';
                    this.whatsappLabel = 'WhatsApp Scan QR';
                    this.whatsappDotClass = 'bg-orange-500';
                    this.whatsappBadgeClass = 'bg-orange-50';
                    this.whatsappTextClass = 'text-orange-700';

                    break;

                case 'STOPPED':
                case 'FAILED':
                case 'OFFLINE':
                case 'UNKNOWN':

                    this.whatsappStatus = 'offline';
                    this.whatsappLabel = 'WhatsApp Offline';
                    this.whatsappDotClass = 'bg-red-500';
                    this.whatsappBadgeClass = 'bg-red-50';
                    this.whatsappTextClass = 'text-red-700';

                    break;

                default:

                    this.whatsappStatus = 'unknown';
                    this.whatsappLabel = 'WhatsApp ' + status;
                    this.whatsappDotClass = 'bg-gray-400';
                    this.whatsappBadgeClass = 'bg-gray-50';
                    this.whatsappTextClass = 'text-gray-600';
            }
        }
    }"
    class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-gray-200 bg-white px-6 lg:px-8">

    {{-- Left --}}
    <div class="flex items-center gap-4">

        {{-- Mobile Menu --}}
        <button
            @click="sidebarOpen = true"
            class="rounded-lg p-2 transition hover:bg-gray-100 lg:hidden">

            <x-heroicon-o-bars-3 class="h-6 w-6 text-gray-700"/>

        </button>

        {{-- Breadcrumb / Page Title --}}
        <div>

            <h1 class="text-xl font-bold text-gray-900">
                @yield('title', 'Dashboard')
            </h1>

        </div>

    </div>


    {{-- Right --}}
    <div class="flex items-center gap-4">

        {{-- WhatsApp Status --}}
        <div
            class="hidden items-center gap-2 rounded-full px-3 py-1 md:flex transition-colors duration-300"
            :class="whatsappBadgeClass">

            <span
                class="h-2 w-2 rounded-full transition-colors duration-300"
                :class="whatsappDotClass">
            </span>

            <span
                class="text-sm font-medium transition-colors duration-300"
                :class="whatsappTextClass"
                x-text="whatsappLabel">
            </span>

        </div>


        {{-- Notification --}}
        <div
            x-data="{ notificationOpen: false }"
            class="relative">

            <button
                type="button"
                @click="notificationOpen = !notificationOpen"
                class="relative rounded-lg p-2 transition hover:bg-gray-100">

                <x-heroicon-o-bell class="h-6 w-6 text-gray-600"/>

                @php
                    $unreadNotificationsCount = Auth::user()
                        ->unreadNotifications()
                        ->count();
                @endphp

                @if ($unreadNotificationsCount > 0)

                    <span
                        class="absolute -right-1 -top-1 flex min-h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">

                        {{ $unreadNotificationsCount > 99 ? '99+' : $unreadNotificationsCount }}

                    </span>

                @endif

            </button>

            {{-- Notification Dropdown --}}
            <div
                x-show="notificationOpen"
                @click.away="notificationOpen = false"
                x-transition
                class="absolute right-0 mt-3 w-[380px] max-w-[calc(100vw-2rem)] overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl"
                style="display: none;">

                {{-- Header --}}
                <div
                    class="flex items-center justify-between border-b border-gray-200 px-4 py-3">

                    <div>

                        <h3 class="text-sm font-bold text-gray-900">
                            Notifikasi
                        </h3>

                        @if ($unreadNotificationsCount > 0)

                            <p class="mt-0.5 text-xs text-gray-500">
                                {{ $unreadNotificationsCount }} belum dibaca
                            </p>

                        @else

                            <p class="mt-0.5 text-xs text-gray-500">
                                Semua telah dibaca
                            </p>

                        @endif

                    </div>

                    @if ($unreadNotificationsCount > 0)

                        <form
                            method="POST"
                            action="{{ route('admin.notifications.read-all') }}">

                            @csrf
                            @method('PATCH')

                            <button
                                type="submit"
                                class="text-xs font-medium text-blue-600 transition hover:text-blue-800">

                                Tandai semua dibaca

                            </button>

                        </form>

                    @endif

                </div>

                {{-- Notification List --}}
                <div class="max-h-[420px] overflow-y-auto">

                    @forelse (
                        Auth::user()
                            ->notifications()
                            ->latest()
                            ->limit(10)
                            ->get()
                        as $notification
                    )

                        @php
                            $data = $notification->data;

                            $type = $data['type'] ?? 'default';

                            $isUnread = is_null($notification->read_at);

                            $icon = match ($type) {
                                'low_stock' =>
                                    'archive-box',

                                'pending_order' =>
                                    'shopping-cart',

                                'pending_refund' =>
                                    'banknotes',

                                'cancellation_request' =>
                                    'x-circle',

                                'cancellation_approved' =>
                                    'check-circle',

                                'cancellation_rejected' =>
                                    'no-symbol',

                                default =>
                                    'bell',
                            };

                            $iconClass = match ($type) {
                                'low_stock' =>
                                    'bg-orange-100 text-orange-600',

                                'pending_order' =>
                                    'bg-blue-100 text-blue-600',

                                'pending_refund' =>
                                    'bg-purple-100 text-purple-600',

                                'cancellation_request' =>
                                    'bg-red-100 text-red-600',

                                'cancellation_approved' =>
                                    'bg-green-100 text-green-600',

                                'cancellation_rejected' =>
                                    'bg-gray-100 text-gray-600',

                                default =>
                                    'bg-gray-100 text-gray-600',
                            };
                        @endphp

                        <div class="relative border-b border-gray-100">

                            {{-- Notification --}}
                            <form
                                method="POST"
                                action="{{ route(
                                    'admin.notifications.read',
                                    $notification
                                ) }}">

                                @csrf
                                @method('PATCH')

                                <button
                                    type="submit"
                                    class="flex w-full items-start gap-3 px-4 py-3 pr-10 text-left transition hover:bg-gray-50
                                    {{ $isUnread ? 'bg-blue-50/60' : 'bg-white' }}">

                                    {{-- Icon --}}
                                    <div
                                        class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $iconClass }}">

                                        @switch($icon)

                                            @case('archive-box')

                                                <x-heroicon-o-archive-box class="h-5 w-5"/>

                                                @break

                                            @case('shopping-cart')

                                                <x-heroicon-o-shopping-cart class="h-5 w-5"/>

                                                @break

                                            @case('banknotes')

                                                <x-heroicon-o-banknotes class="h-5 w-5"/>

                                                @break

                                            @case('x-circle')

                                                <x-heroicon-o-x-circle class="h-5 w-5"/>

                                                @break

                                            @case('check-circle')

                                                <x-heroicon-o-check-circle class="h-5 w-5"/>

                                                @break

                                            @case('no-symbol')

                                                <x-heroicon-o-no-symbol class="h-5 w-5"/>

                                                @break

                                            @default

                                                <x-heroicon-o-bell class="h-5 w-5"/>

                                        @endswitch

                                    </div>

                                    {{-- Content --}}
                                    <div class="min-w-0 flex-1">

                                        <div class="flex items-start justify-between gap-2">

                                            <p
                                                class="truncate text-sm {{ $isUnread ? 'font-semibold text-gray-900' : 'font-medium text-gray-700' }}">

                                                {{ $data['title'] ?? 'Notifikasi' }}

                                            </p>

                                            @if ($isUnread)

                                                <span
                                                    class="mt-1 h-2 w-2 shrink-0 rounded-full bg-blue-500">
                                                </span>

                                            @endif

                                        </div>

                                        <p class="mt-1 text-xs leading-5 text-gray-500">

                                            {{ $data['message'] ?? '' }}

                                        </p>

                                        <p class="mt-1 text-[10px] text-gray-400">

                                            {{ $notification->created_at?->diffForHumans() }}

                                        </p>

                                    </div>

                                </button>

                            </form>

                            {{-- Delete --}}
                            <form
                                method="POST"
                                action="{{ route(
                                    'admin.notifications.destroy',
                                    $notification
                                ) }}"
                                class="absolute right-2 top-2 z-10">

                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    title="Hapus notifikasi"
                                    class="flex h-7 w-7 items-center justify-center rounded-md text-gray-400 transition hover:bg-red-50 hover:text-red-500">

                                    <x-heroicon-o-x-mark class="h-4 w-4"/>

                                </button>

                            </form>

                        </div>

                    @empty

                        <div class="px-6 py-10 text-center">

                            <x-heroicon-o-bell-slash
                                class="mx-auto h-10 w-10 text-gray-300"/>

                            <p class="mt-3 text-sm font-medium text-gray-700">

                                Belum ada notifikasi

                            </p>

                            <p class="mt-1 text-xs text-gray-400">

                                Notifikasi aktivitas administrator akan muncul di sini.

                            </p>

                        </div>

                    @endforelse

                </div>

            </div>

        </div>


        {{-- ============================================================= --}}
        {{-- Profile --}}
        {{-- ============================================================= --}}

        <div class="relative">

            <button
                @click="profileOpen=!profileOpen; notificationOpen=false"
                class="flex items-center gap-3 rounded-lg px-2 py-2 transition hover:bg-gray-100">

                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-blue-100">

                    @if($admin->profile_photo)

                        <img
                            src="{{ asset('storage/' . $admin->profile_photo) }}"
                            alt="{{ $admin->full_name }}"
                            class="h-full w-full object-cover"
                        >

                    @else

                        <x-heroicon-o-user
                            class="h-5 w-5 text-blue-600"
                        />

                    @endif

                </div>


                <div class="hidden text-left xl:block">

                    <p class="text-sm font-semibold">

                        {{ $admin->full_name }}

                    </p>

                    <p class="text-xs text-gray-500">

                        Administrator

                    </p>

                </div>


                <x-heroicon-o-chevron-down
                    class="h-4 w-4 text-gray-500"
                />

            </button>


            {{-- Profile Dropdown --}}
            <div
                x-show="profileOpen"
                @click.away="profileOpen=false"
                x-transition
                class="absolute right-0 mt-2 w-56 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg"
                style="display: none;">

                <a
                    href="{{ route('admin.profile.index') }}"
                    class="flex items-center gap-3 px-4 py-3 text-sm transition hover:bg-gray-50">

                    <x-heroicon-o-user-circle class="h-5 w-5"/>

                    Profil

                </a>


                <a
                    href="{{ route('admin.settings.index') }}"
                    class="flex items-center gap-3 px-4 py-3 text-sm transition hover:bg-gray-50">

                    <x-heroicon-o-cog-6-tooth class="h-5 w-5"/>

                    Setting

                </a>


                <form
                    method="POST"
                    action="{{ route('logout') }}">

                    @csrf

                    <button
                        class="flex w-full items-center gap-3 px-4 py-3 text-left text-sm transition hover:bg-gray-50">

                        <x-heroicon-o-arrow-right-on-rectangle class="h-5 w-5"/>

                        Logout

                    </button>

                </form>

            </div>

        </div>

    </div>

</header>