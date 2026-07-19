window.productForm = () => ({

    /*
    |--------------------------------------------------------------------------
    | Wizard State
    |--------------------------------------------------------------------------
    */

    step: 1,

    maxStep: 4,

    originalPrice: '',

    discountPrice: '',

    discountPercentage: '',
    
    isSale: false,

    calculateDiscount() {

        const original = parseFloat(this.originalPrice);
        const discount = parseFloat(this.discountPrice);

        if (
            isNaN(original) ||
            isNaN(discount) ||
            original <= 0 ||
            discount <= 0 ||
            discount >= original
        ) {

            this.discountPercentage = '';
            this.isSale = false;
            return;
        }

        this.discountPercentage = Math.round(
            ((original - discount) / original) * 100
        );

        this.isSale = true;

    },

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    isStep(step) {
        return this.step === step;
    },

    canNext() {
        return this.step < this.maxStep;
    },

    canPrevious() {
        return this.step > 1;
    },

    /*
    |--------------------------------------------------------------------------
    | Navigation
    |--------------------------------------------------------------------------
    */

    nextStep() {

        if (!this.validateStep()) {
            return;
        }

        if (this.step < this.maxStep) {
            this.step++;
        }

    },

    prevStep() {

        if (this.canPrevious()) {
            this.step--;
        }

    },

    goTo(step) {

        if (step >= 1 && step <= this.maxStep) {
            this.step = step;
        }

    },

    init() {

        this.calculateDiscount();

    },
    validateStep() {

        switch (this.step) {

            case 1:

                if (!this.$refs.name.value.trim()) {
                    alert('Nama produk wajib diisi');
                    return false;
                }

                if (!this.$refs.category.value) {
                    alert('Kategori wajib dipilih');
                    return false;
                }

                if (!this.$refs.description.value.trim()) {
                    alert('Deskripsi wajib diisi');
                    return false;
                }
                if (!this.$refs.product_detail.value.trim()) {
                    alert('Detail Produk wajib diisi');
                    return false;
                }

                return true;

            case 2:

                if (!this.$refs.dimensions.value.trim()) {
                    alert('Dimensi wajib diisi');
                    return false;
                }
                if (!this.$refs.material_details.value.trim()) {
                    alert('Detail Material wajib diisi');
                    return false;
                }
                
                return true;

            case 3:
                if (!this.$refs.original_price.value.trim()) {
                    alert('Harga wajib diisi');
                    return false;
                }
                if (!this.$refs.discount_price.value.trim()) {
                    alert('Harga Diskon wajib diisi');
                    return false;
                }
                if (!this.$refs.ready_stock.value.trim()) {
                    alert('Stok wajib diisi');
                    return false;
                }
                if (!this.$refs.average_rating.value.trim()) {
                    alert('Nilai Rating wajib diisi');
                    return false;
                }

                return true;

            case 4:
                return true;
        }

    },

});