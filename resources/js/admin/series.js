window.seriesCrud = (config) => ({

    ...crudBase(config),

    /*
    |--------------------------------------------------------------------------
    | Default State
    |--------------------------------------------------------------------------
    */

    defaultData() {
        return {
            id: null,
            name: '',
            description: '',
        };
    },

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    validate() {

        this.modal.errors = {};

        if (!this.modal.data.name?.trim()) {
            this.modal.errors.name = 'Nama series wajib diisi.';
        }

        if (!this.modal.data.description?.trim()) {
            this.modal.errors.description = 'Deskripsi series wajib diisi.';
        }

        return Object.keys(this.modal.errors).length === 0;

    },

    /*
    |--------------------------------------------------------------------------
    | Data Helpers
    |--------------------------------------------------------------------------
    */

    setData(data = {}) {

        this.modal.data = {
            ...this.defaultData(),
            ...data,
        };

        this.modal.data.name = this.modal.data.name?.trim() ?? '';
        this.modal.data.description = this.modal.data.description?.trim() ?? '';

    },

    /*
    |--------------------------------------------------------------------------
    | CRUD Modal
    |--------------------------------------------------------------------------
    */

    openCreate() {

        this.reset();

        this.setModal({
            mode: 'create',
            title: 'Tambah Series',
            submitText: 'Simpan',
            action: this.storeUrl,
        });

        this.showModal('series-modal');

    },

    openEdit(series) {

        this.reset();

        this.setData(series);

        this.setModal({
            mode: 'edit',
            title: 'Edit Series',
            submitText: 'Update',
            action: `${this.updateUrl}/${series.id}`,
        });

        this.showModal('series-modal');

    },

    /*
    |--------------------------------------------------------------------------
    | Delete Alert
    |--------------------------------------------------------------------------
    */

    openDelete(series) {

        this.$dispatch('open-alert', {
            name: 'delete-series',

            title: 'Hapus Series',

            message: `Series "${series.name}" akan dihapus. Tindakan ini tidak dapat dibatalkan.`,

            action: `${this.updateUrl}/${series.id}`,
        });

    },

    closeAlert() {
        this.$dispatch('close-alert','delete-series');
    },

});