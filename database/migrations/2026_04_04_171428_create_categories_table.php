<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('icon')->nullable(); // emoji or icon name
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // Seed default categories
        DB::table('categories')->insert([
            ['name' => 'Food & Drink',    'icon' => '🍔', 'is_default' => true,  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Transport',       'icon' => '🚗', 'is_default' => true,  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Bills',           'icon' => '💡', 'is_default' => true,  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Entertainment',   'icon' => '🎮', 'is_default' => true,  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Health',          'icon' => '💊', 'is_default' => true,  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Shopping',        'icon' => '🛍️', 'is_default' => true,  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Education',       'icon' => '📚', 'is_default' => true,  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Other',           'icon' => '📦', 'is_default' => true,  'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
