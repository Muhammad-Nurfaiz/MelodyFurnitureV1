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
            sku: variant.sku ?? '',
            original_price: variant.original_price ?? '',
            discount_price: variant.discount_price ?? '',
            media_id: variant.media_id ?? '',
            ready_stock: variant.ready_stock ?? '',
        }))
        : [],

    availableMedia: [],

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
            sku: '',
            original_price: '',
            discount_price: '',
            media_id: '',
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

        const initialMedia = window.productAvailableMedia;

        if (Array.isArray(initialMedia)) {
            this.availableMedia = initialMedia;
        }

        window.addEventListener('product-media-updated', (event) => {
            this.availableMedia = event.detail ?? [];
        });
    },

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    validateStep() {
        switch (this.step) {

            /*
            |--------------------------------------------------------------------------
            | STEP 1 — Informasi Produk
            |--------------------------------------------------------------------------
            */

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


            /*
            |--------------------------------------------------------------------------
            | STEP 2 — Spesifikasi Produk
            |--------------------------------------------------------------------------
            */

            case 2:

                if (!this.$refs.dimensions.value.trim()) {
                    alert('Dimensi wajib diisi');
                    return false;
                }

                return true;


            /*
            |--------------------------------------------------------------------------
            | STEP 3 — Media Produk
            |--------------------------------------------------------------------------
            */

            case 3:

                if (!this.availableMedia.length) {
                    alert('Minimal satu media produk harus ditambahkan');
                    return false;
                }

                const hasImage = this.availableMedia.some(
                    media => media.media_type === 'image'
                );

                if (!hasImage) {
                    alert('Minimal satu gambar produk harus ditambahkan');
                    return false;
                }

                return true;


            /*
            |--------------------------------------------------------------------------
            | STEP 4 — Harga, Stok & Varian
            |--------------------------------------------------------------------------
            */

            case 4:

                /*
                |--------------------------------------------------------------------------
                | Harga Produk
                |--------------------------------------------------------------------------
                */

                if (!this.$refs.original_price.value.trim()) {
                    alert('Harga normal wajib diisi');
                    return false;
                }

                const originalPrice = Number(
                    this.$refs.original_price.value
                );

                if (Number.isNaN(originalPrice) || originalPrice < 0) {
                    alert('Harga normal tidak valid');
                    return false;
                }

                if (
                    this.discountPrice !== '' &&
                    this.discountPrice !== null &&
                    this.discountPrice !== undefined
                ) {
                    const discountPrice = Number(this.discountPrice);

                    if (
                        Number.isNaN(discountPrice) ||
                        discountPrice < 0
                    ) {
                        alert('Harga diskon tidak valid');
                        return false;
                    }

                    if (discountPrice >= originalPrice) {
                        alert('Harga diskon harus lebih kecil dari harga normal');
                        return false;
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | Stok / Varian
                |--------------------------------------------------------------------------
                */

                if (this.variantsEnabled) {

                    const filledVariants = this.variants.filter(
                        variant => variant.name.trim() !== ''
                    );

                    if (filledVariants.length === 0) {
                        alert('Minimal satu varian harus diisi');
                        return false;
                    }

                    for (const variant of filledVariants) {

                        if (!variant.name.trim()) {
                            alert('Nama varian wajib diisi');
                            return false;
                        }

                        if (!variant.sku?.trim()) {
                            alert(
                                `SKU varian ${variant.name} wajib diisi`
                            );
                            return false;
                        }

                        if (
                            !/^[A-Z0-9-]+$/.test(
                                variant.sku.trim()
                            )
                        ) {
                            alert(
                                `SKU varian ${variant.name} hanya boleh menggunakan huruf kapital, angka, dan tanda strip (-).`
                            );
                            return false;
                        }

                        if (
                            variant.original_price === '' ||
                            variant.original_price === null ||
                            variant.original_price === undefined
                        ) {
                            alert(
                                `Harga asli varian ${variant.name} wajib diisi`
                            );
                            return false;
                        }

                        const variantOriginalPrice = Number(
                            variant.original_price
                        );

                        if (
                            Number.isNaN(variantOriginalPrice) ||
                            variantOriginalPrice < 0
                        ) {
                            alert(
                                `Harga asli varian ${variant.name} tidak valid`
                            );
                            return false;
                        }

                        if (
                            variant.discount_price !== '' &&
                            variant.discount_price !== null &&
                            variant.discount_price !== undefined
                        ) {
                            const variantDiscountPrice = Number(
                                variant.discount_price
                            );

                            if (
                                Number.isNaN(variantDiscountPrice) ||
                                variantDiscountPrice < 0
                            ) {
                                alert(
                                    `Harga diskon varian ${variant.name} tidak valid`
                                );
                                return false;
                            }

                            if (
                                variantDiscountPrice >=
                                variantOriginalPrice
                            ) {
                                alert(
                                    `Harga diskon varian ${variant.name} harus lebih kecil dari harga asli`
                                );
                                return false;
                            }
                        }

                        if (!variant.media_id) {
                            alert(
                                `Foto varian ${variant.name} wajib dipilih`
                            );
                            return false;
                        }

                        const variantMedia = this.getVariantMedia(
                            variant
                        );

                        if (
                            !variantMedia ||
                            variantMedia.media_type === 'video'
                        ) {
                            alert(
                                `Foto varian ${variant.name} tidak valid`
                            );
                            return false;
                        }

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

                    if (Number(this.$refs.ready_stock.value) < 0) {
                        alert('Stok tidak boleh kurang dari 0');
                        return false;
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | Statistik
                |--------------------------------------------------------------------------
                */

                if (!this.$refs.average_rating.value.trim()) {
                    alert('Nilai Rating wajib diisi');
                    return false;
                }

                if (!this.$refs.total_sold.value.trim()) {
                    alert('Total Terjual wajib diisi');
                    return false;
                }

                return true;
        }

        return true;
    },

    getVariantMedia(variant) {
        if (!variant?.media_id) {
            return null;
        }

        return this.availableMedia.find(
            media => media.id === variant.media_id
        ) ?? null;
    },
});