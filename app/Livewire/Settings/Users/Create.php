<?php

namespace App\Livewire\Settings\Users;

use App\Livewire\Settings\UserForm;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Tambah pengguna')]
class Create extends Component
{
    use UserForm;

    public function mount(): void
    {
        $this->authorizeUserManagement();
    }

    public function save(): void
    {
        if (! $this->saveManagedUser()) {
            return;
        }

        Flux::toast(variant: 'success', text: __('Data pengguna berhasil disimpan.'));
        $this->redirect(route('settings.users'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.settings.users.create', $this->userFormViewData());
    }
}
