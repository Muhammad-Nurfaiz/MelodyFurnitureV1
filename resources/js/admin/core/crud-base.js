window.crudBase = (config = {}) => ({

    storeUrl: config.storeUrl ?? '',
    updateUrl: config.updateUrl ?? '',

    modal: {},
    alert: {},

    /*
    |--------------------------------------------------------------------------
    | Initialization
    |--------------------------------------------------------------------------
    */

    init() {
        this.reset();
    },

    reset() {
        this.modal = this.defaultModal();
        this.alert = this.defaultAlert();
    },

    /*
    |--------------------------------------------------------------------------
    | Default State
    |--------------------------------------------------------------------------
    */

    defaultData() {
        return {};
    },

    defaultModal() {
        return {
            mode: 'create',
            title: '',
            submitText: '',
            action: '',
            loading: false,
            errors: {},
            data: this.defaultData(),
        };
    },

    defaultAlert() {
        return {
            title: '',
            message: '',
            action: '',
        };
    },

    /*
    |--------------------------------------------------------------------------
    | Form Helpers
    |--------------------------------------------------------------------------
    */

    submit(event) {

        if (this.modal.loading) {
            event.preventDefault();
            return;
        }

        if (typeof this.validate === 'function') {

            if (!this.validate()) {
                event.preventDefault();
                return;
            }

        }

        this.modal.loading = true;

    },

    clearError(field) {

        delete this.modal.errors[field];

    },

    /*
    |--------------------------------------------------------------------------
    | Modal Helpers
    |--------------------------------------------------------------------------
    */

    setData(data = {}) {

        this.modal.data = {
            ...this.defaultData(),
            ...data,
        };

    },

    setModal(options = {}) {

        Object.assign(this.modal, options);

    },

    showModal(name = 'crud-modal') {

        this.$dispatch('open-modal', name);

    },

    hideModal(name = 'crud-modal') {

        this.$dispatch('close-modal', name);

    },

    closeModal(name = 'crud-modal') {

        this.hideModal(name);

        setTimeout(() => {

            this.reset();

        }, 200);

    },

    /*
    |--------------------------------------------------------------------------
    | Alert Helpers
    |--------------------------------------------------------------------------
    */

    showAlert(name = 'delete-modal') {

        this.$dispatch('open-alert', {
            name,
        });

    },

    hideAlert(name = 'delete-modal') {

        this.$dispatch('close-alert', name);

    },

    closeAlert(name = 'delete-modal') {

        this.hideAlert(name);

    },

});