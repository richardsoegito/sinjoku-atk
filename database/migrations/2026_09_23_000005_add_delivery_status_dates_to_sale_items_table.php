<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->string('status')->default('new')->after('discount_percent');
            $table->timestamp('loaded_at')->nullable()->after('status');
            $table->timestamp('shipped_at')->nullable()->after('loaded_at');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['status', 'loaded_at', 'shipped_at']);
        });
    }
};
