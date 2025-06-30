<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str; // For UUID

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // Use UUIDs for the primary key
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'username',
        'password',
        'email',
        'first_name',
        'last_name',
        'role_name',
        'status',
    ];

    protected $hidden = [
        'password',
    ];

    // Auto-generate UUID on model creation
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->{$model->getKeyName()} = (string) Str::uuid();
        });
    }
}
