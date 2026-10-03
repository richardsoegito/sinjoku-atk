<?php

namespace App\Livewire\Settings;

use App\Models\Role;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

trait UserForm
{
    public string $name = '';

    public string $username = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $roleId = '';

    public string $customerAddress = '';

    protected function authorizeUserManagement(): void
    {
        abort_unless(auth()->user()->can('roles.manage'), 403);
    }

    /** @return array<string, array<int, ValidationRule|array<mixed>|string>> */
    protected function userValidationRules(?User $user = null): array
    {
        $customerRoleId = Role::query()->where('name', 'customer')->value('id');
        $isCustomer = $customerRoleId !== null && (string) $this->roleId === (string) $customerRoleId;

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'min:3', 'max:50', 'alpha_dash:ascii', Rule::unique('users', 'username')->ignore($user?->id)],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'roleId' => ['required', 'integer', Rule::exists('roles', 'id')->where('guard_name', 'web')],
            'customerAddress' => [$isCustomer ? 'required' : 'nullable', 'string', 'max:5000'],
        ];
    }

    protected function saveManagedUser(?User $user = null): bool
    {
        $this->authorizeUserManagement();
        $this->username = Str::lower(trim($this->username));
        $validated = $this->validate($this->userValidationRules($user));
        $roleName = (string) Role::query()->whereKey($validated['roleId'])->value('name');

        if ($user?->hasRole('super-admin')
            && $roleName !== 'super-admin'
            && Role::query()->where('name', 'super-admin')->firstOrFail()->users()->count() <= 1) {
            Flux::toast(variant: 'danger', text: __('Minimal satu pengguna harus tetap menjadi super-admin.'));

            return false;
        }

        DB::transaction(function () use ($user, $validated, $roleName): void {
            $emailChanged = ! $user || $user->email !== $validated['email'];
            $user ??= new User;
            $user->name = $validated['name'];
            $user->username = $validated['username'];
            $user->email = $validated['email'];
            if ($emailChanged) {
                $user->email_verified_at = Carbon::now();
            }
            if ($validated['password']) {
                $user->password = $validated['password'];
            }
            $user->save();
            $user->syncRoles([(int) $validated['roleId']]);

            if ($roleName === 'customer') {
                $user->customer()->updateOrCreate([], ['alamat' => $validated['customerAddress']]);
            } else {
                $user->customer()->delete();
            }
        });

        return true;
    }

    /** @return array{roles: \Illuminate\Database\Eloquent\Collection<int, Role>, selectedRoleName: string|null} */
    protected function userFormViewData(): array
    {
        $roles = Role::query()->where('guard_name', 'web')->orderBy('name')->get();
        $selectedRoleName = $roles->firstWhere('id', (int) $this->roleId)?->name;

        return [
            'roles' => $roles,
            'selectedRoleName' => $selectedRoleName,
        ];
    }
}
