<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->string('role', 16);
            $table->string('provider', 32);
            $table->string('model', 120);
            $table->text('objective');
            $table->json('constraints')->nullable();
            $table->unsignedSmallInteger('max_turns')->default(5);
            $table->unsignedBigInteger('spending_limit_minor')->default(0);
            $table->string('billing_method', 32)->default('prepaid_credits');
            $table->string('status', 16)->default('draft');
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();

            $table->unique(['transaction_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_configurations');
    }
};
