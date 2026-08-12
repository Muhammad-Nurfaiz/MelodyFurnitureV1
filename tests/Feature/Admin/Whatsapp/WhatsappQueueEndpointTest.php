<?php

namespace Tests\Feature\Admin\Whatsapp;

use App\Models\Admin;
use App\Models\WhatsappQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsappQueueEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_admin_can_get_whatsapp_queues(): void
    {
        $admin = Admin::create([
            'full_name' => 'Test Admin',
            'email' => 'admin@test.local',
            'password' => 'password',
            'phone_number' => '081234567890',
            'profile_photo' => null,
        ]);

        WhatsappQueue::create([
            'phone_target' => '081234567892',
            'message_text' => 'Test WhatsApp message',
            'status' => 'pending',
            'attempts' => 0,
            'sent_at' => null,
            'error_log' => null,
        ]);

        $response = $this
            ->actingAs($admin, 'web')
            ->getJson(
                route('admin.whatsapp.queues')
            );

        $response
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'items',
                    'pagination',
                    'stats',
                ],
            ]);

        $response->assertJsonPath(
            'success',
            true
        );

        $response->assertJsonPath(
            'data.stats.pending',
            1
        );

        $response->assertJsonPath(
            'data.stats.total',
            1
        );

        $response->assertJsonPath(
            'data.items.0.phone_target',
            '081234567892'
        );

        $response->assertJsonPath(
            'data.items.0.status',
            'pending'
        );

        $response->assertJsonPath(
            'data.items.0.attempts',
            0
        );
    }

    public function test_unauthenticated_user_cannot_get_whatsapp_queues(): void
    {
        $response = $this->getJson(
            route('admin.whatsapp.queues')
        );

        $response->assertUnauthorized();
    }
}