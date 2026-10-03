<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('Barang') }}</flux:heading>
            <flux:subheading>{{ __('Daftar barang dan riwayat perubahan harga.') }}</flux:subheading>
        </div>

        @if ($canManage)
            <flux:button variant="primary" icon="plus" :href="route('barang.create')" wire:navigate>
                {{ __('Tambah barang') }}
            </flux:button>
        @endif
    </div>

    <div class="max-w-md">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Cari kode atau nama barang...')" />
    </div>

    <div class="overflow-hidden rounded-lg border border-[#EADFCE] bg-[#FFFDF8] dark:border-[#563D35] dark:bg-[#2B1B18]">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-[#EADFCE] bg-[#F9F3E5] text-[#70574D] dark:border-[#563D35] dark:bg-[#211311] dark:text-[#D8C8B6]">
                    <tr>
                        <th class="px-5 py-3 font-medium">{{ __('Kode barang') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Nama barang') }}</th>
                        <th class="px-5 py-3 text-right font-medium">{{ __('Harga barang') }}</th>
                        @if ($canManage)
                            <th class="px-5 py-3 text-right font-medium">{{ __('Tindakan') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#EADFCE] dark:divide-[#563D35]">
                    @forelse ($barang as $item)
                        <tr wire:key="barang-{{ $item->id }}" class="hover:bg-[#F9F3E5]/60 dark:hover:bg-[#211311]/60">
                            <td class="whitespace-nowrap px-5 py-4 font-medium">{{ $item->kode_barang }}</td>
                            <td class="px-5 py-4">{{ $item->nama_barang }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-right">Rp {{ number_format((float) $item->harga_barang, 0, ',', '.') }}</td>
                            @if ($canManage)
                                <td class="px-5 py-4 text-right">
                                    <flux:button variant="ghost" size="sm" icon="pencil-square" :href="route('barang.edit', $item)" wire:navigate :aria-label="__('Ubah barang :name', ['name' => $item->nama_barang])" />
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canManage ? 4 : 3 }}" class="px-5 py-12 text-center">
                                <flux:heading>{{ __('Barang tidak ditemukan') }}</flux:heading>
                                <flux:subheading>{{ __('Tambahkan barang untuk mulai mencatat harga.') }}</flux:subheading>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($barang->hasPages())
            <div class="border-t border-[#EADFCE] p-4 dark:border-[#563D35]">
                {{ $barang->links() }}
            </div>
        @endif
    </div>

</div>