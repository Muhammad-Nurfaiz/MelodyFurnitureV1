<?php

namespace Tests\Unit\Services\Whatsapp;

use App\Models\Order;
use App\Services\Whatsapp\WhatsappMessageTemplateService;
use Tests\TestCase;

class WhatsappMessageTemplateServiceTest extends TestCase
{
    protected WhatsappMessageTemplateService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new WhatsappMessageTemplateService();
    }

    protected function makeOrder(): Order
    {
        return new Order([
            'customer_name' => 'Test Customer',
            'order_number' => 'MF-TEST-0001',
            'tracking_number' => 'JNE123456789',
        ]);
    }

    public function test_paid_template_is_generated(): void
    {
        $message = $this->service->orderStatusChanged(
            $this->makeOrder(),
            'paid',
        );

        $this->assertNotNull($message);
        $this->assertStringContainsString(
            'MF-TEST-0001',
            $message
        );
        $this->assertStringContainsString(
            'berhasil kami terima',
            $message
        );
    }

    public function test_processing_template_is_generated(): void
    {
        $message = $this->service->orderStatusChanged(
            $this->makeOrder(),
            'processing',
        );

        $this->assertNotNull($message);
        $this->assertStringContainsString(
            'sedang kami proses',
            $message
        );
    }

    public function test_picked_up_template_is_generated(): void
    {
        $message = $this->service->orderStatusChanged(
            $this->makeOrder(),
            'picked_up',
        );

        $this->assertNotNull($message);
        $this->assertStringContainsString(
            'telah diambil',
            $message
        );
    }

    public function test_shipped_template_contains_tracking_number(): void
    {
        $message = $this->service->orderStatusChanged(
            $this->makeOrder(),
            'shipped',
        );

        $this->assertNotNull($message);
        $this->assertStringContainsString(
            'JNE123456789',
            $message
        );
    }

    public function test_completed_template_is_generated(): void
    {
        $message = $this->service->orderStatusChanged(
            $this->makeOrder(),
            'completed',
        );

        $this->assertNotNull($message);
        $this->assertStringContainsString(
            'telah selesai',
            $message
        );
    }

    public function test_cancelled_template_is_generated(): void
    {
        $message = $this->service->orderStatusChanged(
            $this->makeOrder(),
            'cancelled',
        );

        $this->assertNotNull($message);
        $this->assertStringContainsString(
            'telah dibatalkan',
            $message
        );
    }

    public function test_unknown_status_returns_null(): void
    {
        $message = $this->service->orderStatusChanged(
            $this->makeOrder(),
            'unknown_status',
        );

        $this->assertNull($message);
    }
}