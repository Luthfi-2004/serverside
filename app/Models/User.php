<?php
// app/Models/User.php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'role',
    ];

    protected $hidden = ['password', 'remember_token'];

    public const ROLE_ADMIN = 'admin';
    public const ROLE_PEKERJA = 'pekerja';

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isPekerja(): bool
    {
        return $this->role === self::ROLE_PEKERJA;
    }
}
