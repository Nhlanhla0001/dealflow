<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('service_id')->nullable()->after('seller_id');
            $table->string('title');
            $table->text('description');
            $table->char('currency', 3)->default('USD');
            $table->unsignedBigInteger('budget_minor');
            $table->unsignedBigInteger('seller_floor_minor')->nullable();
            $table->unsignedBigInteger('seller_offer_minor')->nullable();
            $table->unsignedSmallInteger('delivery_days');
            $table->unsignedSmallInteger('seller_delivery_days')->nullable();
            $table->string('negotiation_mode', 16)->default('manual')->after('delivery_days');
            $table->string('state')->default('draft');
            $table->timestamps();

            $table->index(['buyer_id', 'state']);
            $table->index(['seller_id', 'state']);
            $table->index('service_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
