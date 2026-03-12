<?php

declare(strict_types=1);

namespace App\Models;

use Eymen\Database\Model;

final class User extends Model
{
    protected string $table = 'users';

    protected array $fillable = ['name', 'email', 'password'];

    protected array $hidden = ['password'];

    protected array $casts = [
        'email_verified_at' => 'datetime',
    ];
}
