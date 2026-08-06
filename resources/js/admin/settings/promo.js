import Sortable from "sortablejs";

export default (config = {}) => ({

    /*
    |--------------------------------------------------------------------------
    | Config
    |--------------------------------------------------------------------------
    */

    storeUrl: config.storeUrl,
    updateUrl: config.updateUrl,
    deleteUrl: config.deleteUrl,
    sortUrl: config.sortUrl,

    /*
    |--------------------------------------------------------------------------
    | State
    |--------------------------------------------------------------------------
    */

    sortable: null,

    sorting: false,

    promoModal: {

        open: false,

        loading: false,

        title: "",

        submitText: "Simpan",

        errors: {},

        data: {

            id: null,

            temporary_media_id: null,

            image: null,

            imagePreview: null,

            url: "",

            alt: "",

            is_active: true,

        },

    },

    /*
    |--------------------------------------------------------------------------
    | Init
    |--------------------------------------------------------------------------
    */

    init() {

        this.initSortable();

    },

    /*
    |--------------------------------------------------------------------------
    | Sortable
    |--------------------------------------------------------------------------
    */

    initSortable() {

        this.$nextTick(() => {

            const container = this.$refs.sortableContainer;

            if (!container) {
                return;
            }

            this.sortable = Sortable.create(container, {

                animation: 200,

                ghostClass: "sortable-ghost",

                chosenClass: "sortable-chosen",

                dragClass: "sortable-drag",

                handle: ".data-sort-handle",

                onEnd: async () => {

                    const ids = [...container.children]
                        .map(item => item.dataset.id);

                    await this.saveOrder(ids);

                },

            });

        });

    },

    /*
    |--------------------------------------------------------------------------
    | Modal
    |--------------------------------------------------------------------------
    */

    resetPromoModal() {

        this.promoModal = {

            open: false,

            loading: false,

            title: "",

            submitText: "Simpan",

            errors: {},

            data: {

                id: null,

                temporary_media_id: null,

                image: null,

                imagePreview: null,

                url: "",

                alt: "",

                is_active: true,

            },

        };

    },

    fillData(data = {}) {

        this.promoModal.data = {

            id: data.id ?? null,

            temporary_media_id: null,

            image: data.image ?? null,

            imagePreview: data.image ?? null,

            url: data.url ?? "",

            alt: data.alt ?? "",

            is_active: !!data.is_active,

        };

    },

    openCreatePromo() {

        this.resetPromoModal();

        this.promoModal.title =
            "Tambah Promo Banner";

        this.promoModal.submitText =
            "Simpan Banner";

        this.$dispatch(
            "open-modal",
            "promo-modal"
        );

    },

    openEditPromo(data) {

        this.resetPromoModal();

        this.fillData(data);

        this.promoModal.title =
            "Edit Promo Banner";

        this.promoModal.submitText =
            "Update Banner";

        this.$dispatch(
            "open-modal",
            "promo-modal"
        );

    },

    closePromoModal() {

        this.resetPromoModal();

        this.$dispatch(
            "close-modal",
            "promo-modal"
        );

    },

    /*
    |--------------------------------------------------------------------------
    | CRUD
    |--------------------------------------------------------------------------
    */

    async submitPromo(event) {

        event.preventDefault();

        if (this.promoModal.loading) {
            return;
        }

        this.clearErrors();

        this.setLoading(true);

        try {

            const formData = this.buildFormData();

            let response;

            if (this.promoModal.data.id) {

                formData.append("_method", "PUT");

                response = await axios.post(

                    `${this.updateUrl}/${this.promoModal.data.id}`,

                    formData,

                    {
                        headers: {
                            "Content-Type": "multipart/form-data",
                        },
                    }

                );

            } else {

                response = await axios.post(

                    this.storeUrl,

                    formData,

                    {
                        headers: {
                            "Content-Type": "multipart/form-data",
                        },
                    }

                );

            }

            this.success(

                response.data.message ??

                "Promo banner berhasil disimpan."

            );

            this.refreshPage();

        }

        catch (error) {

            if (error.response?.status === 422) {

                this.setErrors(

                    error.response.data.errors ?? {}

                );

                return;

            }

            this.failed(

                error.response?.data?.message ??

                "Terjadi kesalahan."

            );

        }

        finally {

            this.setLoading(false);

        }

    },

    async deletePromo(id) {

        if (!confirm("Hapus banner ini?")) {
            return;
        }

        try {

            const response = await axios.delete(

                `${this.deleteUrl}/${id}`

            );

            this.success(

                response.data.message ??

                "Banner berhasil dihapus."

            );

            this.refreshPage();

        }

        catch (error) {

            this.failed(

                error.response?.data?.message ??

                "Gagal menghapus banner."

            );

        }

    },

    async saveOrder(ids) {

        if (this.sorting) return;

        this.sorting = true;

        try {

            if (this.sortable) {
                this.sortable.option("disabled", true);
            }

            const { data } = await axios.patch(
                this.sortUrl,
                { ids }
            );

            this.success(
                data.message ??
                "Urutan banner berhasil diperbarui."
            );

        } catch (error) {

            this.failed(
                error.response?.data?.message ??
                "Gagal memperbarui urutan banner."
            );

        } finally {

            this.sorting = false;

            if (this.sortable) {
                this.sortable.option("disabled", false);
            }

        }

    },

    restoreOrder(container, ids) {

        ids.forEach(id => {

            const el = container.querySelector(
                `[data-id="${id}"]`
            );

            if (el) {

                container.appendChild(el);

            }

        });

    },

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    buildFormData() {

        const formData = new FormData();

        if (this.promoModal.data.temporary_media_id) {

            formData.append(
                "temporary_media_id",
                this.promoModal.data.temporary_media_id
            );

        }

        formData.append(
            "url",
            this.promoModal.data.url ?? ""
        );

        formData.append(
            "alt",
            this.promoModal.data.alt ?? ""
        );

        formData.append(
            "is_active",
            this.promoModal.data.is_active ? 1 : 0
        );

        return formData;

    },

    setErrors(errors = {}) {

        this.promoModal.errors = errors;

    },

    clearErrors() {

        this.promoModal.errors = {};

    },

    setLoading(status = true) {

        this.promoModal.loading = status;

    },

    /*
    |--------------------------------------------------------------------------
    | Image Preview
    |--------------------------------------------------------------------------
    */

    updatePreview(url) {

        this.promoModal.data.image = url;

        this.promoModal.data.imagePreview = url;

    },

    removeImage() {

        this.promoModal.data.image = null;

        this.promoModal.data.imagePreview = null;

        this.promoModal.data.temporary_media_id = null;

    },

    /*
    |--------------------------------------------------------------------------
    | Notification
    |--------------------------------------------------------------------------
    */

    success(message = "Berhasil") {

        this.closePromoModal();

        window.dispatchEvent(

            new CustomEvent("notify", {

                detail: {

                    type: "success",

                    message,

                },

            })

        );

    },

    failed(message = "Terjadi kesalahan") {

        window.dispatchEvent(

            new CustomEvent("notify", {

                detail: {

                    type: "error",

                    message,

                },

            })

        );

    },

    /*
    |--------------------------------------------------------------------------
    | Refresh
    |--------------------------------------------------------------------------
    */

    refreshPage() {

        window.location.reload();

    },

});