<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use HasUuids, Notifiable;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'full_name',
        'email',
        'password',
        'phone_number'
    ];

    protected $hidden = [
        'password',
        'remember_token'
    ];

    public function chatHistories()
    {
        return $this->hasMany(ChatHistory::class);
    }

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
}