<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedSmallInteger('revisions_used')->default(0)->after('status');
            $table->text('delivery_message')->nullable()->after('revisions_used');
            $table->string('delivery_url')->nullable()->after('delivery_message');
            $table->timestamp('started_at')->nullable()->after('delivery_url');
            $table->timestamp('delivered_at')->nullable()->after('started_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'revisions_used',
                'delivery_message',
                'delivery_url',
                'started_at',
                'delivered_at',
            ]);
        });
    }
};
