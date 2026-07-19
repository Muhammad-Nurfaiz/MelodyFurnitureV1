<x-admin.toast.container>
    @if(session('success'))
        <x-admin.toast.toast
            type="success"
            title="Berhasil">
            {{ session('success') }}
        </x-admin.toast.toast>
    @endif
    @if(session('error'))
        <x-admin.toast.toast
            type="danger"
            title="Terjadi Kesalahan">
            {{ session('error') }}
        </x-admin.toast.toast>
    @endif
    @if(session('warning'))
        <x-admin.toast.toast
            type="warning"
            title="Peringatan">
            {{ session('warning') }}
        </x-admin.toast.toast>
    @endif
    @if(session('info'))
        <x-admin.toast.toast
            type="info"
            title="Informasi">
            {{ session('info') }}
        </x-admin.toast.toast>
    @endif
</x-admin.toast.container>