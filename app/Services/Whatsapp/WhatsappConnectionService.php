<?php

namespace App\Services\Whatsapp;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WhatsappConnectionService
{
    protected function client()
    {
        return Http::withHeaders([
            'X-Api-Key' => config('services.waha.api_key'),
            'Accept' => 'application/json',
        ]);
    }

    protected function baseUrl(): string
    {
        return rtrim(
            config('services.waha.url'),
            '/'
        );
    }

    protected function session(): string
    {
        return config(
            'services.waha.session',
            'Melody'
        );
    }

    /**
     * Mendapatkan informasi session WhatsApp.
     */
    public function status(): array
    {
        try {
            $response = $this->client()
                ->get(
                    $this->baseUrl()
                    . '/api/sessions/'
                    . $this->session()
                );

            if ($response->status() === 404) {
                return [
                    'exists' => false,
                    'status' => 'STOPPED',
                    'session' => $this->session(),
                    'me' => null,
                ];
            }

            $response->throw();

            $data = $response->json();

            return [
                'exists' => true,
                'status' => $data['status'] ?? 'UNKNOWN',
                'session' => $data['name']
                    ?? $this->session(),
                'me' => $data['me'] ?? null,
            ];
        } catch (ConnectionException $e) {
            throw new RuntimeException(
                'WAHA tidak dapat dihubungi.',
                previous: $e
            );
        }
    }

    /**
     * Membuat session jika belum ada.
     */
    public function create(): array
    {
        try {
            $response = $this->client()
                ->post(
                    $this->baseUrl() . '/api/sessions/',
                    [
                        'name' => $this->session(),
                        'start' => false,
                    ]
                );

            /*
             * Session mungkin sudah ada.
             */
            if ($response->status() === 422) {
                return $this->status();
            }

            $response->throw();

            return $response->json();
        } catch (ConnectionException $e) {
            throw new RuntimeException(
                'WAHA tidak dapat dihubungi.',
                previous: $e
            );
        }
    }

    /**
     * Memulai session WhatsApp.
     */
    public function start(): array
    {
        try {
            $current = $this->status();

            if (
                $current['exists'] === false
            ) {
                $this->create();
            }

            $response = $this->client()
                ->post(
                    $this->baseUrl()
                    . '/api/sessions/'
                    . $this->session()
                    . '/start'
                );

            $response->throw();

            return $response->json();
        } catch (ConnectionException $e) {
            throw new RuntimeException(
                'WAHA tidak dapat dihubungi.',
                previous: $e
            );
        }
    }

    /**
     * Mengambil QR WhatsApp dalam format base64.
     */
    public function qr(): array
    {
        try {
            $response = $this->client()
                ->get(
                    $this->baseUrl()
                    . '/api/'
                    . $this->session()
                    . '/auth/qr?format=image'
                );

            /*
            * WAHA dapat mengembalikan:
            *
            * {
            *     "mimetype": "image/png",
            *     "data": "iVBORw0KGgo..."
            * }
            */

            if ($response->successful()) {
                $data = $response->json();

                if (
                    is_array($data)
                    && isset($data['data'])
                    && is_string($data['data'])
                    && $data['data'] !== ''
                ) {
                    return [
                        'mimetype' => $data['mimetype'] ?? 'image/png',
                        'data' => $data['data'],
                    ];
                }
            }

            /*
            * Jika WAHA mengembalikan error,
            * jangan dianggap sebagai QR kosong.
            */
            $error = $response->json();

            $message = is_array($error)
                ? ($error['error'] ?? 'WAHA gagal mengembalikan QR Code.')
                : 'WAHA gagal mengembalikan QR Code.';

            throw new RuntimeException(
                'WAHA tidak mengembalikan QR Code dalam format yang valid. '
                . $message
            );

        } catch (ConnectionException $e) {

            throw new RuntimeException(
                'WAHA tidak dapat dihubungi.',
                previous: $e
            );
        }
    }

    /**
     * Stop session tanpa logout.
     */
    public function stop(): array
    {
        try {

            $response = $this->client()
                ->post(
                    $this->baseUrl()
                    . '/api/sessions/'
                    . $this->session()
                    . '/stop'
                );

            $response->throw();

            $data = $response->json();

            return is_array($data)
                ? $data
                : [];

        } catch (ConnectionException $e) {

            throw new RuntimeException(
                'WAHA tidak dapat dihubungi.',
                previous: $e
            );
        }
    }

    /**
     * Logout WhatsApp.
     */
    public function logout(): array
    {
        try {

            $response = $this->client()
                ->post(
                    $this->baseUrl()
                    . '/api/sessions/'
                    . $this->session()
                    . '/logout'
                );

            $response->throw();

            $data = $response->json();

            return is_array($data)
                ? $data
                : [];

        } catch (ConnectionException $e) {

            throw new RuntimeException(
                'WAHA tidak dapat dihubungi.',
                previous: $e
            );
        }
    }

    /**
     * Restart session.
     */
    public function restart(): array
    {
        try {

            $response = $this->client()
                ->post(
                    $this->baseUrl()
                    . '/api/sessions/'
                    . $this->session()
                    . '/restart'
                );

            $response->throw();

            $data = $response->json();

            return is_array($data)
                ? $data
                : [];

        } catch (ConnectionException $e) {

            throw new RuntimeException(
                'WAHA tidak dapat dihubungi.',
                previous: $e
            );
        }
    }
}