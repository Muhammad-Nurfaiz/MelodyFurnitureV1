window.productForm = (initial = {}) => ({
    /*
    |--------------------------------------------------------------------------
    | Wizard State
    |--------------------------------------------------------------------------
    */

    step: 1,

    maxStep: 4,

    originalPrice: initial.originalPrice ?? '',

    discountPrice: initial.discountPrice ?? '',

    discountPercentage: initial.discountPercentage ?? '',

    isSale: Boolean(Number(initial.isSale ?? 0)),

    /*
    |--------------------------------------------------------------------------
    | Variant State
    |--------------------------------------------------------------------------
    */

    variantsEnabled: Boolean(initial.variantsEnabled ?? false),

    variants: Array.isArray(initial.variants)
        ? initial.variants.map((variant) => ({
            id: variant.id ?? '',
            name: variant.name ?? '',
            ready_stock: variant.ready_stock ?? '',
        }))
        : [],

    /*
    |--------------------------------------------------------------------------
    | Price
    |--------------------------------------------------------------------------
    */

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
            return;
        }

        this.discountPercentage = Math.round(
            ((original - discount) / original) * 100
        );
    },

    /*
    |--------------------------------------------------------------------------
    | Variant Helpers
    |--------------------------------------------------------------------------
    */

    enableVariants() {
        this.variantsEnabled = true;

        if (this.variants.length === 0) {
            this.addVariant();
        }
    },

    disableVariants() {
        this.variantsEnabled = false;
    },

    addVariant() {
        this.variants.push({
            id: '',
            name: '',
            ready_stock: '',
        });
    },

    removeVariant(index) {
        const variant = this.variants[index];

        if (!variant) {
            return;
        }

        // Baris kosong terakhir tidak memiliki tombol hapus.
        if (
            index === this.variants.length - 1 &&
            !variant.name.trim()
        ) {
            return;
        }

        this.variants.splice(index, 1);

        this.ensureBlankVariantRow();
    },

    ensureBlankVariantRow() {
        if (!this.variantsEnabled) {
            return;
        }

        const lastVariant = this.variants[this.variants.length - 1];

        if (!lastVariant || lastVariant.name.trim() !== '') {
            this.addVariant();
        }
    },

    handleVariantNameInput(index) {
        const variant = this.variants[index];

        if (!variant) {
            return;
        }

        if (
            index === this.variants.length - 1 &&
            variant.name.trim() !== ''
        ) {
            this.addVariant();
        }
    },

    isVariantFilled(variant) {
        return Boolean(variant?.name?.trim());
    },

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    normalizeSku() {
        const input = this.$refs.sku;

        if (!input) {
            return;
        }

        input.value = input.value.toUpperCase();
    },

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

        if (this.variantsEnabled) {
            this.ensureBlankVariantRow();
        }
    },

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    validateStep() {
        switch (this.step) {

            case 1:

                if (!this.$refs.name.value.trim()) {
                    alert('Nama produk wajib diisi');
                    return false;
                }

                if (!this.$refs.sku.value.trim()) {
                    alert('SKU produk wajib diisi');
                    return false;
                }

                const sku = this.$refs.sku.value.trim();

                if (!/^[A-Z0-9-]+$/.test(sku)) {
                    alert(
                        'SKU hanya boleh menggunakan huruf kapital, angka, dan tanda strip (-).'
                    );
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

                return true;

            case 3:

                if (!this.$refs.original_price.value.trim()) {
                    alert('Harga wajib diisi');
                    return false;
                }

                if (this.variantsEnabled) {

                    const filledVariants = this.variants.filter(
                        (variant) => variant.name.trim() !== ''
                    );

                    if (filledVariants.length === 0) {
                        alert('Minimal satu varian warna harus diisi');
                        return false;
                    }

                    for (const variant of filledVariants) {

                        if (
                            variant.ready_stock === '' ||
                            variant.ready_stock === null ||
                            variant.ready_stock === undefined
                        ) {
                            alert(
                                `Stok varian ${variant.name} wajib diisi`
                            );
                            return false;
                        }

                        if (Number(variant.ready_stock) < 0) {
                            alert(
                                `Stok varian ${variant.name} tidak boleh kurang dari 0`
                            );
                            return false;
                        }
                    }

                } else {

                    if (!this.$refs.ready_stock?.value.trim()) {
                        alert('Stok wajib diisi');
                        return false;
                    }

                }

                if (!this.$refs.average_rating.value.trim()) {
                    alert('Nilai Rating wajib diisi');
                    return false;
                }

                return true;

            case 4:
                return true;
        }

        return true;
    },
});