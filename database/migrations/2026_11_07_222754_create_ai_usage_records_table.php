<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_usage_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_configuration_id')->constrained()->cascadeOnDelete();
            $table->foreignId('negotiation_session_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('agent_run_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('provider', 32);
            $table->string('model', 120);
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('completion_tokens')->default(0);
            $table->unsignedInteger('cost_minor')->default(0);
            $table->string('status', 16)->default('recorded');
            $table->timestamps();

            $table->index(['agent_configuration_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_records');
    }
};
