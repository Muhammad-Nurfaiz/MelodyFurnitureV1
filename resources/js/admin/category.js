window.categoryCrud = (config) => ({

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
            this.modal.errors.name = 'Nama kategori wajib diisi.';
        }

        return Object.keys(this.modal.errors).length === 0;

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
            title: 'Tambah Kategori',
            submitText: 'Simpan',
            action: this.storeUrl,
        });

        this.showModal('category-modal');

    },

    openEdit(category) {

        this.reset();

        this.setData(category);

        this.setModal({
            mode: 'edit',
            title: 'Edit Kategori',
            submitText: 'Update',
            action: `${this.updateUrl}/${category.id}`,
        });

        this.showModal('category-modal');

    },

    /*
    |--------------------------------------------------------------------------
    | Delete Alert
    |--------------------------------------------------------------------------
    */

    openDelete(category) {

        this.alert = {
            title: 'Hapus Kategori',
            message: `Kategori "${category.name}" akan dihapus. Tindakan ini tidak dapat dibatalkan.`,
            action: `${this.updateUrl}/${category.id}`,
        };

        this.showAlert('delete-category');

    },

    closeAlert() {

        this.hideAlert('delete-category');

    },

});