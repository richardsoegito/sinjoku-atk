<?php

namespace App\Livewire\Settings;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Kelola Peran')]
class Roles extends Component
{
    use WithPagination;

    private const SYSTEM_ROLES = ['super-admin', 'admin', 'owner'];

    public int|string|null $editingRoleId = null;

    public int|string|null $roleToDeleteId = null;

    public string $name = '';

    /** @var array<int, string> */
    public array $selectedPermissionIds = [];

    /** @var array<int, string> */
    public array $userRoleAssignments = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('roles.manage'), 403);
    }

    public function createRole(): void
    {
        $this->authorizeManagement();
        $this->resetValidation();
        $this->editingRoleId = null;
        $this->name = '';
        $this->selectedPermissionIds = [];
        Flux::modal('role-editor')->show();
    }

    public function editRole(int $roleId): void
    {
        $this->authorizeManagement();
        $role = Role::query()->where('guard_name', 'web')->with('permissions')->findOrFail($roleId);

        $this->resetValidation();
        $this->editingRoleId = $role->id;
        $this->name = $role->name;
        $this->selectedPermissionIds = $role->permissions->pluck('id')->map(fn ($id): string => (string) $id)->all();
        Flux::modal('role-editor')->show();
    }

    public function saveRole(): void
    {
        $this->authorizeManagement();
        $role = $this->editingRoleId
            ? Role::query()->where('guard_name', 'web')->findOrFail($this->editingRoleId)
            : null;
        $isSystemRole = $role && in_array($role->name, self::SYSTEM_ROLES, true);

        if ($isSystemRole) {
            $this->name = $role->name;
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->where('guard_name', 'web')->ignore($role?->id)],
            'selectedPermissionIds' => ['array'],
            'selectedPermissionIds.*' => ['integer', Rule::exists('permissions', 'id')->where('guard_name', 'web')],
        ]);

        DB::transaction(function () use ($role, $validated): void {
            $role ??= Role::create(['name' => $validated['name'], 'guard_name' => 'web']);
            if (! in_array($role->name, self::SYSTEM_ROLES, true)) {
                $role->name = $validated['name'];
                $role->save();
            }

            $permissions = Permission::query()
                ->where('guard_name', 'web')
                ->whereIn('id', $validated['selectedPermissionIds'] ?? [])
                ->get();
            $role->syncPermissions($role->name === 'super-admin' ? Permission::query()->where('guard_name', 'web')->get() : $permissions);
        });

        Flux::modal('role-editor')->close();
        Flux::toast(variant: 'success', text: __('Peran berhasil disimpan.'));
    }

    public function prepareDeleteRole(int $roleId): void
    {
        $this->authorizeManagement();
        $this->roleToDeleteId = $roleId;
        Flux::modal('confirm-role-deletion')->show();
    }

    public function deleteRole(): void
    {
        $this->authorizeManagement();
        $role = Role::query()->where('guard_name', 'web')->findOrFail($this->roleToDeleteId);

        if (in_array($role->name, self::SYSTEM_ROLES, true) || $role->users()->exists()) {
            Flux::toast(variant: 'danger', text: __('Peran bawaan atau peran yang masih digunakan tidak dapat dihapus.'));

            return;
        }

        $role->delete();
        $this->roleToDeleteId = null;
        Flux::modal('confirm-role-deletion')->close();
        Flux::toast(variant: 'success', text: __('Peran berhasil dihapus.'));
    }

    public function saveUserRole(int $userId): void
    {
        $this->authorizeManagement();
        $user = User::query()->findOrFail($userId);
        $roleId = $this->userRoleAssignments[$userId] ?? null;
        $validated = Validator::make(['roleId' => $roleId], [
            'roleId' => ['required', 'integer', Rule::exists('roles', 'id')->where('guard_name', 'web')],
        ])->validate();
        $currentRole = $user->roles->first();
        $role = $validated['roleId']
            ? Role::query()->whereKey((int) $validated['roleId'])->firstOrFail()
            : null;
        if ($currentRole?->name === 'super-admin' && $role?->name !== 'super-admin' && Role::findByName('super-admin')->users()->count() <= 1) {
            Flux::toast(variant: 'danger', text: __('Minimal satu pengguna harus tetap menjadi super-admin.'));

            return;
        }

        $user->syncRoles([$role]);
        Flux::toast(variant: 'success', text: __('Peran pengguna berhasil diperbarui.'));
    }

    public function render(): View
    {
        $roles = Role::query()
            ->where('guard_name', 'web')
            ->with(['permissions:id,name,label,group'])
            ->withCount('users')
            ->orderBy('name')
            ->get();
        $permissions = Permission::query()->where('guard_name', 'web')->orderBy('group')->orderBy('label')->get();
        $users = User::query()->with('roles')->orderBy('name')->paginate(10);

        foreach ($users as $user) {
            if (! array_key_exists($user->id, $this->userRoleAssignments)) {
                $currentRole = $user->roles->first();
                $this->userRoleAssignments[$user->id] = $currentRole ? (string) $currentRole->id : '';
            }
        }

        return view('livewire.settings.roles', [
            'roles' => $roles,
            'permissionGroups' => $permissions->groupBy('group'),
            'users' => $users,
        ]);
    }

    private function authorizeManagement(): void
    {
        abort_unless(auth()->user()->can('roles.manage'), 403);
    }
}
