<?php

namespace App\Livewire\Settings;

use App\Models\Permission;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Kelola Izin')]
class Permissions extends Component
{
    private const SYSTEM_PERMISSIONS = [
        'dashboard.view',
        'sales.view',
        'sales.create',
        'sales.update',
        'sales.delete',
        'sales.manage-all',
        'sales.item-status.update',
        'sales.delivery-proof.manage',
        'sales.tracking-link.generate',
        'products.view',
        'products.manage',
        'roles.manage',
        'permissions.manage',
    ];

    public int|string|null $editingPermissionId = null;

    public int|string|null $permissionToDeleteId = null;

    public string $name = '';

    public string $label = '';

    public string $group = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('permissions.manage'), 403);
    }

    public function createPermission(): void
    {
        $this->authorizeManagement();
        $this->resetValidation();
        $this->editingPermissionId = null;
        $this->name = '';
        $this->label = '';
        $this->group = '';
        Flux::modal('permission-editor')->show();
    }

    public function editPermission(int $permissionId): void
    {
        $this->authorizeManagement();
        $permission = Permission::query()->where('guard_name', 'web')->findOrFail($permissionId);

        $this->resetValidation();
        $this->editingPermissionId = $permission->id;
        $this->name = $permission->name;
        $this->label = $permission->label ?? $permission->name;
        $this->group = $permission->group;
        Flux::modal('permission-editor')->show();
    }

    public function savePermission(): void
    {
        $this->authorizeManagement();
        $permission = $this->editingPermissionId
            ? Permission::query()->where('guard_name', 'web')->findOrFail($this->editingPermissionId)
            : null;
        $isSystemPermission = $permission && in_array($permission->name, self::SYSTEM_PERMISSIONS, true);

        if ($isSystemPermission) {
            $this->name = $permission->name;
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:125', 'regex:/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/', Rule::unique('permissions', 'name')->where('guard_name', 'web')->ignore($permission?->id)],
            'label' => ['required', 'string', 'max:150'],
            'group' => ['required', 'string', 'max:100'],
        ]);

        $permission ??= new Permission(['guard_name' => 'web']);
        $permission->fill($validated);
        $permission->guard_name = 'web';
        $permission->save();

        Flux::modal('permission-editor')->close();
        Flux::toast(variant: 'success', text: __('Izin berhasil disimpan.'));
    }

    public function prepareDeletePermission(int $permissionId): void
    {
        $this->authorizeManagement();
        $this->permissionToDeleteId = $permissionId;
        Flux::modal('confirm-permission-deletion')->show();
    }

    public function deletePermission(): void
    {
        $this->authorizeManagement();
        $permission = Permission::query()->where('guard_name', 'web')->findOrFail($this->permissionToDeleteId);

        if (in_array($permission->name, self::SYSTEM_PERMISSIONS, true)) {
            Flux::toast(variant: 'danger', text: __('Izin bawaan sistem tidak dapat dihapus.'));

            return;
        }

        $permission->delete();
        $this->permissionToDeleteId = null;
        Flux::modal('confirm-permission-deletion')->close();
        Flux::toast(variant: 'success', text: __('Izin berhasil dihapus.'));
    }

    public function render(): View
    {
        return view('livewire.settings.permissions', [
            'permissionGroups' => Permission::query()
                ->where('guard_name', 'web')
                ->withCount('roles')
                ->orderBy('group')
                ->orderBy('label')
                ->get()
                ->groupBy('group'),
            'protectedPermissions' => self::SYSTEM_PERMISSIONS,
        ]);
    }

    private function authorizeManagement(): void
    {
        abort_unless(auth()->user()->can('permissions.manage'), 403);
    }
}
