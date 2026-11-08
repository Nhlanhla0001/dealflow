<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wizard_drafts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('wizard_type', 64);
            $table->string('context_type', 64)->nullable();
            $table->unsignedBigInteger('context_id')->nullable();
            $table->string('current_step', 64);
            $table->json('data')->nullable();
            $table->json('completed_steps')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->string('status', 32)->default('draft');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'wizard_type', 'status']);
            $table->index('expires_at');
            $table->index(['context_type', 'context_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wizard_drafts');
    }
};
