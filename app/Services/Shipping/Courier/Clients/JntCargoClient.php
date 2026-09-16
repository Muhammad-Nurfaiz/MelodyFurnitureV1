<?php

namespace App\Services\Shipping\Courier\Clients;

use App\Models\Order;
use App\Services\Shipping\Courier\CourierShipmentResult;
use App\Services\Shipping\Courier\Payloads\JntCargoOrderPayload;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class JntCargoClient
{
    protected array $config;

    public function __construct(
        protected JntCargoAuthenticator $authenticator,
        protected JntCargoOrderPayload $orderPayload
    ) {
        $this->config = config(
            'shipping.couriers.jnt_cargo',
            []
        );
    }

    protected function isSuccessfulResponse(Response $response): bool
    {
        if (! $response->successful()) {
            return false;
        }

        return (string) $response->json('code') === '1';
    }

    protected function responseMessage(Response $response): string
    {
        return (string) (
            $response->json('msg')
            ?? 'J&T Cargo tidak memberikan pesan response.'
        );
    }

    protected function responseData(Response $response): array
    {
        $data = $response->json('data');

        return is_array($data)
            ? $data
            : [];
    }

    protected function baseUrl(): string
    {
        $environment = $this->config['environment'] ?? 'sandbox';

        $baseUrls = $this->config['base_urls'] ?? [];

        $baseUrl = $baseUrls[$environment] ?? null;

        if (blank($baseUrl)) {
            throw new RuntimeException(
                "J&T Cargo Base URL untuk environment [{$environment}] belum dikonfigurasi."
            );
        }

        return rtrim($baseUrl, '/');
    }

    protected function client(): PendingRequest
    {
        return Http::timeout(
            (int) ($this->config['timeout'] ?? 30)
        )
            ->acceptJson()
            ->asForm()
            ->baseUrl($this->baseUrl());
    }

    protected function timestamp(): string
    {
        return (string) round(microtime(true) * 1000);
    }

    protected function headers(
        string $bizContent,
        string $timestamp
    ): array {
        return [
            'apiAccount' => $this->authenticator->apiAccount(),
            'digest' => $this->authenticator->generateHeaderDigest(
                $bizContent
            ),
            'timestamp' => $timestamp,
        ];
    }

    protected function request(
        string $uri,
        array $bizContent
    ): Response {
        $encodedBizContent = json_encode(
            $bizContent,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if ($encodedBizContent === false) {
            throw new RuntimeException(
                'Gagal melakukan JSON encoding bizContent J&T Cargo.'
            );
        }

        $timestamp = $this->timestamp();

        $headers = $this->headers(
            $encodedBizContent,
            $timestamp
        );

        return $this->client()
            ->withHeaders($headers)
            ->post($uri, [
                'bizContent' => $encodedBizContent,
            ]);
    }

    public function createOrder(
        array $businessData,
        ?string $uuid = null
    ): Response {
        $customerCode = $this->authenticator->customerCode();

        $businessData['customerCode'] = $customerCode;

        $businessData['digest'] = $this->authenticator->generateBodyDigest();

        $uuid = $uuid ?: (string) \Illuminate\Support\Str::uuid();

        $uri = '/order/addOrder';

        if (($this->config['environment'] ?? 'sandbox') === 'sandbox') {
            $uri .= '?uuid=' . $uuid;
        }

        return $this->request(
            $uri,
            $businessData
        );
    }

    /**
     * Query tracking information from J&T Cargo.
     *
     * @param string $trackingNumber
     */
    public function traceRaw(
        string $trackingNumber
    ): Response {
        $trackingNumber = trim($trackingNumber);

        if (blank($trackingNumber)) {
            throw new RuntimeException(
                'Tracking number J&T Cargo tidak boleh kosong.'
            );
        }

        $uuid = (string) \Illuminate\Support\Str::uuid();

        $uri = '/logistics/trace';

        if (
            ($this->config['environment'] ?? 'sandbox')
            === 'sandbox'
        ) {
            $uri .= '?uuid=' . $uuid;
        }

        return $this->request(
            $uri,
            [
                'billCodes' => $trackingNumber,
            ]
        );
    }

    public function createShipment(
        Order $order
    ): CourierShipmentResult {
        try {
            $payload = $this->orderPayload->build($order);

            $response = $this->createOrder($payload);

            if (! $this->isSuccessfulResponse($response)) {
                return CourierShipmentResult::failed(
                    $this->responseMessage($response)
                );
            }

            $data = $this->responseData($response);

            return CourierShipmentResult::success(
                bookingCode: $data['txlogisticId'] ?? null,
                trackingNumber: $data['billCode'] ?? null,
                labelUrl: null,
                status: 'waiting_pickup',
                metadata: $data,
                message: $this->responseMessage($response),
            );
        } catch (\Throwable $e) {
            return CourierShipmentResult::failed(
                $e->getMessage()
            );
        }
    }

    public function getAddress(): Response
    {
        $customerCode = $this->authenticator->customerCode();

        $businessData = [
            'customerCode' => $customerCode,
            'digest' => $this->authenticator->generateBodyDigest(),
        ];

        $uuid = (string) \Illuminate\Support\Str::uuid();

        $uri = '/order/getAddress';

        if (($this->config['environment'] ?? 'sandbox') === 'sandbox') {
            $uri .= '?uuid=' . $uuid;
        }

        return $this->request(
            $uri,
            $businessData
        );
    }

    public function updateShipment(
        Order $order
    ): CourierShipmentResult {
        return CourierShipmentResult::failed(
            'Integrasi update shipment J&T Cargo belum dikonfigurasi.'
        );
    }

    public function cancelShipment(
        Order $order
    ): bool {
        return false;
    }

    public function tracking(
        Order $order
    ): CourierShipmentResult {
        return CourierShipmentResult::failed(
            'Integrasi tracking J&T Cargo belum dikonfigurasi.'
        );
    }

    public function get(
        string $uri,
        array $query = []
    ): Response {
        return $this->client()->get(
            $uri,
            $query
        );
    }

    public function post(
        string $uri,
        array $data = []
    ): Response {
        return $this->client()->post(
            $uri,
            $data
        );
    }

    public function put(
        string $uri,
        array $data = []
    ): Response {
        return $this->client()->put(
            $uri,
            $data
        );
    }

    public function delete(
        string $uri,
        array $data = []
    ): Response {
        return $this->client()->delete(
            $uri,
            $data
        );
    }

    public function config(): array
    {
        return $this->config;
    }
}