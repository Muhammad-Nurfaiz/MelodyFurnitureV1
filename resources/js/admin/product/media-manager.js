import Sortable from "sortablejs";

export default (existingMedia = []) => ({

    media: [],
    deletedMedia: [],
    sortable: null,
    uploading: false,
    uploadError: null,
    isSubmitting: false,

    init() {

        this.media = existingMedia.map(item => ({
            ...item,
            file: null,
        }));

        this.$nextTick(() => {
            this.initSortable();

        });

        window.addEventListener("beforeunload", () => {
            if (!this.isSubmitting) {
                this.cleanupTemporaryMedia();
            }
        });

        window.addEventListener("pagehide", () => {
            if (!this.isSubmitting) {
                this.cleanupTemporaryMedia();
            }
        });
        window.addEventListener("beforeunload", () => {
            console.log("cleanup...");
        });

    },

    async uploadTemporary(file) {
        const formData = new FormData();
        formData.append("file", file);
        const token = document
            .querySelector('meta[name="csrf-token"]')
            ?.content;
        const response = await fetch("/admin/media/temporary", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": token,
                "Accept": "application/json",
            },
            body: formData,
        });
        if (!response.ok) {
            throw new Error("Upload gagal");
        }
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.message ?? "Upload gagal");
        }

        return data.data;
    },

    async cleanupTemporaryMedia() {

        const ids = this.media
            .filter(item => item.temporary)
            .map(item => item.id);

        if (!ids.length) {
            return;
        }

        try {

            fetch("/admin/media/temporary/cleanup", {
                method: "DELETE",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .content,
                    "Accept": "application/json",
                },
                body: JSON.stringify({
                    ids
                }),
            });

        } catch (e) {

            console.error("Cleanup gagal", e);

        }

    },

    /*
    |--------------------------------------------------------------------------
    | Upload
    |--------------------------------------------------------------------------
    */

    async preview(event) {
        const files = [...event.target.files];
        event.target.value = "";
        const allowed = [
            "image/jpeg",
            "image/png",
            "image/webp",
        ];
        this.uploading = true;
        try {
            for (const file of files) {
                if (!allowed.includes(file.type)) {
                    alert("Format gambar tidak didukung.");
                    continue;
                }
                if (file.size > 2 * 1024 * 1024) {
                    alert("Ukuran maksimal 2 MB.");
                    continue;
                }
                const result = await this.uploadTemporary(file);
                this.media.push({
                    id: result.id,
                    url: result.url,
                    media_url: result.path,
                    uploaded: true,
                    temporary: true,
                    is_main: this.media.length === 0,
                });
            }
            if (
                !this.media.some(item => item.is_main)
                &&
                this.media.length
            ) {
                this.media[0].is_main = true;
            }
            this.$nextTick(() => {
                this.initSortable();
            });
        } finally {
            this.uploading = false;
        }
    },

/*
|--------------------------------------------------------------------------
| Thumbnail
|--------------------------------------------------------------------------
*/

    setMain(index) {

        this.media.forEach(item => item.is_main = false);

        this.media[index].is_main = true;

    },

    /*
    |--------------------------------------------------------------------------
    | Delete
    |--------------------------------------------------------------------------
    */

    async remove(index) {
        const item = this.media[index];
        if (item.temporary) {
            const response = await fetch(
                `/admin/media/temporary/${item.id}`,
                {
                    method: "DELETE",
                    headers: {
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .content,
                        "Accept": "application/json"
                    }
                }
            );

            if (!response.ok) {
                console.error("Gagal menghapus temporary media");
            }
        }
        else if (item.uploaded) {
            this.deletedMedia.push(item.id);
        }
        this.media.splice(index, 1);
        if (
            !this.media.some(x => x.is_main)
            &&
            this.media.length
        ) {
            this.media[0].is_main = true;
        }
    },

    findMain() {

        return this.media.find(item => item.is_main);
    },

/*
|--------------------------------------------------------------------------
| Sortable
|--------------------------------------------------------------------------
*/

    initSortable() {

        const grid = this.$el.querySelector("#media-grid");

        if (!grid) return;

        if (this.sortable) {

            this.sortable.destroy();

        }

        this.sortable = Sortable.create(grid, {

            animation: 180,

            draggable: ".media-item",

            handle: ".drag-handle",

            filter: "label",

            preventOnFilter: false,

            ghostClass: "opacity-40",

            onEnd: (evt) => {

                const moved = this.media.splice(
                    evt.oldIndex,
                    1
                )[0];

                this.media.splice(
                    evt.newIndex,
                    0,
                    moved
                );

            }

        });

    },

    /*
    |--------------------------------------------------------------------------
    | Computed
    |--------------------------------------------------------------------------
    */

    get mainMedia() {

        return this.media.find(item => item.is_main);

    },

        get mainMediaId() {

        return this.mainMedia
            ? this.mainMedia.id
            : "";

    },

        get hasMedia() {

        return this.media.length > 0;

    },

});