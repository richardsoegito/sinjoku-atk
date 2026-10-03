<form wire:submit="{{ $isEdit ? 'update' : 'save' }}" class="max-w-3xl space-y-6">
    <div class="grid gap-5 sm:grid-cols-2">
        <flux:input wire:model="name" label="Nama" placeholder="Nama lengkap" autocomplete="name"  autofocus />
        <flux:input wire:model="username" label="Username" placeholder="username" autocomplete="username"  />
        <flux:input wire:model="email" label="Email" type="email" placeholder="nama@contoh.com" autocomplete="email"  />
        <flux:select wire:model.live="roleId" label="Peran" placeholder="Pilih peran" >
            @foreach ($roles as $role)
                <flux:select.option value="{{ $role->id }}">{{ str($role->name)->headline() }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    @if ($selectedRoleName === 'customer')
        <flux:textarea wire:model="customerAddress" label="Alamat customer" rows="4" />
    @endif

    <div class="space-y-1.5">
        <div class="grid gap-5 sm:grid-cols-2">
            <flux:input wire:model="password" :label="$isEdit ? 'Password baru (opsional)' : 'Password'" type="password" viewable autocomplete="new-password" :="!$isEdit" />
            <flux:input wire:model="password_confirmation" label="Konfirmasi password" type="password" viewable autocomplete="new-password" :="!$isEdit" />
        </div>
        <p class="text-xs text-[#70574D] dark:text-[#D8C8B6]">Minimal 8 karakter.{{ $isEdit ? ' Kosongkan jika tidak ingin mengubah.' : '' }}</p>
    </div>

    <div class="flex justify-end gap-2 border-t border-[#EADFCE] pt-4 dark:border-[#563D35]">
        <flux:button variant="filled" :href="route('settings.users')" wire:navigate>Kembali</flux:button>
        <flux:button variant="primary" type="submit">{{ $isEdit ? 'Simpan perubahan' : 'Tambah pengguna' }}</flux:button>
    </div>
</form>