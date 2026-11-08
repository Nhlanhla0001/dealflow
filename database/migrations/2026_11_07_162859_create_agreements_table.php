<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->unsignedBigInteger('price_minor');
            $table->char('currency', 3);
            $table->unsignedSmallInteger('delivery_days');
            $table->json('terms');
            $table->string('status')->default('awaiting_approvals');
            $table->string('hash', 64);
            $table->timestamps();

            $table->unique(['transaction_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agreements');
    }
};
