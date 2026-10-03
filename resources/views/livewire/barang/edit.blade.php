<section class="space-y-8">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Ubah barang') }}</flux:heading>
        <flux:subheading>{{ __('Perbarui informasi barang dan lihat riwayat harganya.') }}</flux:subheading>
    </div>

    @include('livewire.barang.form', ['isEdit' => true])

    <section class="space-y-4 border-t border-[#EADFCE] pt-6 dark:border-[#563D35]">
        <div>
            <flux:heading size="lg">{{ __('Riwayat harga') }}</flux:heading>
            <flux:subheading>{{ __('Perubahan harga untuk :code.', ['code' => $barang->kode_barang]) }}</flux:subheading>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full max-w-2xl text-left text-sm">
                <thead class="border-b border-[#EADFCE] text-[#70574D] dark:border-[#563D35] dark:text-[#D8C8B6]">
                    <tr>
                        <th class="py-3 font-medium">{{ __('Waktu perubahan') }}</th>
                        <th class="py-3 text-right font-medium">{{ __('Harga barang') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#EADFCE] dark:divide-[#563D35]">
                    @forelse ($barang->hargaHistory as $history)
                        <tr wire:key="harga-history-{{ $history->id }}">
                            <td class="py-3">{{ $history->created_at->copy()->timezone($timezone)->format('d M Y, H:i') }}</td>
                            <td class="py-3 text-right">Rp {{ number_format((float) $history->harga_barang, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="py-4 text-[#70574D] dark:text-[#D8C8B6]">{{ __('Belum ada riwayat harga.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</section>