<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agreement_id')->nullable()->constrained()->nullOnDelete();
            $table->string('paypal_order_id')->nullable()->unique();
            $table->string('paypal_approval_url')->nullable()->after('paypal_order_id');
            $table->string('status', 20)->default('pending');
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            $table->string('capture_id')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['transaction_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_orders');
    }
};
