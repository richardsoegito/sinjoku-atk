<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->text('delivery_address')->nullable()->after('description');
            $table->string('transport_name')->nullable()->after('delivery_address');
            $table->string('vehicle_number', 50)->nullable()->after('transport_name');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['delivery_address', 'transport_name', 'vehicle_number']);
        });
    }
};
