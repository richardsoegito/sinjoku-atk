<?php

namespace App\Livewire\Settings\Users;

use App\Livewire\Settings\UserForm;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Ubah pengguna')]
class Edit extends Component
{
    use UserForm;

    #[Locked]
    public int $userId;

    public function mount(User $user): void
    {
        $this->authorizeUserManagement();
        $user->load(['roles', 'customer']);
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->username = $user->username;
        $this->email = $user->email;
        $this->roleId = (string) $user->roles()->value('roles.id');
        $this->customerAddress = (string) $user->customer()->value('alamat');
    }

    public function update(): void
    {
        $user = User::query()->findOrFail($this->userId);

        if (! $this->saveManagedUser($user)) {
            return;
        }

        Flux::toast(variant: 'success', text: __('Data pengguna berhasil disimpan.'));
        $this->redirect(route('settings.users'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.settings.users.edit', $this->userFormViewData());
    }
}
