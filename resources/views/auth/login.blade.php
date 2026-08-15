<x-guest-layout>
    <div class="bg-gray-50 flex items-center justify-center px-4 py-8">
        <div class="w-full max-w-md">

            {{-- Login Card --}}
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">

                {{-- Header --}}
                <div class="px-6 sm:px-8 pt-8 pb-6 text-center border-b border-gray-100">

                    {{-- Logo / Brand --}}
                    <div class="flex justify-center mb-5">
                        <div class="w-14 h-14 rounded-xl bg-blue-600 flex items-center justify-center shadow-sm">
                            <svg
                                class="w-7 h-7 text-white"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M3 10.5L12 3l9 7.5M5.5 9.5V21h13V9.5M9 21v-6h6v6"
                                />
                            </svg>
                        </div>
                    </div>

                    <h1 class="text-xl font-bold text-gray-900">
                        Melody Furniture
                    </h1>

                    <p class="mt-1 text-sm text-gray-500">
                        Admin Panel
                    </p>
                </div>

                {{-- Form --}}
                <div class="px-6 sm:px-8 py-7">

                    {{-- Session Status --}}
                    <x-auth-session-status
                        class="mb-5"
                        :status="session('status')"
                    />

                    <form method="POST" action="{{ route('login') }}" class="space-y-5">
                        @csrf

                        {{-- Email Address --}}
                        <div>
                            <x-input-label
                                for="email"
                                :value="__('Email')"
                                class="text-sm font-medium text-gray-700"
                            />

                            <x-text-input
                                id="email"
                                class="block mt-2 w-full rounded-lg border-gray-300 text-sm
                                       focus:border-blue-500 focus:ring-blue-500"
                                type="email"
                                name="email"
                                :value="old('email')"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="Masukkan email admin"
                            />

                            <x-input-error
                                :messages="$errors->get('email')"
                                class="mt-2"
                            />
                        </div>

                        {{-- Password --}}
                        <div>
                            <x-input-label
                                for="password"
                                :value="__('Password')"
                                class="text-sm font-medium text-gray-700"
                            />

                            <x-text-input
                                id="password"
                                class="block mt-2 w-full rounded-lg border-gray-300 text-sm
                                       focus:border-blue-500 focus:ring-blue-500"
                                type="password"
                                name="password"
                                required
                                autocomplete="current-password"
                                placeholder="Masukkan password"
                            />

                            <x-input-error
                                :messages="$errors->get('password')"
                                class="mt-2"
                            />
                        </div>

                        {{-- Remember + Forgot Password --}}
                        <div class="flex items-center justify-between gap-4">

                            <label
                                for="remember_me"
                                class="inline-flex items-center cursor-pointer"
                            >
                                <input
                                    id="remember_me"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-blue-600 shadow-sm
                                           focus:border-blue-500 focus:ring-blue-500"
                                    name="remember"
                                >

                                <span class="ms-2 text-sm text-gray-600">
                                    {{ __('Remember me') }}
                                </span>
                            </label>

                            @if (Route::has('password.request'))
                                <a
                                    class="text-sm font-medium text-blue-600 hover:text-blue-700
                                           rounded-md focus:outline-none focus:ring-2
                                           focus:ring-blue-500 focus:ring-offset-2"
                                    href="{{ route('password.request') }}"
                                >
                                    {{ __('Forgot your password?') }}
                                </a>
                            @endif

                        </div>

                        {{-- Login Button --}}
                        <button
                            type="submit"
                            class="w-full inline-flex items-center justify-center
                                   px-4 py-2.5 rounded-lg
                                   bg-blue-600 text-white text-sm font-semibold
                                   hover:bg-blue-700
                                   focus:outline-none focus:ring-2
                                   focus:ring-blue-500 focus:ring-offset-2
                                   transition-colors
                                   disabled:cursor-not-allowed
                                   disabled:opacity-50"
                        >
                            <svg
                                class="w-4 h-4 mr-2"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4M10 17l5-5-5-5M15 12H3"
                                />
                            </svg>

                            {{ __('Log in') }}
                        </button>
                    </form>
                </div>
            </div>

            {{-- Footer --}}
            <div class="mt-5 text-center">
                <p class="text-xs text-gray-400">
                    &copy; {{ date('Y') }} Melody Furniture. All rights reserved.
                </p>
            </div>

        </div>
    </div>
</x-guest-layout>