<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'user_role'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function isManager(): bool
    {
        return $this->user_role === 'manager';
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}
