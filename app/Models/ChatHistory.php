<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ChatHistory extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'session_token',
        'admin_id',
        'message',
        'sender'
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}