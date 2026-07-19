<div
    x-data="{ loading:false }"
    x-on:submit.window="loading=true"
    x-show="loading"
    x-transition
    class="fixed inset-0 z-[9999] flex items-center justify-center bg-white/70 backdrop-blur-sm"
    style="display:none;">
    <div
        class="rounded-2xl bg-white p-8 shadow-lg">
        <x-admin.feedback.loading
            size="lg"
            text="Mohon tunggu..." />
    </div>
</div>