<?php

namespace Tests\Unit\Jobs;

use App\Jobs\SendWhatsappMessage;
use App\Models\WhatsappQueue;
use App\Services\Whatsapp\WhatsappSenderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SendWhatsappMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_whatsapp_queue_is_marked_as_success(): void
    {
        $queue = WhatsappQueue::create([
            'phone_target' => '081234567892',
            'message_text' => 'Test WhatsApp message',
            'status' => 'pending',
            'attempts' => 0,
            'sent_at' => null,
        ]);

        $senderService = Mockery::mock(WhatsappSenderService::class);

        $senderService
            ->shouldReceive('send')
            ->once()
            ->with(
                '081234567892',
                'Test WhatsApp message',
            )
            ->andReturnNull();

        $job = new SendWhatsappMessage(
            whatsappQueueId: $queue->id,
        );

        $job->handle($senderService);

        $queue->refresh();

        $this->assertSame(
            1,
            $queue->attempts
        );

        $this->assertDatabaseHas('whatsapp_queues', [
            'id' => $queue->id,
            'status' => 'success',
            'attempts' => 1,
            'error_log' => null,
        ]);

        $this->assertNotNull($queue->sent_at);
    }

    public function test_non_pending_queue_is_not_processed_again(): void
    {
        $queue = WhatsappQueue::create([
            'phone_target' => '081234567892',
            'message_text' => 'Already processed',
            'status' => 'success',
            'attempts' => 1,
            'sent_at' => now(),
        ]);

        $senderService = Mockery::mock(WhatsappSenderService::class);

        $senderService
            ->shouldNotReceive('send');

        $job = new SendWhatsappMessage(
            whatsappQueueId: $queue->id,
        );

        $job->handle($senderService);

        $this->assertDatabaseHas('whatsapp_queues', [
            'id' => $queue->id,
            'status' => 'success',
            'attempts' => 1,
        ]);
    }

    public function test_missing_queue_is_ignored(): void
    {
        $senderService = Mockery::mock(WhatsappSenderService::class);

        $senderService
            ->shouldNotReceive('send');

        $job = new SendWhatsappMessage(
            whatsappQueueId: '019fffffffffffffffffffffffffffffff',
        );

        $job->handle($senderService);

        $this->assertDatabaseCount('whatsapp_queues', 0);
    }

    public function test_failed_whatsapp_message_is_marked_as_failed(): void
    {
        $queue = WhatsappQueue::create([
            'phone_target' => '081234567892',
            'message_text' => 'Test failed message',
            'status' => 'pending',
            'attempts' => 0,
            'sent_at' => null,
        ]);

        $senderService = Mockery::mock(WhatsappSenderService::class);

        $senderService
            ->shouldReceive('send')
            ->once()
            ->with(
                '081234567892',
                'Test failed message',
            )
            ->andThrow(new \RuntimeException('WAHA error'));

        $job = new SendWhatsappMessage(
            whatsappQueueId: $queue->id,
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('WAHA error');

        try {
            $job->handle($senderService);
        } finally {
            $queue->refresh();

            $this->assertSame(
                'failed',
                $queue->status
            );

            $this->assertSame(
                1,
                $queue->attempts
            );

            $this->assertNull(
                $queue->sent_at
            );

            $this->assertSame(
                'WAHA error',
                $queue->error_log
            );
        }
    }

    public function test_existing_attempts_are_incremented_when_processing(): void
    {
        $queue = WhatsappQueue::create([
            'phone_target' => '081234567892',
            'message_text' => 'Retry WhatsApp message',
            'status' => 'pending',
            'attempts' => 2,
            'sent_at' => null,
        ]);

        $senderService = Mockery::mock(
            WhatsappSenderService::class
        );

        $senderService
            ->shouldReceive('send')
            ->once()
            ->with(
                '081234567892',
                'Retry WhatsApp message',
            )
            ->andReturnNull();

        $job = new SendWhatsappMessage(
            whatsappQueueId: $queue->id,
        );

        $job->handle($senderService);

        $queue->refresh();

        $this->assertSame(
            3,
            $queue->attempts
        );

        $this->assertNotNull(
            $queue->sent_at
        );

        $this->assertSame(
            'success',
            $queue->status
        );
    }
}