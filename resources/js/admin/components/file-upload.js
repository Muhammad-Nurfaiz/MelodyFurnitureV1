export default function fileUpload(config = {}) {
    
    return {

        multiple: config.multiple ?? false,
        temporaryUpload: config.temporaryUpload ?? false,

        previews: [],
        filenames: [],
        drag: false,
        uploading: false,
        uploadError: null,
        init() {

            if (!config.preview) {
                return;
            }

            if (this.multiple) {

                this.previews = config.preview;

            } else {

                this.previews = [config.preview];

            }

        },

        async uploadTemporary(file) {

            const formData = new FormData();

            formData.append("file", file);

            const token = document
                .querySelector('meta[name="csrf-token"]')
                ?.content;

            const response = await fetch(
                "/admin/media/temporary",
                {
                    method: "POST",

                    headers: {
                        Accept: "application/json",
                        "X-CSRF-TOKEN": token,
                    },

                    body: formData,
                }
            );

            if (!response.ok) {
                throw new Error("Upload gagal");
            }

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message);
            }

            return result.data;

        },

        async updatePreview(event) {

            this.clearObjectUrls();

            this.previews = [];
            this.filenames = [];

            const files = [...event.target.files];

            for (const file of files) {

                const preview = URL.createObjectURL(file);

                this.previews.push(preview);

                this.$dispatch("preview-created", {
                    preview,
                });

                this.filenames.push(file.name);

                if (!this.temporaryUpload) {
                    continue;
                }

                this.uploading = true;

                try {

                    this.uploadError = null;

                    const media =
                        await this.uploadTemporary(file);

                    this.$dispatch(
                        "temporary-uploaded",
                        media
                    );

                }
                catch (e) {

                    this.uploadError = e.message;

                }
                finally {

                    this.uploading = false;

                }

            }

        },

        dropFile(event) {

            this.drag = false;

            this.$refs.input.files =
                event.dataTransfer.files;

            this.updatePreview({
                target: this.$refs.input,
            });

        },

        remove(index = null) {

            if (this.multiple && index !== null) {

                const preview =
                    this.previews[index];

                if (
                    preview &&
                    preview.startsWith("blob:")
                ) {
                    URL.revokeObjectURL(preview);
                }

                this.previews.splice(index, 1);

                this.filenames.splice(index, 1);

                const dt = new DataTransfer();

                [...this.$refs.input.files]
                    .forEach((file, i) => {

                        if (i !== index) {
                            dt.items.add(file);
                        }

                    });

                this.$refs.input.files =
                    dt.files;

                return;
            }

            this.clearObjectUrls();

            this.previews = [];

            this.filenames = [];

            this.$refs.input.value = "";

        },

        clearObjectUrls() {

            this.previews.forEach(url => {

                if (
                    typeof url === "string" &&
                    url.startsWith("blob:")
                ) {
                    URL.revokeObjectURL(url);
                }

            });

        },

    };

}