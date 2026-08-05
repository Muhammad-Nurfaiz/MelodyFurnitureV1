<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        @yield('title', 'Melody Furniture')
    </title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])
</head>

<body
    class="bg-gray-100 antialiased">
    <x-admin.toast.flash />
<div
    x-data="{
        sidebarOpen: false,
        sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
        isMobile: false,
        tooltip:{
            show:false,
            text:'',
            x:0,
            y:0,
        },
        toggleCollapse() {
            this.sidebarCollapsed = !this.sidebarCollapsed;
            localStorage.setItem('sidebarCollapsed', this.sidebarCollapsed);
            this.tooltip.show = false;
        },
        init() {
            const updateScreen = () => {

                this.isMobile = window.innerWidth < 1024;

                if (this.isMobile) {

                    this.sidebarCollapsed = false;

                } else {

                    this.sidebarOpen = false;
                    this.sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
                }

            };

            updateScreen();

            window.addEventListener('resize', updateScreen);
        }
    }"
    class="min-h-screen">

    {{-- Mobile Overlay --}}
    <div
        x-show="sidebarOpen"
        x-transition.opacity
        class="fixed inset-0 z-30 bg-black/40 lg:hidden"
        @click="sidebarOpen=false">
    </div>

    {{-- Sidebar --}}
    @include('admin.layouts.sidebar')

    {{-- Main --}}
    <div

        :class="sidebarCollapsed ? 'lg:ml-20' : 'lg:ml-64'"

        class="min-h-screen transition-all duration-300">

        {{-- Navbar --}}
        @include('admin.layouts.navbar')

        {{-- Page --}}
        <main class="p-6 lg:p-8">

            @yield('content')

        </main>

    </div>

</div>
<div
    x-show="tooltip.show"
    x-cloak
    x-transition.opacity
    :style="`left:${tooltip.x}px;top:${tooltip.y}px`"
    class="
        fixed
        -translate-y-1/2
        pointer-events-none
        z-[9999]
        rounded-lg
        bg-gray-900
        px-3
        py-2
        text-xs
        font-medium
        text-white
        shadow-xl">

    <span x-text="tooltip.text"></span>

</div>
    @stack('scripts')
</body>

</html>