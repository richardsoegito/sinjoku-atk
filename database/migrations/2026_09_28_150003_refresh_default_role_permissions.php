<?php

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(RolePermissionSeeder::class)->run();
    }

    public function down(): void
    {
        // Keep role assignments and permissions intact during rollback.
    }
};