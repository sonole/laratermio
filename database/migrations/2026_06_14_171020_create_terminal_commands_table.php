<?php

use App\Enums\InteractionType;
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
        Schema::create('terminal_commands', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('command_class');
            $table->string('display_label');
            $table->string('description')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->enum('interaction_type', array_column(InteractionType::cases(), 'value'))->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('terminal_commands');
    }
};
