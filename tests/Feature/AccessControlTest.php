<?php

namespace Tests\Feature;

use App\Actions\Fortify\CreateNewUser;
use App\Livewire\Sales\Edit;
use App\Livewire\Settings\Permissions;
use App\Livewire\Settings\Roles;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_roles_have_the_requested_sales_permissions(): void
    {
        $superAdmin = Role::findByName('super-admin');
        $admin = Role::findByName('admin');
        $owner = Role::findByName('owner');

        $this->assertTrue($superAdmin->hasPermissionTo('sales.delivery-proof.manage'));
        $this->assertTrue($admin->hasPermissionTo('sales.item-status.update'));
        $this->assertTrue($admin->hasPermissionTo('sales.tracking-link.generate'));
        $this->assertFalse($admin->hasPermissionTo('sales.delivery-proof.manage'));
        $this->assertTrue($owner->hasPermissionTo('sales.item-status.update'));
        $this->assertTrue($owner->hasPermissionTo('sales.view-all'));
        $this->assertTrue($owner->hasPermissionTo('sales.delivery-proof.manage'));
        $this->assertTrue($owner->hasPermissionTo('sales.tracking-link.generate'));
        $this->assertFalse($owner->hasPermissionTo('sales.update'));
    }

    public function test_first_registered_user_becomes_super_admin_and_later_users_become_owners(): void
    {
        $creator = app(CreateNewUser::class);
        $firstUser = $creator->create([
            'name' => 'Admin Pertama',
            'username' => 'admin-pertama',
            'email' => 'pertama@example.test',
            'timezone' => 'Asia/Jakarta',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);
        $secondUser = $creator->create([
            'name' => 'Pemilik Kedua',
            'username' => 'pemilik-kedua',
            'email' => 'kedua@example.test',
            'timezone' => 'Asia/Jakarta',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertTrue($firstUser->hasRole('super-admin'));
        $this->assertTrue($secondUser->hasRole('owner'));
        $this->assertCount(1, $firstUser->roles);
        $this->assertCount(1, $secondUser->roles);
    }

    public function test_a_user_cannot_be_assigned_more_than_one_role(): void
    {
        $user = User::factory()->create();
        $user->syncRoles(['admin']);
        $user->syncRoles(['owner']);

        $this->assertCount(1, $user->fresh()->roles);
        $this->assertTrue($user->fresh()->hasRole('owner'));

        $this->expectException(QueryException::class);

        $user->assignRole('admin');
    }

    public function test_super_admin_can_create_roles_with_permissions(): void
    {
        $user = User::factory()->create();
        $user->syncRoles('super-admin');
        $permission = Permission::findByName('sales.view');

        Livewire::actingAs($user)
            ->test(Roles::class)
            ->set('name', 'auditor')
            ->set('selectedPermissionIds', [(string) $permission->id])
            ->call('saveRole')
            ->assertHasNoErrors();

        $role = Role::findByName('auditor');
        $this->assertTrue($role->hasPermissionTo('sales.view'));
        $this->assertFalse($role->hasPermissionTo('sales.delete'));
    }

    public function test_super_admin_can_create_grouped_permissions(): void
    {
        $user = User::factory()->create();
        $user->syncRoles('super-admin');

        Livewire::actingAs($user)
            ->test(Permissions::class)
            ->set('name', 'sales.export')
            ->set('label', 'Ekspor transaksi')
            ->set('group', 'Penjualan')
            ->call('savePermission')
            ->assertHasNoErrors();

        $permission = Permission::findByName('sales.export');
        $this->assertSame('Ekspor transaksi', $permission->label);
        $this->assertSame('Penjualan', $permission->group);
    }

    public function test_super_admin_can_edit_and_delete_custom_roles(): void
    {
        $manager = User::factory()->create();
        $manager->syncRoles('super-admin');
        $role = Role::create(['name' => 'reviewer', 'guard_name' => 'web']);

        Livewire::actingAs($manager)
            ->test(Roles::class)
            ->set('editingRoleId', $role->id)
            ->set('name', 'auditor')
            ->set('selectedPermissionIds', [])
            ->call('saveRole')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'auditor']);

        Livewire::actingAs($manager)
            ->test(Roles::class)
            ->call('prepareDeleteRole', $role->id)
            ->call('deleteRole')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_super_admin_can_edit_and_delete_custom_permissions(): void
    {
        $manager = User::factory()->create();
        $manager->syncRoles('super-admin');
        $permission = Permission::create([
            'name' => 'sales.audit',
            'label' => 'Audit penjualan',
            'group' => 'Penjualan',
            'guard_name' => 'web',
        ]);

        Livewire::actingAs($manager)
            ->test(Permissions::class)
            ->set('editingPermissionId', $permission->id)
            ->set('name', 'sales.audit')
            ->set('label', 'Tinjau penjualan')
            ->set('group', 'Audit')
            ->call('savePermission')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('permissions', [
            'id' => $permission->id,
            'label' => 'Tinjau penjualan',
            'group' => 'Audit',
        ]);

        Livewire::actingAs($manager)
            ->test(Permissions::class)
            ->call('prepareDeletePermission', $permission->id)
            ->call('deletePermission')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('permissions', ['id' => $permission->id]);
    }

    public function test_assigning_a_role_replaces_the_users_previous_role(): void
    {
        $manager = User::factory()->create();
        $manager->syncRoles('super-admin');
        $user = User::factory()->create();
        $adminRole = Role::findByName('admin');

        Livewire::actingAs($manager)
            ->test(Roles::class)
            ->set("userRoleAssignments.{$user->id}", (string) $adminRole->id)
            ->call('saveUserRole', $user->id)
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertCount(1, $user->roles);
        $this->assertTrue($user->hasRole('admin'));
    }

    public function test_user_role_assignment_cannot_be_left_empty(): void
    {
        $manager = User::factory()->create();
        $manager->syncRoles('super-admin');
        $user = User::factory()->create();

        Livewire::actingAs($manager)
            ->test(Roles::class)
            ->set("userRoleAssignments.{$user->id}", '')
            ->call('saveUserRole', $user->id)
            ->assertHasErrors(['roleId']);
    }

    public function test_non_super_admin_cannot_open_access_management_pages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.roles'))
            ->assertForbidden();

        $this->get(route('settings.permissions'))->assertForbidden();
    }

    public function test_super_admin_can_open_role_and_permission_pages(): void
    {
        $user = User::factory()->create();
        $user->syncRoles('super-admin');

        $this->actingAs($user)
            ->get(route('settings.roles'))
            ->assertOk()
            ->assertSee('Kelola peran');

        $this->get(route('settings.permissions'))
            ->assertOk()
            ->assertSee('Kelola izin');
    }

    public function test_owner_can_update_only_item_status_on_owned_sales(): void
    {
        $owner = User::factory()->create();
        $sale = Sale::create([
            'user_id' => $owner->id,
            'store_name' => 'Toko Asli',
            'invoice_number' => 'INV-20260928-OWNER1',
            'sale_date' => now()->toDateString(),
            'status' => 'new',
        ]);
        $item = $sale->items()->create([
            'product_name' => 'Barang Asli',
            'quantity' => 2,
            'unit_price' => 10000,
            'status' => 'new',
        ]);

        $component = Livewire::actingAs($owner)
            ->test(Edit::class, ['sale' => $sale])
            ->set('storeName', 'Nama Diubah')
            ->set('items.0.product_name', 'Barang Diubah')
            ->set('items.0.unit_price', 1)
            ->set('items.0.status', 'loaded')
            ->call('updateItemStatuses')
            ->assertHasNoErrors();

        $sale->refresh();
        $item->refresh();
        $this->assertSame('Toko Asli', $sale->store_name);
        $this->assertSame('in_progress', $sale->status);
        $this->assertSame('Barang Asli', $item->product_name);
        $this->assertSame('10000.00', $item->unit_price);
        $this->assertSame('loaded', $item->status);
        $this->assertNotNull($item->loaded_at);
    }

    public function test_owner_can_see_and_update_status_for_sales_created_by_admin(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $admin->syncRoles('admin');
        $sale = Sale::create([
            'user_id' => $admin->id,
            'store_name' => 'Transaksi Admin',
            'invoice_number' => 'INV-20260928-OWNER2',
            'sale_date' => now()->toDateString(),
            'status' => 'new',
        ]);
        $item = $sale->items()->create([
            'product_name' => 'Barang Admin',
            'quantity' => 1,
            'unit_price' => 25000,
            'status' => 'new',
        ]);

        Livewire::actingAs($owner)
            ->test(\App\Livewire\Sales\Index::class)
            ->assertSee('Transaksi Admin')
            ->assertSee($sale->invoice_number);

        Livewire::actingAs($owner)
            ->test(\App\Livewire\Sales\Edit::class, ['sale' => $sale])
            ->set('items.0.status', 'loaded')
            ->call('updateItemStatuses')
            ->assertHasNoErrors();

        $this->assertSame('loaded', $item->refresh()->status);
        $this->assertSame('Transaksi Admin', $sale->refresh()->store_name);
        $this->assertFalse($owner->can('sales.update'));
        $this->assertFalse($owner->can('sales.delete'));
    }

    public function test_admin_can_manage_sales_but_cannot_manage_delivery_proofs(): void
    {
        $admin = User::factory()->create();
        $admin->syncRoles('admin');

        $this->assertTrue($admin->can('sales.manage-all'));
        $this->assertTrue($admin->can('sales.create'));
        $this->assertTrue($admin->can('sales.update'));
        $this->assertTrue($admin->can('sales.delete'));
        $this->assertTrue($admin->can('sales.tracking-link.generate'));
        $this->assertFalse($admin->can('sales.delivery-proof.manage'));
    }
}
