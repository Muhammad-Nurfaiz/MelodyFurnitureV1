export default function brandingCrud(config) {

    return {

        updateUrl: config.updateUrl,

        modal: {
            open: false,
            mode: "logo",

            title: "",

            submitText: "Simpan",

            loading: false,

            action: "",

            data: {
                type: "",
                temporary_media_id: "",
            },

            errors: {},
        },


        init() {
        },

        openLogo() {

            this.reset();

            this.modal.mode = "logo";

            this.modal.title = "Ganti Logo";

            this.modal.data.type = "logo";

            this.modal.action = this.updateUrl;

            this.modal.open = true;

            this.$dispatch("open-modal", "branding-modal");
        },

        openFavicon() {

            this.reset();

            this.modal.mode = "favicon";

            this.modal.title = "Ganti Favicon";

            this.modal.data.type = "favicon";

            this.modal.action = this.updateUrl;

            this.modal.open = true;

            this.$dispatch("open-modal", "branding-modal");
        },

        closeModal() {

            this.modal.open = false;

            this.$dispatch("close-modal", "branding-modal");
        },

        reset() {

            this.modal.data = {
                type: "",
                temporary_media_id: "",
            };

            this.modal.errors = {};
        },

        async submit(e) {

            e.preventDefault();

            this.modal.loading = true;

            this.modal.errors = {};

            try {

                const formData = new FormData();

                formData.append(
                    "type",
                    this.modal.data.type
                );

                formData.append(
                    "temporary_media_id",
                    this.modal.data.temporary_media_id
                );

                console.log(
                    "temporary_media_id:",
                    this.modal.data.temporary_media_id
                );

                const response = await fetch(
                    this.modal.action,
                    {
                        method: "POST",

                        headers: {
                            "Accept": "application/json",
                            "X-CSRF-TOKEN":
                                document
                                    .querySelector(
                                        'meta[name="csrf-token"]'
                                    )
                                    .content,
                        },

                        body: (() => {

                            formData.append(
                                "_method",
                                "PATCH"
                            );

                            return formData;

                        })(),
                    }
                );

                const result =
                    await response.json();

                if (!response.ok) {

                    if (
                        response.status === 422
                    ) {

                        this.modal.errors =
                            result.errors ?? {};

                        return;
                    }

                    throw new Error(
                        result.message ??
                        "Terjadi kesalahan."
                    );
                }

                this.closeModal();

                window.location.reload();

            }
            catch (error) {

                console.error(error);

                alert(
                    error.message
                );

            }
            finally {

                this.modal.loading = false;

            }

        },

        

    };
}