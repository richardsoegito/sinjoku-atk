<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <flux:heading size="xl">Kelola izin</flux:heading>
            <flux:subheading>Atur nama, kunci teknis, dan grup izin akses.</flux:subheading>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="createPermission">Tambah izin</flux:button>
    </div>

    <div class="flex flex-wrap gap-2 border-b border-[#EADFCE] dark:border-[#563D35]">
        <a href="{{ route('settings.roles') }}" wire:navigate class="px-3 py-2 text-sm text-[#70574D] hover:text-[#4B150F] dark:text-[#D8C8B6]">Peran</a>
        <span class="border-b-2 border-[#6C3429] px-3 py-2 text-sm font-semibold text-[#4B150F] dark:text-[#F9F3E5]">Izin</span>
    </div>

    @forelse ($permissionGroups as $group => $permissions)
        <section class="overflow-hidden rounded-lg border border-[#EADFCE] bg-[#FFFDF8] dark:border-[#563D35] dark:bg-[#2B1B18]">
            <div class="flex items-center justify-between border-b border-[#EADFCE] px-4 py-3 dark:border-[#563D35] sm:px-5">
                <h2 class="font-semibold">{{ $group }}</h2>
                <span class="text-xs text-[#70574D] dark:text-[#D8C8B6]">{{ $permissions->count() }} izin</span>
            </div>
            <div class="divide-y divide-[#EADFCE] dark:divide-[#563D35]">
                @foreach ($permissions as $permission)
                    @php($isProtected = in_array($permission->name, $protectedPermissions, true))
                    <div wire:key="permission-{{ $permission->id }}" class="grid gap-3 px-4 py-4 sm:grid-cols-[minmax(0,1fr)_minmax(12rem,0.7fr)_auto] sm:items-center sm:px-5">
                        <div class="min-w-0">
                            <p class="font-medium">{{ $permission->label ?: $permission->name }}</p>
                            <p class="mt-1 break-all text-xs text-[#70574D] dark:text-[#D8C8B6]">{{ $permission->name }}</p>
                        </div>
                        <div class="text-xs text-[#70574D] dark:text-[#D8C8B6]">Digunakan {{ $permission->roles_count }} peran @if ($isProtected) · Izin bawaan @endif</div>
                        <div class="flex gap-2">
                            <flux:button size="sm" variant="outline" icon="pencil-square" wire:click="editPermission({{ $permission->id }})">Ubah</flux:button>
                            <flux:button size="sm" variant="ghost" icon="trash" wire:click="prepareDeletePermission({{ $permission->id }})" :disabled="$isProtected" aria-label="Hapus izin" />
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @empty
        <div class="rounded-lg border border-[#EADFCE] bg-[#FFFDF8] px-5 py-10 text-center dark:border-[#563D35] dark:bg-[#2B1B18]">
            <p class="font-medium">Belum ada izin</p>
            <p class="mt-1 text-sm text-[#70574D] dark:text-[#D8C8B6]">Tambahkan izin untuk mengatur akses setiap peran.</p>
        </div>
    @endforelse

    <flux:modal name="permission-editor" class="max-w-xl">
        <form wire:submit="savePermission" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $editingPermissionId ? 'Ubah izin' : 'Tambah izin' }}</flux:heading>
                <flux:subheading>Kunci izin digunakan di aturan akses aplikasi.</flux:subheading>
            </div>

            <flux:input wire:model="label" label="Nama izin" placeholder="Contoh: Ubah status barang" />
            <flux:input wire:model="name" label="Kunci izin" placeholder="contoh: sales.item-status.update" :readonly="$editingPermissionId && in_array($name, $protectedPermissions, true)" />
            <flux:input wire:model="group" label="Grup" placeholder="Contoh: Penjualan" />

            <div class="flex justify-end gap-2 border-t border-[#EADFCE] pt-4 dark:border-[#563D35]">
                <flux:modal.close><flux:button variant="ghost" type="button">Batal</flux:button></flux:modal.close>
                <flux:button variant="primary" type="submit">Simpan izin</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="confirm-permission-deletion" focusable class="max-w-lg">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">Hapus izin?</flux:heading>
                <flux:subheading>Izin yang dihapus akan dilepas dari seluruh peran.</flux:subheading>
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Batal</flux:button></flux:modal.close>
                <flux:button variant="danger" icon="trash" wire:click="deletePermission">Hapus izin</flux:button>
            </div>
        </div>
    </flux:modal>
</div>