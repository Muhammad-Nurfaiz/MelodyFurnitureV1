async function sendWorkflowRequest(button) {

    const url = button.dataset.url;
    const method = (button.dataset.method ?? 'PATCH').toUpperCase();

    if (!url) return;

    const confirmed = confirm(
        `Apakah Anda yakin ingin melakukan aksi "${button.innerText.trim()}"?`
    );

    if (!confirmed) return;

    button.disabled = true;

    try {

        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content');

        if (!csrfToken) {
            throw new Error('CSRF token tidak ditemukan.');
        }

        const headers = {
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        };

        const options = {
            method: method,
            headers: headers,
            credentials: 'same-origin',
        };

        /*
        |--------------------------------------------------------------------------
        | POST
        |--------------------------------------------------------------------------
        |
        | Digunakan ketika workflow membuat shipment.
        |
        */

        if (method === 'POST') {

            headers['Content-Type'] = 'application/json';

            options.body = JSON.stringify({
                booking_code: null,
                tracking_number: null,
                label_url: null,
                status: 'waiting_pickup',
                metadata: null,
            });
        }

        const response = await fetch(url, options);

        const contentType =
            response.headers.get('content-type') ?? '';

        let result;

        if (contentType.includes('application/json')) {

            result = await response.json();

        } else {

            const text = await response.text();

            throw new Error(
                text || `Request gagal (${response.status}).`
            );
        }

        if (!response.ok) {

            throw new Error(
                result.message ??
                result.error ??
                'Terjadi kesalahan.'
            );
        }

        alert(result.message);

        window.location.reload();

    } catch (error) {

        console.error('Workflow error:', error);

        alert(error.message);

    } finally {

        button.disabled = false;

    }
}


document.addEventListener('DOMContentLoaded', () => {

    document
        .querySelectorAll('#workflow-action')
        .forEach(button => {

            button.addEventListener('click', () => {
                sendWorkflowRequest(button);
            });

        });

});