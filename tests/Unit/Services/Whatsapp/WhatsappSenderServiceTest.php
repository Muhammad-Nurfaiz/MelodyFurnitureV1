<?php

namespace Tests\Unit\Services\Whatsapp;

use App\Services\Whatsapp\WhatsappSenderService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsappSenderServiceTest extends TestCase
{
    public function test_message_is_sent_to_waha(): void
    {
        Http::fake([
            '*' => Http::response([
                'id' => 'true_TEST_MESSAGE_ID',
            ], 201),
        ]);

        config()->set('services.waha.url', 'http://127.0.0.1:3000');
        config()->set('services.waha.api_key', 'test-api-key');
        config()->set('services.waha.session', 'default');

        $service = app(WhatsappSenderService::class);

        $service->send(
            phone: '081234567892',
            message: 'Test message Melody Furniture',
        );

        Http::assertSent(function ($request) {
            return $request->url() === 'http://127.0.0.1:3000/api/sendText'
                && $request->header('X-Api-Key')[0] === 'test-api-key'
                && $request['session'] === 'default'
                && $request['chatId'] === '6281234567892@c.us'
                && $request['text'] === 'Test message Melody Furniture';
        });
    }

    public function test_waha_error_is_thrown(): void
    {
        Http::fake([
            '*' => Http::response([
                'error' => 'WAHA error',
            ], 500),
        ]);

        config()->set('services.waha.url', 'http://127.0.0.1:3000');
        config()->set('services.waha.api_key', 'test-api-key');
        config()->set('services.waha.session', 'default');

        $service = app(WhatsappSenderService::class);

        $this->expectException(\Illuminate\Http\Client\RequestException::class);

        $service->send(
            phone: '081234567892',
            message: 'Test message',
        );
    }

    public function test_indonesian_phone_number_is_normalized(): void
    {
        Http::fake([
            '*' => Http::response([
                'id' => 'true_TEST_MESSAGE_ID',
            ], 201),
        ]);

        config()->set('services.waha.url', 'http://127.0.0.1:3000');
        config()->set('services.waha.api_key', 'test-api-key');
        config()->set('services.waha.session', 'default');

        $service = app(WhatsappSenderService::class);

        $service->send(
            phone: '81234567892',
            message: 'Test message',
        );

        Http::assertSent(function ($request) {
            return $request['chatId'] === '6281234567892@c.us';
        });
    }
}