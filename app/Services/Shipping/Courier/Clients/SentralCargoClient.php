<?php

namespace App\Services\Shipping\Courier\Clients;

use App\Models\Order;
use App\Services\Shipping\Courier\CourierShipmentResult;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class SentralCargoClient
{
    protected array $config;

    public function __construct()
    {
        $this->config = config(
            'shipping.couriers.sentral_cargo',
            []
        );
    }

    /**
     * Membuat HTTP client dasar.
     */
    protected function client(): PendingRequest
    {
        $request = Http::timeout(
            (int) ($this->config['timeout'] ?? 30)
        )
        ->acceptJson();

        $baseUrl = $this->config['base_url'] ?? null;

        if (filled($baseUrl)) {
            $request->baseUrl(
                rtrim($baseUrl, '/')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Authentication
        |--------------------------------------------------------------------------
        |
        | Jangan menebak nama header atau mekanisme authentication
        | Sentral Cargo.
        |
        | Akan disesuaikan berdasarkan dokumentasi resmi dan
        | credential perusahaan.
        |
        */

        return $request;
    }

    /**
     * Create shipment.
     *
     * Endpoint dan payload belum diimplementasikan
     * sampai dokumentasi resmi Sentral Cargo tersedia.
     */
    public function createShipment(
        Order $order
    ): CourierShipmentResult {

        return CourierShipmentResult::failed(
            'Integrasi create shipment Sentral Cargo belum dikonfigurasi.'
        );
    }

    /**
     * Update shipment.
     */
    public function updateShipment(
        Order $order
    ): CourierShipmentResult {

        return CourierShipmentResult::failed(
            'Integrasi update shipment Sentral Cargo belum dikonfigurasi.'
        );
    }

    /**
     * Cancel shipment.
     */
    public function cancelShipment(
        Order $order
    ): bool {

        /*
        |--------------------------------------------------------------------------
        | IMPORTANT
        |--------------------------------------------------------------------------
        |
        | Belum melakukan request ke Sentral Cargo.
        | Implementasi menunggu:
        |
        | - endpoint cancellation
        | - authentication
        | - request payload
        | - response format
        |
        */

        return false;
    }

    /**
     * Tracking shipment.
     */
    public function tracking(
        Order $order
    ): CourierShipmentResult {

        return CourierShipmentResult::failed(
            'Integrasi tracking Sentral Cargo belum dikonfigurasi.'
        );
    }

    /**
     * GET request.
     */
    public function get(
        string $uri,
        array $query = []
    ): Response {
        return $this->client()->get(
            $uri,
            $query
        );
    }

    /**
     * POST request.
     */
    public function post(
        string $uri,
        array $data = []
    ): Response {
        return $this->client()->post(
            $uri,
            $data
        );
    }

    /**
     * PUT request.
     */
    public function put(
        string $uri,
        array $data = []
    ): Response {
        return $this->client()->put(
            $uri,
            $data
        );
    }

    /**
     * DELETE request.
     */
    public function delete(
        string $uri,
        array $data = []
    ): Response {
        return $this->client()->delete(
            $uri,
            $data
        );
    }

    /**
     * Mendapatkan konfigurasi courier.
     */
    public function config(): array
    {
        return $this->config;
    }
}