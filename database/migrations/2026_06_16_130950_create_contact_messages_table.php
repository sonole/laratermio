<?php

use App\Enums\ContactMessageStatus;
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
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('message');

            $table->enum('visitor_status', array_column(ContactMessageStatus::cases(), 'value'))
                ->default(ContactMessageStatus::Pending->value);
            $table->enum('admin_status', array_column(ContactMessageStatus::cases(), 'value'))
                ->default(ContactMessageStatus::Pending->value);

            $table->timestamps();

            $table->index(['email', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};
