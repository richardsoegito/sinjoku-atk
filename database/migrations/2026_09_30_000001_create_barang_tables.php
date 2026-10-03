<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barang', function (Blueprint $table) {
            $table->id();
            $table->string('kode_barang')->unique();
            $table->string('nama_barang');
            $table->decimal('harga_barang', 15, 2);
            $table->timestamps();
        });

        Schema::create('harga_barang', function (Blueprint $table) {
            $table->id();
            $table->string('kode_barang');
            $table->decimal('harga_barang', 15, 2);
            $table->timestamps();
            $table->foreign('kode_barang')
                ->references('kode_barang')
                ->on('barang')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        $viewPermission = Permission::query()->firstOrCreate(
            ['name' => 'products.view', 'guard_name' => 'web'],
            ['label' => 'Lihat barang dan riwayat harga', 'group' => 'Barang'],
        );
        $managePermission = Permission::query()->firstOrCreate(
            ['name' => 'products.manage', 'guard_name' => 'web'],
            ['label' => 'Kelola barang dan harga', 'group' => 'Barang'],
        );

        Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', ['super-admin', 'admin'])
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo([$viewPermission, $managePermission]));
        Role::query()
            ->where('name', 'owner')
            ->where('guard_name', 'web')
            ->first()
            ?->givePermissionTo($viewPermission);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('harga_barang');
        Schema::dropIfExists('barang');
    }
};
