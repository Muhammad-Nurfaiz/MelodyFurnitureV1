@extends('admin.layouts.app')

@section('title', 'Profil Admin')

@section('content')

<div class="space-y-6">

    <x-admin.page-header
        title="Profil Admin"
        description="Kelola informasi profil dan keamanan akun admin."
    />

    {{-- ===================================================== --}}
    {{-- PROFILE INFORMATION --}}
    {{-- ===================================================== --}}

    <x-admin.card>

        <x-admin.card-header
            title="Informasi Profil"
            description="Perbarui informasi akun administrator."
        />

        <x-admin.card-body>

            <form
                method="POST"
                action="{{ route('admin.profile.update') }}"
                enctype="multipart/form-data"
                class="space-y-6"
            >

                @csrf
                @method('PATCH')

                {{-- PROFILE PHOTO --}}

                <div>

                    <p class="text-sm font-semibold text-slate-700">
                        Foto Profil
                    </p>

                    <div class="mt-4 flex items-center gap-5">

                        <div class="shrink-0">

                            @if($admin->profile_photo)

                                <img
                                    src="{{ asset('storage/' . $admin->profile_photo) }}"
                                    alt="{{ $admin->full_name }}"
                                    class="h-20 w-20 rounded-full object-cover border border-slate-200"
                                >

                            @else

                                <div
                                    class="
                                        flex
                                        h-20
                                        w-20
                                        items-center
                                        justify-center
                                        rounded-full
                                        bg-slate-100
                                        text-2xl
                                        font-bold
                                        text-slate-500
                                    "
                                >
                                    {{ strtoupper(substr($admin->full_name, 0, 1)) }}
                                </div>

                            @endif

                        </div>

                        <div>

                            <input
                                type="file"
                                name="profile_photo"
                                accept="image/jpeg,image/png,image/webp"
                                class="
                                    block
                                    w-full
                                    text-sm
                                    text-slate-600
                                "
                            >

                            <p class="mt-1 text-xs text-slate-500">
                                JPG, PNG, atau WEBP. Maksimal 2 MB.
                            </p>

                            @error('profile_photo')

                                <p class="mt-1 text-sm text-red-600">
                                    {{ $message }}
                                </p>

                            @enderror

                        </div>

                    </div>

                </div>

                {{-- FULL NAME --}}

                <x-admin.form.group
                    label="Nama Lengkap"
                    required
                >

                    <x-admin.form.input
                        name="full_name"
                        value="{{ old('full_name', $admin->full_name) }}"
                        placeholder="Masukkan nama lengkap..."
                    />

                    @error('full_name')

                        <p class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </p>

                    @enderror

                </x-admin.form.group>

                {{-- EMAIL --}}

                <x-admin.form.group
                    label="Email"
                    required
                >

                    <x-admin.form.input
                        type="email"
                        name="email"
                        value="{{ old('email', $admin->email) }}"
                        placeholder="Masukkan email..."
                    />

                    @error('email')

                        <p class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </p>

                    @enderror

                </x-admin.form.group>

                {{-- PHONE --}}

                <x-admin.form.group
                    label="Nomor HP"
                    required
                >

                    <x-admin.form.input
                        name="phone_number"
                        value="{{ old('phone_number', $admin->phone_number) }}"
                        placeholder="Masukkan nomor HP..."
                    />

                    @error('phone_number')

                        <p class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </p>

                    @enderror

                </x-admin.form.group>

                <div class="flex justify-end">

                    <x-admin.button type="submit">

                        Simpan Perubahan

                    </x-admin.button>

                </div>

            </form>

        </x-admin.card-body>

    </x-admin.card>


    {{-- ===================================================== --}}
    {{-- PASSWORD --}}
    {{-- ===================================================== --}}

    <x-admin.card>

        <x-admin.card-header
            title="Keamanan Akun"
            description="Ubah password akun administrator."
        />

        <x-admin.card-body>

            <form
                method="POST"
                action="{{ route('admin.profile.password') }}"
                class="max-w-xl space-y-5"
            >

                @csrf
                @method('PATCH')

                <x-admin.form.group
                    label="Password Saat Ini"
                    required
                >

                    <x-admin.form.input
                        type="password"
                        name="current_password"
                        placeholder="Masukkan password saat ini..."
                    />

                    @error('current_password')

                        <p class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </p>

                    @enderror

                </x-admin.form.group>

                <x-admin.form.group
                    label="Password Baru"
                    required
                >

                    <x-admin.form.input
                        type="password"
                        name="new_password"
                        placeholder="Masukkan password baru..."
                    />

                    @error('password')

                        <p class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </p>

                    @enderror

                </x-admin.form.group>

                <x-admin.form.group
                    label="Konfirmasi Password"
                    required
                >

                    <x-admin.form.input
                        type="password"
                        name="new_password_confirmation"
                        placeholder="Ulangi password baru..."
                    />

                </x-admin.form.group>

                <div class="flex justify-end">

                    <x-admin.button
                        type="submit"
                        variant="primary"
                    >

                        Ubah Password

                    </x-admin.button>

                </div>

            </form>

        </x-admin.card-body>

    </x-admin.card>

</div>

@endsection