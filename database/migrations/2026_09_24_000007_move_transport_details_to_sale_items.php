<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->string('transport_name')->nullable()->after('status');
            $table->string('vehicle_number', 50)->nullable()->after('transport_name');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['transport_name', 'vehicle_number']);
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('transport_name')->nullable()->after('delivery_address');
            $table->string('vehicle_number', 50)->nullable()->after('transport_name');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['transport_name', 'vehicle_number']);
        });
    }
};