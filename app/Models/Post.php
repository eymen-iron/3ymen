<?php

declare(strict_types=1);

namespace App\Models;

use Eymen\Database\Model;

final class Post extends Model
{
    protected string $table = 'posts';

    protected array $fillable = [];

    protected array $casts = [];
}