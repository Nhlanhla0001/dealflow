<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('negotiation_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negotiation_session_id')->constrained()->cascadeOnDelete();
            $table->string('agent', 10);
            $table->unsignedSmallInteger('round');
            $table->string('action', 20);
            $table->unsignedBigInteger('price_minor')->nullable();
            $table->char('currency', 3)->nullable();
            $table->unsignedSmallInteger('delivery_days')->nullable();
            $table->text('reason')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['negotiation_session_id', 'round']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('negotiation_messages');
    }
};
