<?php

use App\Enums\NavItemType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('nav_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('terminal_command_id')
                ->nullable()
                ->constrained('terminal_commands')
                ->nullOnDelete();
            $table->string('command_args')->nullable();
            $table->string('label')->nullable();
            $table->string('url')->nullable();
            $table->string('target')->default('_self');
            $table->enum('type', array_column(NavItemType::cases(), 'value'))
                ->default(NavItemType::Command->value);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nav_items');
    }
};
