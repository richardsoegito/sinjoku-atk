<?php

namespace App\Livewire\Settings;

use App\Models\User;
use App\Models\Role;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Pengguna')]
class Users extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public int|string|null $userToDeleteId = null;

    public string $userToDeleteName = '';

    public function mount(): void
    {
        $this->authorizeManagement();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function prepareDeleteUser(int $userId): void
    {
        $this->authorizeManagement();
        $user = User::query()->findOrFail($userId);
        $this->userToDeleteId = $user->id;
        $this->userToDeleteName = $user->name;
        Flux::modal('confirm-user-deletion')->show();
    }

    public function deleteUser(): void
    {
        $this->authorizeManagement();
        $user = User::query()->with('roles')->findOrFail($this->userToDeleteId);

        if ($user->is(auth()->user())) {
            Flux::toast(variant: 'danger', text: __('Akun yang sedang digunakan tidak dapat dihapus.'));

            return;
        }

        if ($user->roles->contains('name', 'super-admin') && Role::query()->where('name', 'super-admin')->firstOrFail()->users()->count() <= 1) {
            Flux::toast(variant: 'danger', text: __('Minimal satu pengguna harus tetap menjadi super-admin.'));

            return;
        }

        $user->delete();
        $this->userToDeleteId = null;
        $this->userToDeleteName = '';
        Flux::modal('confirm-user-deletion')->close();
        Flux::toast(variant: 'success', text: __('Pengguna berhasil dihapus.'));
    }

    public function render(): View
    {
        $search = trim($this->search);

        return view('livewire.settings.users', [
            'users' => User::query()
                ->with('roles')
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                })
                ->orderBy('name')
                ->paginate(10),
        ]);
    }

    private function authorizeManagement(): void
    {
        abort_unless(auth()->user()->can('roles.manage'), 403);
    }
}
