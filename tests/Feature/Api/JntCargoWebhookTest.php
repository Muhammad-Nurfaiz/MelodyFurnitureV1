<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use App\Events\OrderStatusChanged;
use App\Models\Shipment;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use App\Services\Shipping\Webhooks\JntCargoWebhookService;
use App\Models\JntCargoWebhookEvent;
use App\Models\Customer;
use Illuminate\Support\Str;
use Tests\Support\CreatesJntCargoWebhookTestDatabase;

class JntCargoWebhookTest extends TestCase
{
    use CreatesJntCargoWebhookTestDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createJntCargoWebhookTestDatabase();
        $this->createJntCargoWebhookShipmentFixture();
    }

    public function test_jnt_cargo_webhook_endpoint_is_registered(): void
    {
        $response = $this->postJson('/api/webhooks/jnt-cargo/status', [
            'code' => '1',
            'msg' => 'success',
            'data' => [],
        ]);

        $this->assertNotEquals(404, $response->status());
    }

    public function test_jnt_cargo_webhook_processes_picked_up_status(): void
    {
        Event::fake([
            OrderStatusChanged::class,
        ]);

        $shipment = Shipment::where(
            'tracking_number',
            '200004729041'
        )->firstOrFail();

        $response = $this->postJson(
            '/api/webhooks/jnt-cargo/status',
            [
                'code' => '1',
                'msg' => 'success',
                'data' => [
                    [
                        'billCode' => '200004729041',
                        'details' => [
                            [
                                'scanTime' => '2026-09-15 10:00:00',
                                'desc' => 'Paket telah diambil oleh kurir J&T Cargo.',
                                'scanCode' => 1,
                                'scanType' => 'pengambilan paket',
                                'scanNetworkName' => 'TEST001',
                                'scanNetworkId' => '999',
                            ],
                        ],
                    ],
                ],
            ]
        );

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
        ]);

        $shipment->refresh();

        $this->assertEquals(
            'picked_up',
            $shipment->status
        );

        $this->assertNotNull(
            $shipment->last_tracking_sync_at
        );

        $shipment->load('order');

        $this->assertEquals(
            'picked_up',
            $shipment->order->status
        );

        $this->assertNotNull(
            $shipment->order->picked_up_at
        );

        Event::assertDispatched(
            OrderStatusChanged::class
        );
    }

    public function test_jnt_cargo_webhook_processes_in_transit_status(): void
    {
        Event::fake([
            OrderStatusChanged::class,
        ]);

        $shipment = Shipment::where(
            'tracking_number',
            '200004729041'
        )->firstOrFail();

        // Pastikan kondisi awal sesuai alur:
        // Shipment: picked_up
        // Order: picked_up
        $shipment->update([
            'status' => 'picked_up',
        ]);

        $shipment->order->update([
            'status' => 'picked_up',
        ]);

        $response = $this->postJson(
            '/api/webhooks/jnt-cargo/status',
            [
                'code' => '1',
                'msg' => 'success',
                'data' => [
                    [
                        'billCode' => '200004729041',
                        'details' => [
                            [
                                'scanTime' => '2026-09-15 11:00:00',
                                'desc' => 'Paket sedang dalam perjalanan.',
                                'scanCode' => 3,
                                'scanType' => 'outgoing scan',
                                'scanNetworkName' => 'TEST002',
                                'scanNetworkId' => '998',
                            ],
                        ],
                    ],
                ],
            ]
        );

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
        ]);

        $shipment->refresh();
        $shipment->load('order');

        $this->assertEquals(
            'in_transit',
            $shipment->status
        );

        $this->assertEquals(
            'shipped',
            $shipment->order->status
        );

        $this->assertNotNull(
            $shipment->last_tracking_sync_at
        );

        Event::assertDispatched(
            OrderStatusChanged::class
        );
    }

    public function test_jnt_cargo_webhook_processes_delivered_status(): void
    {
        Event::fake([
            OrderStatusChanged::class,
        ]);

        $shipment = Shipment::where(
            'tracking_number',
            '200004729041'
        )->firstOrFail();

        // Kondisi sebelum paket diterima:
        $shipment->update([
            'status' => 'in_transit',
        ]);

        $shipment->order->update([
            'status' => 'shipped',
        ]);

        $response = $this->postJson(
            '/api/webhooks/jnt-cargo/status',
            [
                'code' => '1',
                'msg' => 'success',
                'data' => [
                    [
                        'billCode' => '200004729041',
                        'details' => [
                            [
                                'scanTime' => '2026-09-15 15:00:00',
                                'desc' => 'Paket telah diterima oleh penerima.',
                                'scanCode' => 10,
                                'scanType' => 'delivered',
                                'scanNetworkName' => 'TEST003',
                                'scanNetworkId' => '997',
                            ],
                        ],
                    ],
                ],
            ]
        );

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
        ]);

        $shipment->refresh();
        $shipment->load('order');

        $this->assertEquals(
            'delivered',
            $shipment->status
        );

        $this->assertEquals(
            'completed',
            $shipment->order->status
        );

        $this->assertNotNull(
            $shipment->delivered_at
        );

        $this->assertNotNull(
            $shipment->order->completed_at
        );

        $this->assertNotNull(
            $shipment->last_tracking_sync_at
        );

        Event::assertDispatched(
            OrderStatusChanged::class
        );
    }

    public function test_jnt_cargo_webhook_ignores_problem_scan_status(): void
    {
        Event::fake([
            OrderStatusChanged::class,
        ]);

        $shipment = Shipment::where(
            'tracking_number',
            '200004729041'
        )->firstOrFail();

        $shipment->update([
            'status' => 'picked_up',
        ]);

        $shipment->order->update([
            'status' => 'picked_up',
        ]);

        $response = $this->postJson(
            '/api/webhooks/jnt-cargo/status',
            [
                'code' => '1',
                'msg' => 'success',
                'data' => [
                    [
                        'billCode' => '200004729041',
                        'details' => [
                            [
                                'scanTime' => '2026-09-15 12:00:00',
                                'desc' => 'Terjadi masalah pada pengiriman.',
                                'scanCode' => 11,
                                'scanType' => 'problem scan',
                                'scanNetworkName' => 'TEST-PROBLEM',
                                'scanNetworkId' => '996',
                            ],
                        ],
                    ],
                ],
            ]
        );

        $response->assertStatus(200);

        $shipment->refresh();
        $shipment->load('order');

        $this->assertEquals(
            'picked_up',
            $shipment->status
        );

        $this->assertEquals(
            'picked_up',
            $shipment->order->status
        );
    }

    public function test_jnt_cargo_webhook_ignores_return_scan_status(): void
    {
        Event::fake([
            OrderStatusChanged::class,
        ]);

        $shipment = Shipment::where(
            'tracking_number',
            '200004729041'
        )->firstOrFail();

        $shipment->update([
            'status' => 'in_transit',
        ]);

        $shipment->order->update([
            'status' => 'shipped',
        ]);

        $response = $this->postJson(
            '/api/webhooks/jnt-cargo/status',
            [
                'code' => '1',
                'msg' => 'success',
                'data' => [
                    [
                        'billCode' => '200004729041',
                        'details' => [
                            [
                                'scanTime' => '2026-09-15 13:00:00',
                                'desc' => 'Paket dikembalikan.',
                                'scanCode' => 12,
                                'scanType' => 'return scan',
                                'scanNetworkName' => 'TEST-RETURN',
                                'scanNetworkId' => '995',
                            ],
                        ],
                    ],
                ],
            ]
        );

        $response->assertStatus(200);

        $shipment->refresh();
        $shipment->load('order');

        $this->assertEquals(
            'in_transit',
            $shipment->status
        );

        $this->assertEquals(
            'shipped',
            $shipment->order->status
        );
    }

    public function test_jnt_cargo_webhook_ignores_pickup_failed_status(): void
    {
        Event::fake([
            OrderStatusChanged::class,
        ]);

        $shipment = Shipment::where(
            'tracking_number',
            '200004729041'
        )->firstOrFail();

        $shipment->update([
            'status' => 'waiting_pickup',
        ]);

        $shipment->order->update([
            'status' => 'processing',
        ]);

        $response = $this->postJson(
            '/api/webhooks/jnt-cargo/status',
            [
                'code' => '1',
                'msg' => 'success',
                'data' => [
                    [
                        'billCode' => '200004729041',
                        'details' => [
                            [
                                'scanTime' => '2026-09-15 14:00:00',
                                'desc' => 'Pengambilan paket gagal.',
                                'scanCode' => 13,
                                'scanType' => 'pickup failed',
                                'scanNetworkName' => 'TEST-FAILED',
                                'scanNetworkId' => '994',
                            ],
                        ],
                    ],
                ],
            ]
        );

        $response->assertStatus(200);

        $shipment->refresh();
        $shipment->load('order');

        $this->assertEquals(
            'waiting_pickup',
            $shipment->status
        );

        $this->assertEquals(
            'processing',
            $shipment->order->status
        );
    }

    public function test_jnt_cargo_webhook_does_not_regress_delivered_status(): void
    {
        Event::fake([
            OrderStatusChanged::class,
        ]);

        $shipment = Shipment::where(
            'tracking_number',
            '200004729041'
        )->firstOrFail();

        $shipment->update([
            'status' => 'delivered',
        ]);

        $shipment->order->update([
            'status' => 'completed',
        ]);

        $response = $this->postJson(
            '/api/webhooks/jnt-cargo/status',
            [
                'code' => '1',
                'msg' => 'success',
                'data' => [
                    [
                        'billCode' => '200004729041',
                        'details' => [
                            [
                                'scanTime' => '2026-09-15 14:00:00',
                                'desc' => 'Paket sedang dalam perjalanan.',
                                'scanCode' => 3,
                                'scanType' => 'outgoing scan',
                                'scanNetworkName' => 'TEST-REGRESSION',
                                'scanNetworkId' => '993',
                            ],
                        ],
                    ],
                ],
            ]
        );

        $response->assertStatus(200);

        $shipment->refresh();
        $shipment->load('order');

        $this->assertEquals(
            'delivered',
            $shipment->status
        );

        $this->assertEquals(
            'completed',
            $shipment->order->status
        );
    }

    public function test_jnt_cargo_webhook_can_process_duplicate_picked_up_webhook_safely(): void
    {
        Event::fake([
            OrderStatusChanged::class,
        ]);

        $shipment = Shipment::where(
            'tracking_number',
            '200004729041'
        )->firstOrFail();

        $shipment->update([
            'status' => 'waiting_pickup',
        ]);

        $shipment->order->update([
            'status' => 'processing',
        ]);

        $payload = [
            'code' => '1',
            'msg' => 'success',
            'data' => [
                [
                    'billCode' => '200004729041',
                    'details' => [
                        [
                            'scanTime' => '2026-09-15 10:00:00',
                            'desc' => 'Paket telah diambil oleh kurir J&T Cargo.',
                            'scanCode' => 1,
                            'scanType' => 'pickup',
                            'scanNetworkName' => 'TEST-DUPLICATE',
                            'scanNetworkId' => '992',
                        ],
                    ],
                ],
            ],
        ];

        $firstResponse = $this->postJson(
            '/api/webhooks/jnt-cargo/status',
            $payload
        );

        $firstResponse->assertStatus(200);

        $shipment->refresh();

        $this->assertEquals(
            'picked_up',
            $shipment->status
        );

        $secondResponse = $this->postJson(
            '/api/webhooks/jnt-cargo/status',
            $payload
        );

        $secondResponse->assertStatus(200);

        $shipment->refresh();
        $shipment->load('order');

        $this->assertEquals(
            'picked_up',
            $shipment->status
        );

        $this->assertEquals(
            'picked_up',
            $shipment->order->status
        );

        $this->assertNotNull(
            $shipment->last_tracking_sync_at
        );
    }

    public function test_jnt_cargo_webhook_ignores_unknown_shipment(): void
    {
        Event::fake([
            OrderStatusChanged::class,
        ]);

        $response = $this->postJson(
            '/api/webhooks/jnt-cargo/status',
            [
                'code' => '1',
                'msg' => 'success',
                'data' => [
                    [
                        'billCode' => '999999999999',
                        'details' => [
                            [
                                'scanTime' => '2026-09-15 10:00:00',
                                'desc' => 'Paket telah diambil oleh kurir J&T Cargo.',
                                'scanCode' => 1,
                                'scanType' => 'pickup',
                                'scanNetworkName' => 'TEST-UNKNOWN',
                                'scanNetworkId' => '991',
                            ],
                        ],
                    ],
                ],
            ]
        );

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
        ]);
    }

    public function test_jnt_cargo_webhook_ignores_empty_details(): void
    {
        Event::fake([
            OrderStatusChanged::class,
        ]);

        $shipment = Shipment::where(
            'tracking_number',
            '200004729041'
        )->firstOrFail();

        $shipment->update([
            'status' => 'waiting_pickup',
        ]);

        $shipment->order->update([
            'status' => 'processing',
        ]);

        $response = $this->postJson(
            '/api/webhooks/jnt-cargo/status',
            [
                'code' => '1',
                'msg' => 'success',
                'data' => [
                    [
                        'billCode' => '200004729041',
                        'details' => [],
                    ],
                ],
            ]
        );

        $response->assertStatus(200);

        $shipment->refresh();
        $shipment->load('order');

        $this->assertEquals(
            'waiting_pickup',
            $shipment->status
        );

        $this->assertEquals(
            'processing',
            $shipment->order->status
        );
    }

    public function test_jnt_cargo_webhook_accepts_single_data_object(): void
    {
        Event::fake([
            OrderStatusChanged::class,
        ]);

        $shipment = Shipment::where(
            'tracking_number',
            '200004729041'
        )->firstOrFail();

        $shipment->update([
            'status' => 'waiting_pickup',
        ]);

        $shipment->order->update([
            'status' => 'processing',
        ]);

        $response = $this->postJson(
            '/api/webhooks/jnt-cargo/status',
            [
                'code' => '1',
                'msg' => 'success',
                'data' => [
                    'billCode' => '200004729041',
                    'details' => [
                        [
                            'scanTime' => '2026-09-15 10:00:00',
                            'desc' => 'Paket telah diambil oleh kurir J&T Cargo.',
                            'scanCode' => 1,
                            'scanType' => 'pickup',
                        ],
                    ],
                ],
            ]
        );

        $response->assertStatus(200);

        $shipment->refresh();
        $shipment->load('order');

        $this->assertEquals(
            'picked_up',
            $shipment->status
        );

        $this->assertEquals(
            'picked_up',
            $shipment->order->status
        );
    }

    public function test_jnt_cargo_webhook_processes_real_jnt_biz_content_format(): void
    {
        Event::fake([
            OrderStatusChanged::class,
        ]);

        $shipment = Shipment::where(
            'tracking_number',
            '200004729041'
        )->firstOrFail();

        $shipment->update([
            'status' => 'waiting_pickup',
        ]);

        $shipment->order->update([
            'status' => 'processing',
        ]);

        /*
        * Format ini meniru request aktual yang dikirim
        * J&T Cargo Sandbox berdasarkan hasil ngrok Inspector:
        *
        * Content-Type:
        * application/x-www-form-urlencoded
        *
        * Form parameter:
        * bizContent = JSON string
        */
        $bizContent = json_encode([
            'billCode' => '200004729041',
            'details' => [
                [
                    'scanTime' => '2026-09-15 10:00:00',
                    'desc' => 'Paket telah diambil oleh kurir J&T Cargo.',
                    'scanCode' => 1,
                    'scanType' => 'pickup',
                    'scanNetworkName' => 'TEST-JNT',
                    'scanNetworkId' => '999',
                ],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $response = $this->post(
            '/api/webhooks/jnt-cargo/status',
            [
                'bizContent' => $bizContent,
            ],
            [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ]
        );

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
            'data' => [
                'processed_count' => 1,
                'ignored_count' => 0,
            ],
        ]);

        $shipment->refresh();
        $shipment->load('order');

        $this->assertEquals(
            'picked_up',
            $shipment->status
        );

        $this->assertEquals(
            'picked_up',
            $shipment->order->status
        );

        $this->assertNotNull(
            $shipment->last_tracking_sync_at
        );

        $metadata = $shipment->metadata ?? [];

        $this->assertEquals(
            'jnt_cargo_webhook',
            $metadata['tracking']['source'] ?? null
        );

        $this->assertEquals(
            '200004729041',
            $metadata['tracking']['billCode'] ?? null
        );

        $this->assertEquals(
            '1',
            (string) ($metadata['tracking']['latest_detail']['scanCode'] ?? '')
        );

        Event::assertDispatched(
            OrderStatusChanged::class
        );
    }

    public function test_jnt_cargo_webhook_accepts_lowercase_scancode(): void
    {
        Event::fake([
            OrderStatusChanged::class,
        ]);

        $shipment = Shipment::where(
            'tracking_number',
            '200004729041'
        )->firstOrFail();

        $shipment->update([
            'status' => 'waiting_pickup',
        ]);

        $shipment->order->update([
            'status' => 'processing',
        ]);

        $response = $this->postJson(
            '/api/webhooks/jnt-cargo/status',
            [
                'code' => '1',
                'msg' => 'success',
                'data' => [
                    [
                        'billCode' => '200004729041',
                        'details' => [
                            [
                                'scanTime' => '2026-09-15 10:00:00',
                                'desc' => 'Paket telah diambil oleh kurir J&T Cargo.',
                                'scancode' => 1,
                                'scanType' => 'pickup',
                            ],
                        ],
                    ],
                ],
            ]
        );

        $response->assertStatus(200);

        $shipment->refresh();
        $shipment->load('order');

        $this->assertEquals(
            'picked_up',
            $shipment->status
        );

        $this->assertEquals(
            'picked_up',
            $shipment->order->status
        );
    }

    public function test_jnt_cargo_webhook_processes_multiple_shipments(): void
    {
        Event::fake([
            OrderStatusChanged::class,
        ]);

        $shipmentA = Shipment::where(
            'tracking_number',
            '200004729041'
        )->firstOrFail();

        $shipmentA->update([
            'status' => 'waiting_pickup',
        ]);

        $shipmentA->order->update([
            'status' => 'processing',
        ]);

        $orderB = $shipmentA->order->replicate();

        $orderB->order_number = 'JNT-WEBHOOK-MULTI-TEST';
        $orderB->midtrans_order_id = 'JNT-MULTI-' . (string) \Illuminate\Support\Str::uuid();
        $orderB->tracking_token = (string) \Illuminate\Support\Str::uuid();
        $orderB->tracking_number = '200004729042';
        $orderB->status = 'processing';
        $orderB->save();

        $shipmentB = $shipmentA->replicate();

        $shipmentB->id = (string) \Illuminate\Support\Str::uuid();
        $shipmentB->order_id = $orderB->id;
        $shipmentB->tracking_number = '200004729042';
        $shipmentB->status = 'waiting_pickup';

        $shipmentB->save();

        $response = $this->postJson(
            '/api/webhooks/jnt-cargo/status',
            [
                'code' => '1',
                'msg' => 'success',
                'data' => [
                    [
                        'billCode' => '200004729041',
                        'details' => [
                            [
                                'scanTime' => '2026-09-15 10:00:00',
                                'desc' => 'Paket telah diambil oleh kurir J&T Cargo.',
                                'scanCode' => 1,
                                'scanType' => 'pickup',
                            ],
                        ],
                    ],
                    [
                        'billCode' => '200004729042',
                        'details' => [
                            [
                                'scanTime' => '2026-09-15 10:01:00',
                                'desc' => 'Paket telah diambil oleh kurir J&T Cargo.',
                                'scanCode' => 1,
                                'scanType' => 'pickup',
                            ],
                        ],
                    ],
                ],
            ]
        );

        $response->assertStatus(200);

        $shipmentA->refresh();
        $shipmentA->load('order');

        $shipmentB->refresh();
        $shipmentB->load('order');

        $this->assertEquals(
            'picked_up',
            $shipmentA->status
        );

        $this->assertEquals(
            'picked_up',
            $shipmentA->order->status
        );

        $this->assertEquals(
            'picked_up',
            $shipmentB->status
        );

        $this->assertEquals(
            'picked_up',
            $shipmentB->order->status
        );
    }

    public function test_jnt_cargo_order_status_mapper_maps_deployed_salesperson_to_waiting_pickup(): void
    {
        $mapper = app(\App\Services\Shipping\Courier\JntCargoTrackingStatusMapper::class);

        $this->assertEquals(
            'waiting_pickup',
            $mapper->mapOrderStatus(
                'Telah menjadwalpan Sprinter untuk delivery'
            )
        );
    }

    public function test_jnt_cargo_order_status_mapper_maps_pickup_to_picked_up(): void
    {
        $mapper = app(\App\Services\Shipping\Courier\JntCargoTrackingStatusMapper::class);

        $this->assertEquals(
            'picked_up',
            $mapper->mapOrderStatus('sudah diambil')
        );
    }

    public function test_jnt_cargo_order_status_mapper_maps_collecting_to_picked_up(): void
    {
        $mapper = app(\App\Services\Shipping\Courier\JntCargoTrackingStatusMapper::class);

        $this->assertEquals(
            'picked_up',
            $mapper->mapOrderStatus('pickup and collecting')
        );
    }

    public function test_jnt_cargo_order_status_mapper_ignores_cancelled_for_now(): void
    {
        $mapper = app(\App\Services\Shipping\Courier\JntCargoTrackingStatusMapper::class);

        $this->assertNull(
            $mapper->mapOrderStatus('sudah dibatalkan')
        );
    }

    public function test_jnt_cargo_order_status_mapper_ignores_pickup_failed_for_now(): void
    {
        $mapper = app(\App\Services\Shipping\Courier\JntCargoTrackingStatusMapper::class);

        $this->assertNull(
            $mapper->mapOrderStatus('pickupFail')
        );
    }

    public function test_jnt_cargo_order_status_webhook_processes_deployed_salesperson(): void
    {
        Event::fake([
            OrderStatusChanged::class,
        ]);

        $shipment = Shipment::where(
            'tracking_number',
            '200004729041'
        )->firstOrFail();

        $shipment->update([
            'status' => 'waiting_pickup',
        ]);

        $shipment->order->update([
            'status' => 'processing',
        ]);

        $service = app(JntCargoWebhookService::class);

        $bizContent = json_encode([
            'txlogisticId' => $shipment->order->order_number,
            'billCode' => '200004729041',
            'jtOrderId' => '437807433025323047',
            'networkName' => 'TestWD1J',
            'pickStaffName' => 'Test Sprinter',
            'pickStaffPhone' => '081234567890',
            'scanType' => 'Telah menjadwalpan Sprinter untuk delivery',
            'time' => '2026-09-15 08:50:22',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $result = $service->handleOrderStatus([
            'bizContent' => $bizContent,
        ]);

        $this->assertTrue($result['processed']);

        $this->assertEquals(
            $shipment->id,
            $result['shipment_id']
        );

        $this->assertEquals(
            '200004729041',
            $result['tracking_number']
        );

        $this->assertEquals(
            'waiting_pickup',
            $result['mapped_status']
        );

        $shipment->refresh();
        $shipment->load('order');

        $this->assertEquals(
            'waiting_pickup',
            $shipment->status
        );

        $this->assertEquals(
            'processing',
            $shipment->order->status
        );

        $metadata = $shipment->metadata ?? [];

        $this->assertEquals(
            'jnt_cargo_order_status_webhook',
            $metadata['tracking']['source'] ?? null
        );

        $this->assertEquals(
            'Telah menjadwalpan Sprinter untuk delivery',
            $metadata['tracking']['scanType'] ?? null
        );

        $this->assertEquals(
            'waiting_pickup',
            $metadata['tracking']['mapped_status'] ?? null
        );
    }

    public function test_jnt_cargo_order_status_webhook_processes_picked_up(): void
    {
        Event::fake([
            OrderStatusChanged::class,
        ]);

        $shipment = Shipment::where(
            'tracking_number',
            '200004729041'
        )->firstOrFail();

        $shipment->update([
            'status' => 'waiting_pickup',
        ]);

        $shipment->order->update([
            'status' => 'processing',
        ]);

        $service = app(JntCargoWebhookService::class);

        $bizContent = json_encode([
            'txlogisticId' => $shipment->order->order_number,
            'billCode' => '200004729041',
            'customerCost' => '500',
            'insuranceCost' => '2000',
            'jtOrderId' => '437811608983437344',
            'scanType' => 'sudah diambil',
            'sumFreight' => '2500',
            'time' => '2026-09-15 08:50:14',
            'weight' => '125.0',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $result = $service->handleOrderStatus([
            'bizContent' => $bizContent,
        ]);

        $this->assertTrue($result['processed']);

        $this->assertEquals(
            'picked_up',
            $result['mapped_status']
        );

        $shipment->refresh();
        $shipment->load('order');

        $this->assertEquals(
            'picked_up',
            $shipment->status
        );

        $this->assertEquals(
            'picked_up',
            $shipment->order->status
        );

        $this->assertNotNull(
            $shipment->last_tracking_sync_at
        );

        $metadata = $shipment->metadata ?? [];

        $this->assertEquals(
            'jnt_cargo_order_status_webhook',
            $metadata['tracking']['source'] ?? null
        );

        $this->assertEquals(
            'sudah diambil',
            $metadata['tracking']['scanType'] ?? null
        );

        $this->assertEquals(
            'picked_up',
            $metadata['tracking']['mapped_status'] ?? null
        );

        $this->assertEquals(
            '125.0',
            (string) (
                $metadata['tracking']['weight'] ?? ''
            )
        );

        Event::assertDispatched(
            OrderStatusChanged::class
        );
    }

    public function test_jnt_cargo_order_status_webhook_falls_back_to_txlogistic_id(): void
    {
        Event::fake([
            OrderStatusChanged::class,
        ]);

        $shipment = Shipment::where(
            'tracking_number',
            '200004729041'
        )->firstOrFail();

        $shipment->update([
            'status' => 'waiting_pickup',
        ]);

        $shipment->order->update([
            'status' => 'processing',
        ]);

        $service = app(JntCargoWebhookService::class);

        $bizContent = json_encode([
            'txlogisticId' => $shipment->order->order_number,
            'jtOrderId' => '437811608983437344',
            'scanType' => 'sudah diambil',
            'time' => '2026-09-15 08:50:14',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $result = $service->handleOrderStatus([
            'bizContent' => $bizContent,
        ]);

        $this->assertTrue($result['processed']);

        $this->assertEquals(
            $shipment->id,
            $result['shipment_id']
        );

        $this->assertEquals(
            '200004729041',
            $result['tracking_number']
        );

        $this->assertEquals(
            'picked_up',
            $result['mapped_status']
        );

        $shipment->refresh();
        $shipment->load('order');

        $this->assertEquals(
            'picked_up',
            $shipment->status
        );

        $this->assertEquals(
            'picked_up',
            $shipment->order->status
        );
    }

    public function test_jnt_cargo_order_status_webhook_ignores_unknown_shipment(): void
    {
        $service = app(JntCargoWebhookService::class);

        $bizContent = json_encode([
            'txlogisticId' => 'JNT-UNKNOWN-ORDER',
            'billCode' => '999999999999',
            'jtOrderId' => '437811608983437344',
            'scanType' => 'sudah diambil',
            'time' => '2026-09-15 08:50:14',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $result = $service->handleOrderStatus([
            'bizContent' => $bizContent,
        ]);

        $this->assertFalse(
            $result['processed']
        );

        $this->assertEquals(
            '999999999999',
            $result['tracking_number']
        );

        $this->assertEquals(
            'JNT-UNKNOWN-ORDER',
            $result['order_number']
        );

        $this->assertEquals(
            'Shipment lokal tidak ditemukan.',
            $result['reason']
        );
    }

    public function test_jnt_cargo_order_status_webhook_rejects_invalid_biz_content(): void
    {
        $service = app(JntCargoWebhookService::class);

        $this->expectException(\RuntimeException::class);

        $this->expectExceptionMessage(
            'Payload Order Status Return J&T Cargo tidak valid.'
        );

        $service->handleOrderStatus([
            'bizContent' => '{invalid-json',
        ]);
    }

    public function test_jnt_cargo_order_status_webhook_ignores_cancelled_status_for_now(): void
    {
        Event::fake([
            OrderStatusChanged::class,
        ]);

        $shipment = Shipment::where(
            'tracking_number',
            '200004729041'
        )->firstOrFail();

        $shipment->update([
            'status' => 'waiting_pickup',
        ]);

        $shipment->order->update([
            'status' => 'processing',
        ]);

        $service = app(JntCargoWebhookService::class);

        $bizContent = json_encode([
            'txlogisticId' => $shipment->order->order_number,
            'billCode' => '200004729041',
            'jtOrderId' => '437811608983437344',
            'reason' => 'pelanggan membatalkan pesanan',
            'scanType' => 'sudah dibatalkan',
            'time' => '2026-09-15 09:08:21',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $result = $service->handleOrderStatus([
            'bizContent' => $bizContent,
        ]);

        $this->assertTrue(
            $result['processed']
        );

        $this->assertNull(
            $result['mapped_status']
        );

        $shipment->refresh();
        $shipment->load('order');

        $this->assertEquals(
            'waiting_pickup',
            $shipment->status
        );

        $this->assertEquals(
            'processing',
            $shipment->order->status
        );

        Event::assertNotDispatched(
            OrderStatusChanged::class
        );
    }

    public function test_jnt_cargo_order_status_webhook_endpoint_is_registered(): void
    {
        $response = $this->postJson(
            '/api/webhooks/jnt-cargo/order-status',
            [
                'bizContent' => json_encode([
                    'txlogisticId' => 'UNKNOWN-ORDER',
                    'billCode' => 'UNKNOWN-AWB',
                    'jtOrderId' => '437807433025323047',
                    'scanType' => 'sudah diambil',
                    'time' => '2026-01-01 10:00:00',
                ]),
            ]
        );

        $response->assertStatus(200);

        $response->assertJson([
            'code' => '1',
            'msg' => 'success',
            'data' => 'SUCCESS',
        ]);
    }

    public function test_jnt_cargo_order_status_webhook_accepts_form_urlencoded_biz_content(): void
    {
        $response = $this->post(
            '/api/webhooks/jnt-cargo/order-status',
            [
                'bizContent' => json_encode([
                    'txlogisticId' => 'UNKNOWN-ORDER-FORM',
                    'billCode' => 'UNKNOWN-AWB-FORM',
                    'jtOrderId' => '437807433025323047',
                    'scanType' => 'sudah diambil',
                    'time' => '2026-01-01 10:00:00',
                ]),
            ],
            [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ]
        );

        $response->assertStatus(200);

        $response->assertJson([
            'code' => '1',
            'msg' => 'success',
            'data' => 'SUCCESS',
        ]);
    }

    public function test_jnt_cargo_order_status_webhook_processes_form_urlencoded_picked_up_status(): void
    {
        Event::fake();

        $shipment = Shipment::where(
            'tracking_number',
            '200004729041'
        )->firstOrFail();

        $shipment->update([
            'status' => 'waiting_pickup',
        ]);

        $response = $this->post(
            '/api/webhooks/jnt-cargo/order-status',
            [
                'bizContent' => json_encode([
                    'txlogisticId' => $shipment->order->order_number,
                    'billCode' => $shipment->tracking_number,
                    'jtOrderId' => '437807433025323047',
                    'networkName' => 'TestWD1J',
                    'pickStaffName' => 'xiaoHu',
                    'pickStaffPhone' => '08123456789',
                    'scanType' => 'sudah diambil',
                    'time' => '2026-01-01 10:00:00',
                    'Weight' => '125.0',
                    'sumFreight' => '2500',
                    'customerCost' => '500',
                    'insuranceCost' => '2000',
                ]),
            ],
            [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ]
        );

        $response->assertStatus(200);

        $response->assertJson([
            'code' => '1',
            'msg' => 'success',
            'data' => 'SUCCESS',
        ]);

        $shipment->refresh();

        $this->assertSame('picked_up', $shipment->status);
    }

    public function test_jnt_cargo_order_status_webhook_returns_jnt_success_response(): void
    {
        $bizContent = json_encode([
            'customerCode' => 'J0086329296',
            'freight' => 7,
            'insuredFee' => '0',
            'orderSourceCode' => 'D474',
            'packageChargeWeight' => 1,
            'totalFreight' => 7,
            'waybillNo' => 'UT001359848713',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $response = $this->post(
            '/api/webhooks/jnt-cargo/order-status',
            [
                'bizContent' => $bizContent,
            ],
            [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ]
        );

        $response->assertStatus(200);

        $response->assertJson([
            'code' => '1',
            'msg' => 'success',
            'data' => 'SUCCESS',
        ]);
    }

    public function test_jnt_cargo_order_status_webhook_updates_shipment_and_order_status(): void
    {
        Event::fake();

        $shipment = Shipment::where(
            'tracking_number',
            '200004729041'
        )->firstOrFail();

        $shipment->update([
            'status' => 'waiting_pickup',
            'picked_up_at' => null,
            'delivered_at' => null,
            'last_tracking_sync_at' => null,
            'metadata' => [],
        ]);

        $shipment->order->update([
            'status' => 'processing',
            'picked_up_at' => null,
            'shipped_at' => null,
            'completed_at' => null,
        ]);

        $bizContent = json_encode([
            'txlogisticId' => $shipment->order->order_number,
            'billCode' => $shipment->tracking_number,
            'jtOrderId' => '437807433025323047',
            'scanType' => 'sudah diambil',
            'time' => '2026-09-14 08:50:14',
            'networkName' => 'TestWD1J',
            'pickStaffName' => 'Test Sprinter',
            'pickStaffPhone' => '081234567890',
            'Weight' => '125.0',
            'sumFreight' => '2500',
            'customerCost' => '500',
            'insuranceCost' => '2000',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $response = $this->post(
            '/api/webhooks/jnt-cargo/order-status',
            [
                'bizContent' => $bizContent,
            ],
            [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ]
        );

        $response->assertStatus(200);

        $response->assertJson([
            'code' => '1',
            'msg' => 'success',
            'data' => 'SUCCESS',
        ]);

        $shipment->refresh();
        $shipment->order->refresh();

        $this->assertSame('picked_up', $shipment->status);
        $this->assertNotNull($shipment->picked_up_at);

        $this->assertSame('picked_up', $shipment->order->status);
        $this->assertNotNull($shipment->order->picked_up_at);

        $this->assertNotNull($shipment->last_tracking_sync_at);

        $this->assertIsArray($shipment->metadata);
        $this->assertArrayHasKey('tracking', $shipment->metadata);

        Event::assertDispatched(OrderStatusChanged::class);
    }

    public function test_jnt_cargo_logistics_trackback_duplicate_webhook_is_not_processed_twice(): void
    {
        Event::fake();

        $shipment = Shipment::where(
            'tracking_number',
            '200004729041'
        )->firstOrFail();

        $shipment->update([
            'status' => 'waiting_pickup',
            'picked_up_at' => null,
            'delivered_at' => null,
            'last_tracking_sync_at' => null,
            'metadata' => [],
        ]);

        $shipment->order->update([
            'status' => 'processing',
            'picked_up_at' => null,
            'shipped_at' => null,
            'completed_at' => null,
        ]);

        $bizContent = json_encode([
            'billCode' => $shipment->tracking_number,
            'details' => [
                [
                    'scanCode' => '3',
                    'scanTime' => '2026-09-15 10:00:00',
                    'desc' => 'Test outgoing scan',
                ],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        /*
        * Callback pertama
        */
        $firstResponse = $this->post(
            '/api/webhooks/jnt-cargo/status',
            [
                'bizContent' => $bizContent,
            ],
            [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ]
        );

        $firstResponse->assertStatus(200);

        $firstResponse->assertJson([
            'success' => true,
            'data' => [
                'processed_count' => 1,
                'ignored_count' => 0,
            ],
        ]);

        /*
        * Callback kedua dengan payload IDENTIK
        */
        $secondResponse = $this->post(
            '/api/webhooks/jnt-cargo/status',
            [
                'bizContent' => $bizContent,
            ],
            [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ]
        );

        $secondResponse->assertStatus(200);

        $secondResponse->assertJson([
            'success' => true,
            'data' => [
                'processed_count' => 0,
                'ignored_count' => 1,
            ],
        ]);

        $secondResponse->assertJsonPath(
            'data.ignored.0.duplicate',
            true
        );

        $secondResponse->assertJsonPath(
            'data.ignored.0.reason',
            'Webhook event sudah pernah diterima.'
        );

        /*
        * Hanya boleh ada satu event webhook
        */
        $this->assertSame(
            1,
            JntCargoWebhookEvent::query()
                ->where('event_type', 'jnt_cargo_logistics_trackback')
                ->count()
        );

        $event = JntCargoWebhookEvent::query()
            ->where('event_type', 'jnt_cargo_logistics_trackback')
            ->first();

        $this->assertNotNull($event);
        $this->assertNotNull($event->processed_at);

        /*
        * Status bisnis hanya berubah satu kali
        */
        $shipment->refresh();
        $shipment->order->refresh();

        $this->assertSame(
            'in_transit',
            $shipment->status
        );

        $this->assertSame(
            'shipped',
            $shipment->order->status
        );

        /*
        * Event bisnis hanya sekali.
        *
        * Callback kedua tidak boleh menghasilkan
        * OrderStatusChanged baru.
        */
        $events = Event::dispatched(OrderStatusChanged::class);

        $this->assertCount(2, $events);

        $eventObjects = $events->map(
            fn (array $arguments) => $arguments[0]
        );

        $this->assertTrue(
            $eventObjects->contains(function ($event) {
                return $event->previousStatus === 'processing'
                    && $event->newStatus === 'picked_up';
            })
        );

        $this->assertTrue(
            $eventObjects->contains(function ($event) {
                return $event->previousStatus === 'picked_up'
                    && $event->newStatus === 'shipped';
            })
        );
    }

    public function test_jnt_cargo_order_status_duplicate_webhook_is_not_processed_twice(): void
    {
        Event::fake();

        $shipment = Shipment::where(
            'tracking_number',
            '200004729041'
        )->firstOrFail();

        $shipment->update([
            'status' => 'waiting_pickup',
            'picked_up_at' => null,
            'delivered_at' => null,
            'last_tracking_sync_at' => null,
            'metadata' => [],
        ]);

        $shipment->order->update([
            'status' => 'processing',
            'picked_up_at' => null,
            'shipped_at' => null,
            'completed_at' => null,
        ]);

        $bizContent = json_encode([
            'txlogisticId' => $shipment->order->order_number,
            'billCode' => $shipment->tracking_number,
            'jtOrderId' => '437807433025323047',
            'scanType' => 'sudah diambil',
            'time' => '2026-09-15 08:50:14',
            'networkName' => 'TestWD1J',
            'pickStaffName' => 'Test Sprinter',
            'pickStaffPhone' => '081234567890',
            'Weight' => '125.0',
            'sumFreight' => '2500',
            'customerCost' => '500',
            'insuranceCost' => '2000',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        /*
        |--------------------------------------------------------------------------
        | Callback pertama
        |--------------------------------------------------------------------------
        */

        $firstResponse = $this->post(
            '/api/webhooks/jnt-cargo/order-status',
            [
                'bizContent' => $bizContent,
            ],
            [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ]
        );

        if ($firstResponse->status() !== 200) {
            dump($firstResponse->status());
            dump($firstResponse->json());
            dump($firstResponse->getContent());
        }

        $firstResponse->assertStatus(200);

        $firstResponse->assertJson([
            'code' => '1',
            'msg' => 'success',
            'data' => 'SUCCESS',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Callback duplicate
        |--------------------------------------------------------------------------
        */

        $secondResponse = $this->post(
            '/api/webhooks/jnt-cargo/order-status',
            [
                'bizContent' => $bizContent,
            ],
            [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ]
        );

        $secondResponse->assertStatus(200);

        $secondResponse->assertJson([
            'code' => '1',
            'msg' => 'success',
            'data' => 'SUCCESS',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Pastikan duplicate terdeteksi oleh service
        |--------------------------------------------------------------------------
        */

        $secondResponseData = $secondResponse->json();

        $this->assertSame(
            '1',
            (string) $secondResponseData['code']
        );

        /*
        |--------------------------------------------------------------------------
        | Event idempotency
        |--------------------------------------------------------------------------
        */

        $this->assertSame(
            1,
            JntCargoWebhookEvent::query()
                ->where(
                    'event_type',
                    'jnt_cargo_order_status'
                )
                ->count()
        );

        $event = JntCargoWebhookEvent::query()
            ->where(
                'event_type',
                'jnt_cargo_order_status'
            )
            ->first();

        $this->assertNotNull($event);
        $this->assertNotNull($event->processed_at);

        /*
        |--------------------------------------------------------------------------
        | Status akhir
        |--------------------------------------------------------------------------
        */

        $shipment->refresh();
        $shipment->order->refresh();

        $this->assertSame(
            'picked_up',
            $shipment->status
        );

        $this->assertSame(
            'picked_up',
            $shipment->order->status
        );

        $this->assertNotNull(
            $shipment->picked_up_at
        );

        $this->assertNotNull(
            $shipment->order->picked_up_at
        );

        $this->assertNotNull(
            $shipment->last_tracking_sync_at
        );

        /*
        |--------------------------------------------------------------------------
        | Business event hanya sekali
        |--------------------------------------------------------------------------
        */

        $events = Event::dispatched(
            OrderStatusChanged::class
        );

        $this->assertCount(
            1,
            $events
        );

        $eventObjects = $events->map(
            fn (array $arguments) => $arguments[0]
        );

        $this->assertTrue(
            $eventObjects->contains(function ($event) {
                return $event->previousStatus === 'processing'
                    && $event->newStatus === 'picked_up';
            })
        );
    }

    private function createJntCargoWebhookShipmentFixture(
        string $trackingNumber = '200004729041',
        string $orderNumber = 'JNT-WEBHOOK-TEST'
    ): Shipment {
        $customer = Customer::query()->create([
            'id' => (string) Str::uuid(),
            'phone' => '081234567890',
            'email' => 'webhook-test@example.com',
            'name' => 'Webhook Test Customer',
        ]);

        $order = Order::query()->create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'order_number' => $orderNumber . '-' . Str::uuid(),
            'midtrans_order_id' => 'MID-WEBHOOK-' . Str::uuid(),
            'tracking_token' => (string) Str::uuid(),

            'total_product_price' => 100000,
            'voucher_discount_amount' => 0,
            'original_shipping_fee' => 0,
            'shipping_fee' => 0,
            'total_payment' => 100000,

            'shipping_address' => [
                'name' => 'Webhook Test Customer',
                'phone' => '081234567890',
                'address' => 'Alamat test webhook',
            ],

            'shipping_method' => 'delivery',
            'courier' => 'jnt_cargo',
            'tracking_number' => $trackingNumber,
            'total_weight' => 1,

            'status' => 'processing',
            'payment_status' => 'paid',

            'payment_expired_at' => now()->addDay(),
            'paid_at' => now(),
        ]);

        $shipment = new Shipment();

        $shipment->id = (string) Str::uuid();
        $shipment->order_id = $order->id;
        $shipment->courier = 'jnt_cargo';
        $shipment->service = 'FT';
        $shipment->booking_code = $order->order_number;
        $shipment->tracking_number = $trackingNumber;
        $shipment->status = 'waiting_pickup';
        $shipment->metadata = [];

        $shipment->save();

        return $shipment;
    }
}