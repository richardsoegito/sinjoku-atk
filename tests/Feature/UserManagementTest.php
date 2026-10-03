<?php

namespace Tests\Feature;

use App\Livewire\Settings\Users;
use App\Livewire\Settings\Users\Create as CreateUser;
use App\Livewire\Settings\Users\Edit as EditUser;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_with_role_management_permission_can_open_user_management(): void
    {
        $user = User::factory()->create();
        $user->syncRoles('super-admin');

        $this->actingAs($user)
            ->get(route('settings.users'))
            ->assertOk()
            ->assertSee('Pengguna')
            ->assertSee(route('settings.users.create'))
            ->assertSee(route('settings.users'));
    }

    public function test_users_without_role_management_permission_cannot_open_user_management(): void
    {
        $role = Role::create(['name' => 'user-manager-test', 'guard_name' => 'web']);
        $role->syncPermissions(['dashboard.view']);
        $user = User::factory()->create();
        $user->syncRoles($role);

        $this->actingAs($user)->get(route('settings.users'))->assertForbidden();
    }

    public function test_user_can_be_created_updated_and_deleted(): void
    {
        $admin = User::factory()->create();
        $admin->syncRoles('super-admin');
        $role = Role::create(['name' => 'staff-test', 'guard_name' => 'web']);

        Livewire::actingAs($admin)->test(CreateUser::class)
            ->set('name', 'Staff Baru')
            ->set('username', 'staff-baru')
            ->set('email', 'staff@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('roleId', (string) $role->id)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('settings.users'));

        $managedUser = User::query()->where('email', 'staff@example.com')->firstOrFail();
        $this->assertSame('Staff Baru', $managedUser->name);
        $this->assertNotNull($managedUser->email_verified_at);
        $this->assertTrue($managedUser->hasRole('staff-test'));

        Livewire::actingAs($admin)->test(EditUser::class, ['user' => $managedUser])
            ->set('name', 'Staff Diperbarui')
            ->set('username', 'staff-updated')
            ->set('email', 'staff.updated@example.com')
            ->set('roleId', (string) $role->id)
            ->call('update')
            ->assertHasNoErrors();

        $this->assertSame('Staff Diperbarui', $managedUser->fresh()->name);
        $this->assertSame('staff-updated', $managedUser->fresh()->username);

        Livewire::actingAs($admin)->test(Users::class)
            ->call('prepareDeleteUser', $managedUser->id)
            ->call('deleteUser')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('users', ['id' => $managedUser->id]);
    }

    public function test_customer_role_requires_and_saves_customer_address_without_permissions(): void
    {
        $admin = User::factory()->create();
        $admin->syncRoles('super-admin');
        $customerRole = Role::findByName('customer');

        Livewire::actingAs($admin)->test(CreateUser::class)
            ->set('name', 'Customer Baru')
            ->set('username', 'customer-baru')
            ->set('email', 'customer@example.com')
            ->set('roleId', (string) $customerRole->id)
            ->assertSee('Alamat customer')
            ->set('customerAddress', 'Jl. Merdeka No. 10, Bandung')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('save')
            ->assertHasNoErrors();

        $customer = User::query()->where('username', 'customer-baru')->firstOrFail();

        $this->assertTrue($customer->hasRole('customer'));
        $this->assertSame('Jl. Merdeka No. 10, Bandung', $customer->customer->alamat);
        $this->assertCount(0, $customerRole->fresh()->permissions);
    }

    public function test_last_super_admin_cannot_be_demoted_or_deleted(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->syncRoles('super-admin');
        $ownerRole = Role::findByName('owner');

        Livewire::actingAs($superAdmin)
            ->test(EditUser::class, ['user' => $superAdmin])
            ->set('roleId', (string) $ownerRole->id)
            ->call('update')
            ->assertHasNoErrors();

        Livewire::actingAs($superAdmin)
            ->test(Users::class)
            ->set('userToDeleteId', $superAdmin->id)
            ->call('deleteUser')
            ->assertHasNoErrors();

        $this->assertTrue($superAdmin->fresh()->hasRole('super-admin'));
        $this->assertDatabaseHas('users', ['id' => $superAdmin->id]);
    }
}
