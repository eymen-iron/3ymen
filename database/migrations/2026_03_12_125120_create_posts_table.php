<?php

declare(strict_types=1);

use Eymen\Database\Migration;
use Eymen\Database\Schema;
use Eymen\Database\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        $this->schema()->create('posts', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $this->schema()->dropIfExists('posts');
    }
};