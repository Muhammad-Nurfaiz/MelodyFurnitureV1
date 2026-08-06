export default function heroCrud(config) {
    return {
        storeUrl: config.storeUrl,
        updateUrl: config.updateUrl,
        deleteUrl: config.deleteUrl,

        modal: {
            open: false,
            mode: 'create',

            action: '',
            title: '',
            submitText: '',
            loading: false,

            data: {
                id: null,
                temporary_media_id: null,

                image: null,
                imagePreview: null,

                eyebrow: '',
                title: '',
                description: '',

                button_text: '',
                button_url: '',

                is_active: true,
            },

            errors: {},
        },


        /*
        |--------------------------------------------------------------------------
        | Init
        |--------------------------------------------------------------------------
        */

        init() {
            // Reserved for future initialization.
        },


        /*
        |--------------------------------------------------------------------------
        | CREATE
        |--------------------------------------------------------------------------
        */

        openCreate() {
            this.resetModal();

            this.modal.mode = 'create';

            this.modal.title = 'Tambah Hero';

            this.modal.submitText = 'Simpan Hero';

            this.modal.action = this.storeUrl;

            this.modal.open = true;

            this.$dispatch('open-modal', 'hero-modal');
        },


        /*
        |--------------------------------------------------------------------------
        | EDIT
        |--------------------------------------------------------------------------
        */

        openEdit(hero) {
            this.resetModal();

            this.modal.mode = 'edit';

            this.modal.title = 'Edit Hero';

            this.modal.submitText = 'Simpan Perubahan';

            this.modal.action = `${this.updateUrl}/${hero.id}`;

            this.modal.data = {

                id: hero.id,

                temporary_media_id: null,

                image: hero.image ?? null,

                imagePreview: null,

                eyebrow: hero.eyebrow ?? '',

                title: hero.title ?? '',

                description: hero.description ?? '',

                button_text: hero.button_text ?? '',

                button_url: hero.button_url ?? '',

                is_active: Boolean(hero.is_active),

            };

            this.modal.open = true;

            this.$dispatch('open-modal', 'hero-modal');
        },


        /*
        |--------------------------------------------------------------------------
        | RESET
        |--------------------------------------------------------------------------
        */

        resetModal() {

            this.modal.loading = false;

            this.modal.errors = {};

            this.modal.data = {

                id: null,

                temporary_media_id: null,

                image: null,

                imagePreview: null,

                eyebrow: '',

                title: '',

                description: '',

                button_text: '',

                button_url: '',

                is_active: true,

            };

            this.modal.action = '';
            this.modal.title = '';
            this.modal.submitText = '';

        },


        /*
        |--------------------------------------------------------------------------
        | CLOSE
        |--------------------------------------------------------------------------
        */

        closeModal() {

            this.modal.open = false;

            this.modal.loading = false;

            this.$dispatch('close-modal', 'hero-modal');

        },


        /*
        |--------------------------------------------------------------------------
        | IMAGE
        |--------------------------------------------------------------------------
        */

        async handleImageChange(event) {

            const file = event.target.files?.[0];

            if (!file) {
                return;
            }

            this.modal.loading = true;

            try {

                /*
                |--------------------------------------------------------------------------
                | Validate type
                |--------------------------------------------------------------------------
                */

                const allowedTypes = [
                    'image/jpeg',
                    'image/png',
                    'image/webp',
                ];

                if (!allowedTypes.includes(file.type)) {

                    throw new Error(
                        'Format gambar harus JPG, PNG, atau WEBP.'
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | Validate size
                |--------------------------------------------------------------------------
                */

                if (file.size > 2 * 1024 * 1024) {

                    throw new Error(
                        'Ukuran gambar maksimal 2 MB.'
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | Upload temporary media
                |--------------------------------------------------------------------------
                */

                const result =
                    await this.uploadTemporary(file);



                /*
                |--------------------------------------------------------------------------
                | Save temporary media ID
                |--------------------------------------------------------------------------
                */

                this.modal.data.temporary_media_id =
                    result.id;


                /*
                |--------------------------------------------------------------------------
                | Save image path
                |--------------------------------------------------------------------------
                */

                this.modal.data.image =
                    result.path ?? null;


                /*
                |--------------------------------------------------------------------------
                | Preview
                |--------------------------------------------------------------------------
                */

                if (this.modal.data.imagePreview) {

                    URL.revokeObjectURL(
                        this.modal.data.imagePreview
                    );

                }

                this.modal.data.imagePreview =
                    URL.createObjectURL(file);


                /*
                |--------------------------------------------------------------------------
                | Clear errors
                |--------------------------------------------------------------------------
                */

                delete this.modal.errors.image;

                delete this.modal.errors.temporary_media_id;


            } catch (error) {

                this.modal.data.temporary_media_id = null;

                this.modal.data.image = null;

                this.modal.data.imagePreview = null;

                event.target.value = '';

                this.modal.errors.image =
                    error.message ??
                    'Gagal mengupload gambar.';

            } finally {

                this.modal.loading = false;

            }

        },


        /*
        |--------------------------------------------------------------------------
        | REMOVE IMAGE
        |--------------------------------------------------------------------------
        */

        removeImage() {

            this.modal.data.image = null;

            this.modal.data.imagePreview = null;

            const input =
                document.getElementById('hero-image');

            if (input) {
                input.value = '';
            }

        },


        /*
        |--------------------------------------------------------------------------
        | ERROR
        |--------------------------------------------------------------------------
        */

        clearError(field) {

            if (this.modal.errors[field]) {

                delete this.modal.errors[field];

            }

        },


        /*
        |--------------------------------------------------------------------------
        | SUBMIT
        |--------------------------------------------------------------------------
        */

        async submit(event) {

            event.preventDefault();

            if (this.modal.loading) {
                return;
            }


            this.modal.loading = true;

            this.modal.errors = {};


            try {

                const form =
                    event.currentTarget;

                const formData =
                    new FormData(form);


                /*
                |--------------------------------------------------------------------------
                | Active
                |--------------------------------------------------------------------------
                */

                formData.set(
                    'is_active',
                    this.modal.data.is_active ? '1' : '0'
                );


                /*
                |--------------------------------------------------------------------------
                | Image
                |--------------------------------------------------------------------------
                */

                if (
                    this.modal.data.image &&
                    this.modal.data.image instanceof File
                ) {

                    formData.set(
                        'image',
                        this.modal.data.image
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | Request
                |--------------------------------------------------------------------------
                */

                const response =
                    await fetch(
                        this.modal.action,
                        {
                            method: 'POST',

                            headers: {
                                'X-CSRF-TOKEN':
                                    document
                                        .querySelector(
                                            'meta[name="csrf-token"]'
                                        )
                                        ?.getAttribute('content'),

                                'Accept':
                                    'application/json',

                                'X-Requested-With':
                                    'XMLHttpRequest',
                            },

                            body: formData,
                        }
                    );


                const data =
                    await response.json();


                /*
                |--------------------------------------------------------------------------
                | Validation Error
                |--------------------------------------------------------------------------
                */

                if (response.status === 422) {

                    this.modal.errors =
                        data.errors ?? {};

                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | Other Error
                |--------------------------------------------------------------------------
                */

                if (!response.ok) {

                    throw new Error(
                        data.message ||
                        'Terjadi kesalahan saat menyimpan hero.'
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | Success
                |--------------------------------------------------------------------------
                */

                this.closeModal();

                window.location.reload();

            } catch (error) {

                alert(
                    error.message ||
                    'Terjadi kesalahan saat menyimpan hero.'
                );

            } finally {

                this.modal.loading = false;

            }

        },


        /*
        |--------------------------------------------------------------------------
        | DELETE
        |--------------------------------------------------------------------------
        */

        async deleteHero(id) {

            if (!id) {
                return;
            }


            const confirmed =
                confirm(
                    'Apakah Anda yakin ingin menghapus hero ini?'
                );


            if (!confirmed) {
                return;
            }


            try {
                const response =
                    await fetch(
                        `${this.deleteUrl}/${id}`,
                        {
                            method: 'DELETE',

                            headers: {
                                'X-CSRF-TOKEN':
                                    document
                                        .querySelector(
                                            'meta[name="csrf-token"]'
                                        )
                                        ?.getAttribute('content'),

                                'Accept':
                                    'application/json',

                                'X-Requested-With':
                                    'XMLHttpRequest',
                            },
                        }
                    );


                const data =
                    await response.json();


                if (!response.ok) {

                    throw new Error(
                        data.message ||
                        'Hero gagal dihapus.'
                    );

                }


                window.location.reload();

            } catch (error) {

                alert(
                    error.message ||
                    'Terjadi kesalahan saat menghapus hero.'
                );

            }

        },

        async uploadTemporary(file) {
            const formData = new FormData();

            formData.append('file', file);

            const token = document
                .querySelector('meta[name="csrf-token"]')
                ?.content;

            const response = await fetch(
                '/admin/media/temporary',
                {
                    method: 'POST',

                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                    },

                    body: formData,
                }
            );

            if (!response.ok) {
                throw new Error(
                    'Upload gambar gagal.'
                );
            }

            const result = await response.json();

            if (!result.success) {
                throw new Error(
                    result.message ?? 'Upload gambar gagal.'
                );
            }

            return result.data;
        },

    };
}