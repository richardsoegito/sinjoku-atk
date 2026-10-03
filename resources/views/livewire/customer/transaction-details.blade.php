<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <flux:heading size="xl">Detail transaksi</flux:heading>
            <flux:subheading>{{ $sale->manual_invoice_number ?: $sale->invoice_number }}</flux:subheading>
        </div>
        <flux:button variant="ghost" icon="arrow-left" :href="route('customer.transactions', ['type' => in_array($sale->status, ['completed', 'cancelled'], true) ? 'history' : 'active'])" wire:navigate>
            Kembali ke daftar
        </flux:button>
    </div>

    <section class="grid gap-4 sm:grid-cols-2">
        <div class="rounded-lg border border-[#EADFCE] bg-[#FFFDF8] p-4 dark:border-[#563D35] dark:bg-[#2B1B18]">
            <p class="text-xs text-[#70574D] dark:text-[#D8C8B6]">Tanggal transaksi</p>
            <p class="mt-1 font-medium">{{ $sale->sale_date->format('d M Y') }}</p>
        </div>
        <div class="rounded-lg border border-[#EADFCE] bg-[#FFFDF8] p-4 dark:border-[#563D35] dark:bg-[#2B1B18]">
            <p class="text-xs text-[#70574D] dark:text-[#D8C8B6]">Status</p>
            <p class="mt-1 font-medium">{{ match ($sale->status) {
                'new', 'not_started' => 'Baru',
                'completed' => 'Selesai',
                'cancelled' => 'Dibatalkan',
                default => 'Dalam proses',
            } }}</p>
        </div>
        <div class="rounded-lg border border-[#EADFCE] bg-[#FFFDF8] p-4 dark:border-[#563D35] dark:bg-[#2B1B18] sm:col-span-2">
            <p class="text-xs text-[#70574D] dark:text-[#D8C8B6]">Alamat pengiriman</p>
            <p class="mt-1 whitespace-pre-line font-medium">{{ $sale->delivery_address ?: 'Alamat pengiriman belum tersedia.' }}</p>
        </div>
        @if ($sale->description)
            <div class="rounded-lg border border-[#EADFCE] bg-[#FFFDF8] p-4 dark:border-[#563D35] dark:bg-[#2B1B18] sm:col-span-2">
                <p class="text-xs text-[#70574D] dark:text-[#D8C8B6]">Catatan</p>
                <p class="mt-1 whitespace-pre-line font-medium">{{ $sale->description }}</p>
            </div>
        @endif
    </section>

    <section class="overflow-hidden rounded-lg border border-[#EADFCE] bg-[#FFFDF8] dark:border-[#563D35] dark:bg-[#2B1B18]">
        <div class="border-b border-[#EADFCE] px-4 py-4 dark:border-[#563D35]">
            <flux:heading size="lg">Barang</flux:heading>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-[#EADFCE] bg-[#F9F3E5] text-[#70574D] dark:border-[#563D35] dark:bg-[#211311] dark:text-[#D8C8B6]">
                    <tr>
                        <th class="px-4 py-3 font-medium">Nama barang</th>
                        <th class="px-4 py-3 text-right font-medium">Jumlah</th>
                        <th class="px-4 py-3 text-right font-medium">Harga satuan</th>
                        <th class="px-4 py-3 text-right font-medium">Subtotal</th>
                        <th class="px-4 py-3 font-medium">Status pengiriman</th>
                        <th class="px-4 py-3 font-medium">Transportasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#EADFCE] dark:divide-[#563D35]">
                    @foreach ($sale->items as $item)
                        <tr wire:key="customer-sale-item-{{ $item->id }}">
                            <td class="px-4 py-3 font-medium">{{ $item->product_name }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">{{ $item->quantity }} {{ $item->unit }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">Rp {{ number_format((float) $item->unit_price, 0, ',', '.') }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">Rp {{ number_format((float) $item->quantity * (float) $item->unit_price, 0, ',', '.') }}</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ match ($item->status) { 'loaded' => 'Dalam perjalanan', 'delivered' => 'Terkirim', default => 'Menunggu' } }}</td>
                            <td class="px-4 py-3">
                                {{ $item->transport_name ?: '—' }}
                                @if ($item->vehicle_number)
                                    <span class="block text-xs text-[#70574D] dark:text-[#D8C8B6]">{{ $item->vehicle_number }}</span>
                                @endif
                                @if ($item->loaded_at)
                                    <span class="block text-xs text-[#70574D] dark:text-[#D8C8B6]">Muat {{ $item->loaded_at->copy()->timezone($timezone)->format('d/m/Y H:i') }}</span>
                                @endif
                                @if ($item->shipped_at)
                                    <span class="block text-xs text-[#70574D] dark:text-[#D8C8B6]">Kirim {{ $item->shipped_at->copy()->timezone($timezone)->format('d/m/Y H:i') }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t border-[#EADFCE] dark:border-[#563D35]">
                    <tr>
                        <th colspan="3" class="px-4 py-4 text-right font-semibold">Total transaksi</th>
                        <td colspan="3" class="whitespace-nowrap px-4 py-4 text-right font-semibold">Rp {{ number_format($total, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>
</div>