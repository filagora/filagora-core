<?php

declare(strict_types=1);

use ForumKit\Core\Support\Tables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(Tables::name('system_settings'), function (Blueprint $table): void {
            $table->id();
            $table->string('key', 120)->unique();
            $table->string('label', 160)->nullable();
            $table->longText('value')->nullable();
            $table->string('value_type', 20)->default('string');
            $table->boolean('is_public')->default(false)->index();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(Tables::name('system_settings'));
    }
};
