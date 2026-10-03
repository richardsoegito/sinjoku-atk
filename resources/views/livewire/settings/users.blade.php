<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">Pengguna</flux:heading>
            <flux:subheading>Kelola akun pengguna dan peran aksesnya.</flux:subheading>
        </div>
        <flux:button variant="primary" icon="plus" :href="route('settings.users.create')" wire:navigate>
            Tambah pengguna
        </flux:button>
    </div>

    {{-- Card --}}
    <section class="overflow-hidden rounded-xl border border-[#EADFCE] bg-[#FFFDF8] dark:border-[#563D35] dark:bg-[#2B1B18]">
        {{-- Toolbar --}}
        <div class="border-b border-[#EADFCE] bg-[#F9F3E5] p-4 dark:border-[#563D35] dark:bg-[#211311]">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="w-full sm:max-w-sm">
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        icon="magnifying-glass"
                        placeholder="Cari nama atau email..."
                        clearable
                    />
                </div>
                <span class="text-xs text-[#70574D] sm:ml-auto dark:text-[#D8C8B6]">
                    {{ $users->total() }} pengguna
                </span>
            </div>
        </div>

        {{-- Table header --}}
        <div class="hidden grid-cols-[minmax(0,1.4fr)_minmax(0,1.6fr)_10rem_6rem] gap-4 border-b border-[#EADFCE] px-5 py-3 text-xs font-semibold uppercase tracking-wide text-[#70574D] dark:border-[#563D35] dark:text-[#D8C8B6] sm:grid">
            <span>Nama / Username</span>
            <span>Email</span>
            <span>Peran</span>
            <span class="text-right">Aksi</span>
        </div>

        {{-- Rows --}}
        <div class="divide-y divide-[#EADFCE] dark:divide-[#563D35]">
            @forelse ($users as $user)
                @php $roleName = $user->roles->first()?->name; @endphp
                <div
                    wire:key="managed-user-{{ $user->id }}"
                    class="grid gap-3 px-5 py-4 transition hover:bg-[#F9F3E5]/60 sm:grid-cols-[minmax(0,1.4fr)_minmax(0,1.6fr)_10rem_6rem] sm:items-center sm:gap-4 dark:hover:bg-[#211311]/60"
                >
                    {{-- Avatar + Nama --}}
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#EADFCE] text-sm font-semibold text-[#70574D] dark:bg-[#563D35] dark:text-[#F9F3E5]">
                            {{ str($user->name)->substr(0, 1)->upper() }}
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium">{{ $user->name }}</p>
                            <span class="truncate text-xs text-[#70574D] dark:text-[#D8C8B6]">{{ '@'.$user->username }}</span>
                            @if ($user->is(auth()->user()))
                                <span class="text-xs text-[#70574D] dark:text-[#D8C8B6]">Akun Anda</span>
                            @endif
                        </div>
                    </div>

                    {{-- Email --}}
                    <p class="truncate text-sm text-[#70574D] dark:text-[#D8C8B6]">
                        {{ $user->email }}
                    </p>

                    {{-- Peran --}}
                    <div>
                        @if ($roleName)
                            <span class="inline-flex items-center rounded-full bg-[#EADFCE] px-2.5 py-0.5 text-xs font-medium text-[#70574D] dark:bg-[#563D35] dark:text-[#F9F3E5]">
                                {{ str($roleName)->headline() }}
                            </span>
                        @else
                            <span class="text-xs italic text-[#70574D] dark:text-[#D8C8B6]">
                                Belum ada peran
                            </span>
                        @endif
                    </div>

                    {{-- Aksi --}}
                    <div class="flex justify-start gap-1 sm:justify-end">
                        <flux:button
                            size="sm"
                            variant="ghost"
                            icon="pencil-square"
                            :href="route('settings.users.edit', $user)"
                            wire:navigate
                            aria-label="Ubah pengguna"
                            title="Ubah"
                        />
                        <flux:button
                            size="sm"
                            variant="ghost"
                            icon="trash"
                            wire:click="prepareDeleteUser({{ $user->id }})"
                            :disabled="$user->is(auth()->user())"
                            aria-label="Hapus pengguna"
                            title="Hapus"
                        />
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center gap-2 px-4 py-16 text-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-[#EADFCE] dark:bg-[#563D35]">
                        <flux:icon name="users" class="size-6 text-[#70574D] dark:text-[#D8C8B6]" />
                    </div>
                    <p class="text-sm font-medium">
                        {{ $search !== '' ? 'Pengguna tidak ditemukan' : 'Belum ada pengguna' }}
                    </p>
                    <p class="text-xs text-[#70574D] dark:text-[#D8C8B6]">
                        {{ $search !== '' ? 'Coba kata kunci lain atau hapus filter.' : 'Tambahkan pengguna pertama Anda untuk memulai.' }}
                    </p>
                </div>
            @endforelse
        </div>

        @if ($users->hasPages())
            <div class="border-t border-[#EADFCE] p-4 dark:border-[#563D35]">
                {{ $users->links() }}
            </div>
        @endif
    </section>

    {{-- Modal: Konfirmasi Hapus --}}
    <flux:modal name="confirm-user-deletion" focusable class="max-w-md">
        <div class="space-y-5">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-500/20">
                    <flux:icon name="exclamation-triangle" class="size-5 text-red-600 dark:text-red-400" />
                </div>
                <div class="space-y-1">
                    <flux:heading size="lg">Hapus pengguna?</flux:heading>
                    <flux:subheading>
                        Akun <span class="font-semibold">{{ $userToDeleteName }}</span>
                        akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.
                    </flux:subheading>
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" icon="trash" wire:click="deleteUser">
                    Hapus pengguna
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>