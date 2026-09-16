<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JntCargoWebhookEvent extends Model
{
    protected $fillable = [
        'event_type',
        'event_key',
        'tracking_number',
        'order_number',
        'payload',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}