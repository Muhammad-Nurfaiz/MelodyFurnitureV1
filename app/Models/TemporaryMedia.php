<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TemporaryMedia extends Model
{
    use HasUuids;
    protected $table = 'temporary_media';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'disk',
        'path',
        'filename',
        'mime_type',
        'extension',
        'size',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function isExpired(): bool
    {
        return now()->greaterThan($this->expires_at);
    }
}
