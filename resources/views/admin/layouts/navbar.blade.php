<header
    x-data="{ profileOpen:false }"
    class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-gray-200 bg-white px-6 lg:px-8">

    {{-- Left --}}
    <div class="flex items-center gap-4">

        {{-- Mobile Menu --}}
        <button
            @click="sidebarOpen = true"
            class="rounded-lg p-2 transition hover:bg-gray-100 lg:hidden">

            <x-heroicon-o-bars-3 class="h-6 w-6 text-gray-700"/>

        </button>

        {{-- Breadcrumb --}}
        <div>

            <!-- <nav
                class="mb-1 text-xs text-gray-500">

                <ol class="flex items-center gap-2">

                    <li>

                        Dashboard

                    </li>

                    @hasSection('title')

                        <li>/</li>

                        <li class="font-medium text-gray-700">

                            @yield('title')

                        </li>

                    @endif

                </ol>

            </nav> -->

            <h1 class="text-xl font-bold text-gray-900">

                @yield('title','Dashboard')

            </h1>

        </div>

    </div>

    {{-- Right --}}
    <div class="flex items-center gap-4">

        {{-- WhatsApp --}}
        <div
            class="hidden items-center gap-2 rounded-full bg-green-50 px-3 py-1 md:flex">

            <span class="h-2 w-2 rounded-full bg-green-500"></span>

            <span class="text-sm font-medium text-green-700">

                WhatsApp Online

            </span>

        </div>

        {{-- Notification --}}
        <button
            class="relative rounded-lg p-2 transition hover:bg-gray-100">

            <x-heroicon-o-bell class="h-6 w-6 text-gray-600"/>

            <span
                class="absolute right-2 top-2 h-2 w-2 rounded-full bg-red-500">

            </span>

        </button>

        {{-- Profile --}}
        <div class="relative">

            <button
                @click="profileOpen=!profileOpen"
                class="flex items-center gap-3 rounded-lg px-2 py-2 transition hover:bg-gray-100">

                <div
                    class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100">

                    <x-heroicon-o-user class="h-5 w-5 text-blue-600"/>

                </div>

                <div class="hidden text-left xl:block">

                    <p class="text-sm font-semibold">

                        {{ Auth::user()->full_name }}

                    </p>

                    <p class="text-xs text-gray-500">

                        Administrator

                    </p>

                </div>

                <x-heroicon-o-chevron-down class="h-4 w-4 text-gray-500"/>

            </button>

            <div
                x-show="profileOpen"
                @click.away="profileOpen=false"
                x-transition
                class="absolute right-0 mt-2 w-56 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg">

                <a
                    href="{{ route('profile.edit') }}"
                    class="flex items-center gap-3 px-4 py-3 text-sm transition hover:bg-gray-50">

                    <x-heroicon-o-user-circle class="h-5 w-5"/>

                    Profil

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