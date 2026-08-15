window.shippingRateCrud = (config) => ({

    ...crudBase(config),

    /*
    |--------------------------------------------------------------------------
    | Default State
    |--------------------------------------------------------------------------
    */

    defaultData() {
        return {
            id: null,

            courier: '',
            courier_code: '',

            regency: '',
            regency_id: '',

            province: '',

            rate_type: '',

            price_per_kg: '',
            first_price: '',
            additional_price_per_kg: '',
        };
    },

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    validate() {

        this.modal.errors = {};

        /*
        |--------------------------------------------------------------------------
        | Per KG
        |--------------------------------------------------------------------------
        */

        if (this.modal.data.rate_type === 'per_kg') {

            if (
                this.modal.data.price_per_kg === null ||
                this.modal.data.price_per_kg === '' ||
                this.modal.data.price_per_kg === undefined
            ) {

                this.modal.errors.price_per_kg =
                    'Harga per kg wajib diisi.';

            } else if (
                Number(this.modal.data.price_per_kg) < 0
            ) {

                this.modal.errors.price_per_kg =
                    'Harga per kg tidak boleh kurang dari 0.';

            }

        }

        /*
        |--------------------------------------------------------------------------
        | Tiered
        |--------------------------------------------------------------------------
        */

        if (this.modal.data.rate_type === 'tiered') {

            if (
                this.modal.data.first_price === null ||
                this.modal.data.first_price === '' ||
                this.modal.data.first_price === undefined
            ) {

                this.modal.errors.first_price =
                    'Harga dasar wajib diisi.';

            } else if (
                Number(this.modal.data.first_price) < 0
            ) {

                this.modal.errors.first_price =
                    'Harga dasar tidak boleh kurang dari 0.';

            }


            if (
                this.modal.data.additional_price_per_kg === null ||
                this.modal.data.additional_price_per_kg === '' ||
                this.modal.data.additional_price_per_kg === undefined
            ) {

                this.modal.errors.additional_price_per_kg =
                    'Harga tambahan per kg wajib diisi.';

            } else if (
                Number(this.modal.data.additional_price_per_kg) < 0
            ) {

                this.modal.errors.additional_price_per_kg =
                    'Harga tambahan per kg tidak boleh kurang dari 0.';

            }

        }

        return Object.keys(this.modal.errors).length === 0;

    },

    /*
    |--------------------------------------------------------------------------
    | Open Edit
    |--------------------------------------------------------------------------
    */

    openEdit(rate) {

        this.reset();

        this.setData({
            ...rate,

            /*
            |--------------------------------------------------------------------------
            | Normalize nullable database values
            |--------------------------------------------------------------------------
            */

            price_per_kg:
                rate.price_per_kg ?? '',

            first_price:
                rate.first_price ?? '',

            additional_price_per_kg:
                rate.additional_price_per_kg ?? '',
        });

        this.setModal({

            mode: 'edit',

            title: 'Edit Tarif Shipping',

            submitText: 'Update',

            action: `${this.updateUrl}/${rate.id}`,

        });

        this.showModal('shipping-rate-modal');

    },

});