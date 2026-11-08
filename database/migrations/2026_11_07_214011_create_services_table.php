<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->json('deliverables');
            $table->string('pricing_type')->default('fixed');
            $table->unsignedBigInteger('price_minor');
            $table->char('currency', 3)->default('USD');
            $table->text('includes')->nullable();
            $table->unsignedSmallInteger('delivery_days');
            $table->unsignedSmallInteger('revisions')->default(0);
            $table->text('exclusions')->nullable();
            $table->text('requirements')->nullable();
            $table->json('images')->nullable();
            $table->text('supporting_info')->nullable();
            $table->boolean('negotiation_enabled')->default(true);
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['seller_id', 'status']);
            $table->index(['category_id', 'status']);
            $table->index(['status', 'published_at']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreign('service_id')
                ->references('id')
                ->on('services')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
        });

        Schema::dropIfExists('services');
    }
};
