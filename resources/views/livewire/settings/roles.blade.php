<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <flux:heading size="xl">Kelola peran</flux:heading>
            <flux:subheading>Atur izin setiap peran dan tetapkan satu peran untuk tiap pengguna.</flux:subheading>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="createRole">Tambah peran</flux:button>
    </div>

    <section class="space-y-3">
        <h2 class="text-sm font-semibold text-[#70574D] dark:text-[#D8C8B6]">Daftar peran</h2>
        @forelse ($roles as $role)
            @php($isSystemRole = in_array($role->name, ['super-admin', 'admin', 'owner'], true))
            <article class="rounded-lg border border-[#EADFCE] bg-[#FFFDF8] p-4 dark:border-[#563D35] dark:bg-[#2B1B18] sm:p-5">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="font-semibold">{{ str($role->name)->headline() }}</h3>
                            <span class="rounded-full bg-[#F2EBDD] px-2.5 py-1 text-xs text-[#6C3429] dark:bg-[#211311] dark:text-[#E1A88C]">{{ $role->users_count }} pengguna</span>
                            @if ($isSystemRole)
                                <span class="text-xs text-[#70574D] dark:text-[#D8C8B6]">Peran bawaan</span>
                            @endif
                        </div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @forelse ($role->permissions as $permission)
                                <span class="rounded-md border border-[#EADFCE] px-2 py-1 text-xs text-[#594840] dark:border-[#563D35] dark:text-[#E8D9CC]">{{ $permission->label ?: $permission->name }}</span>
                            @empty
                                <span class="text-sm text-[#70574D] dark:text-[#D8C8B6]">Belum memiliki izin.</span>
                            @endforelse
                        </div>
                    </div>
                    <div class="flex shrink-0 gap-2">
                        <flux:button size="sm" variant="outline" icon="pencil-square" wire:click="editRole({{ $role->id }})">Ubah</flux:button>
                        <flux:button size="sm" variant="ghost" icon="trash" wire:click="prepareDeleteRole({{ $role->id }})" :disabled="$isSystemRole || $role->users_count > 0" aria-label="Hapus peran" />
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-lg border border-[#EADFCE] bg-[#FFFDF8] px-5 py-10 text-center dark:border-[#563D35] dark:bg-[#2B1B18]">
                <p class="font-medium">Belum ada peran</p>
                <p class="mt-1 text-sm text-[#70574D] dark:text-[#D8C8B6]">Tambahkan peran untuk mulai mengatur akses.</p>
            </div>
        @endforelse
    </section>

    <section class="overflow-hidden rounded-lg border border-[#EADFCE] bg-[#FFFDF8] dark:border-[#563D35] dark:bg-[#2B1B18]">
        <div class="border-b border-[#EADFCE] px-4 py-4 dark:border-[#563D35] sm:px-5">
            <h2 class="font-semibold">Peran pengguna</h2>
            <p class="mt-1 text-sm text-[#70574D] dark:text-[#D8C8B6]">Setiap pengguna hanya dapat memiliki satu peran.</p>
        </div>
        <div class="divide-y divide-[#EADFCE] dark:divide-[#563D35]">
            @foreach ($users as $user)
                <div wire:key="user-role-{{ $user->id }}" class="grid gap-3 px-4 py-4 sm:grid-cols-[minmax(0,1fr)_14rem_auto] sm:items-center sm:px-5">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium">{{ $user->name }}</p>
                        <p class="truncate text-xs text-[#70574D] dark:text-[#D8C8B6]">{{ $user->email }}</p>
                    </div>
                    <flux:select wire:model="userRoleAssignments.{{ $user->id }}" :label="__('Peran')">
                        <flux:select.option value="">Pilih peran</flux:select.option>
                        @foreach ($roles as $role)
                            <flux:select.option value="{{ $role->id }}">{{ str($role->name)->headline() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:button size="sm" variant="outline" icon="check" wire:click="saveUserRole({{ $user->id }})">Simpan peran</flux:button>
                </div>
            @endforeach
        </div>
        @if ($users->hasPages())
            <div class="border-t border-[#EADFCE] p-4 dark:border-[#563D35]">{{ $users->links() }}</div>
        @endif
    </section>

    <flux:modal name="role-editor" class="max-w-3xl">
        <form wire:submit="saveRole" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $editingRoleId ? 'Ubah peran' : 'Tambah peran' }}</flux:heading>
                <flux:subheading>Pilih izin yang dapat dijalankan oleh peran ini.</flux:subheading>
            </div>

            <flux:input wire:model="name" label="Nama peran" placeholder="Contoh: supervisor" :readonly="$editingRoleId && in_array($name, ['super-admin', 'admin', 'owner'], true)" />

            <div class="max-h-[55vh] space-y-4 overflow-y-auto pe-1">
                @foreach ($permissionGroups as $group => $permissions)
                    <fieldset class="rounded-lg border border-[#EADFCE] p-4 dark:border-[#563D35]">
                        <legend class="px-1 text-sm font-semibold">{{ $group }}</legend>
                        <div class="grid gap-3 sm:grid-cols-2">
                            @foreach ($permissions as $permission)
                                <label class="flex cursor-pointer items-start gap-3 text-sm">
                                    <input type="checkbox" wire:model="selectedPermissionIds" value="{{ $permission->id }}" class="mt-0.5 size-4 rounded border-[#B6A598] text-[#6C3429] focus:ring-[#6C3429]" />
                                    <span class="min-w-0">
                                        <span class="block font-medium">{{ $permission->label ?: $permission->name }}</span>
                                        <span class="mt-0.5 block break-all text-xs text-[#70574D] dark:text-[#D8C8B6]">{{ $permission->name }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endforeach
            </div>

            <div class="flex justify-end gap-2 border-t border-[#EADFCE] pt-4 dark:border-[#563D35]">
                <flux:modal.close><flux:button variant="ghost" type="button">Batal</flux:button></flux:modal.close>
                <flux:button variant="primary" type="submit">Simpan peran</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="confirm-role-deletion" focusable class="max-w-lg">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">Hapus peran?</flux:heading>
                <flux:subheading>Peran ini akan dihapus permanen.</flux:subheading>
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Batal</flux:button></flux:modal.close>
                <flux:button variant="danger" icon="trash" wire:click="deleteRole">Hapus peran</flux:button>
            </div>
        </div>
    </flux:modal>
</div>