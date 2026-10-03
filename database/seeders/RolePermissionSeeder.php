<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $definitions = [
            'dashboard.view' => ['label' => 'Lihat dasbor', 'group' => 'Dasbor'],
            'sales.view' => ['label' => 'Lihat transaksi penjualan', 'group' => 'Penjualan'],
            'sales.view-all' => ['label' => 'Lihat semua transaksi penjualan', 'group' => 'Penjualan'],
            'sales.create' => ['label' => 'Buat transaksi penjualan', 'group' => 'Penjualan'],
            'sales.update' => ['label' => 'Ubah transaksi penjualan', 'group' => 'Penjualan'],
            'sales.delete' => ['label' => 'Hapus transaksi penjualan', 'group' => 'Penjualan'],
            'sales.manage-all' => ['label' => 'Kelola semua transaksi', 'group' => 'Penjualan'],
            'sales.item-status.update' => ['label' => 'Ubah status barang', 'group' => 'Penjualan'],
            'sales.delivery-proof.manage' => ['label' => 'Kelola bukti pengiriman', 'group' => 'Penjualan'],
            'sales.tracking-link.generate' => ['label' => 'Buat tautan pelacakan', 'group' => 'Penjualan'],
            'products.view' => ['label' => 'Lihat barang dan riwayat harga', 'group' => 'Barang'],
            'products.manage' => ['label' => 'Kelola barang dan harga', 'group' => 'Barang'],
            'roles.manage' => ['label' => 'Kelola peran pengguna', 'group' => 'Pengaturan'],
            'permissions.manage' => ['label' => 'Kelola izin akses', 'group' => 'Pengaturan'],
        ];

        $permissions = collect($definitions)->map(function (array $attributes, string $name): Permission {
            $permission = Permission::query()->firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                $attributes,
            );

            if ($permission->group !== $attributes['group'] || $permission->label !== $attributes['label']) {
                $permission->update($attributes);
            }

            return $permission;
        });

        $superAdmin = Role::findOrCreate('super-admin', 'web');
        $admin = Role::findOrCreate('admin', 'web');
        $owner = Role::findOrCreate('owner', 'web');
        Role::findOrCreate('customer', 'web');

        $superAdmin->syncPermissions($permissions);
        $admin->syncPermissions($permissions->reject(fn (Permission $permission): bool => in_array($permission->name, [
            'roles.manage',
            'permissions.manage',
            'sales.delivery-proof.manage',
        ], true)));
        $owner->syncPermissions($permissions->filter(fn (Permission $permission): bool => in_array($permission->name, [
            'dashboard.view',
            'sales.view',
            'sales.view-all',
            'sales.item-status.update',
            'sales.delivery-proof.manage',
            'sales.tracking-link.generate',
            'products.view',
        ], true)));

        $users = User::query()->with('roles')->orderBy('id')->get();
        $firstUser = $users->first();

        if ($firstUser && ! $users->contains(fn (User $user): bool => $user->hasRole('super-admin'))) {
            $firstUser->syncRoles([$superAdmin]);
        }

        foreach ($users as $user) {
            if (! $user->roles()->exists()) {
                $user->assignRole($owner);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
