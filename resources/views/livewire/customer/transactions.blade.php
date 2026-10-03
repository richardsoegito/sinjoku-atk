<div class="space-y-6">
    <div>
        <flux:heading size="xl">{{ $title }}</flux:heading>
        <flux:subheading>{{ $historyOnly ? __('Daftar transaksi selesai dan dibatalkan.') : __('Daftar transaksi baru dan yang sedang diproses.') }}</flux:subheading>
    </div>

    <div class="overflow-hidden rounded-lg border border-[#EADFCE] bg-[#FFFDF8] dark:border-[#563D35] dark:bg-[#2B1B18]">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-[#EADFCE] bg-[#F9F3E5] text-[#70574D] dark:border-[#563D35] dark:bg-[#211311] dark:text-[#D8C8B6]">
                    <tr>
                        <th class="px-4 py-3 font-medium">Nomor transaksi</th>
                        <th class="px-4 py-3 font-medium">Tanggal</th>
                        <th class="px-4 py-3 font-medium">Barang</th>
                        <th class="px-4 py-3 text-right font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#EADFCE] dark:divide-[#563D35]">
                    @forelse ($sales as $sale)
                        <tr wire:key="customer-sale-{{ $sale->id }}">
                            <td class="whitespace-nowrap px-4 py-3 font-medium">{{ $sale->manual_invoice_number ?: $sale->invoice_number }}</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $sale->sale_date->format('d M Y') }}</td>
                            <td class="px-4 py-3">
                                @foreach ($sale->items as $item)
                                    <span class="block">{{ $item->product_name }} × {{ $item->quantity }}</span>
                                @endforeach
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                {{ match ($sale->status) {
                                    'new', 'not_started' => __('Baru'),
                                    'completed' => __('Selesai'),
                                    'cancelled' => __('Dibatalkan'),
                                    default => __('Dalam proses'),
                                } }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <flux:button variant="ghost" size="sm" icon="eye" :href="route('customer.transactions.show', $sale)" wire:navigate :aria-label="__('Lihat detail transaksi :invoice', ['invoice' => $sale->manual_invoice_number ?: $sale->invoice_number])" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-[#70574D] dark:text-[#D8C8B6]">{{ $emptyMessage }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($sales->hasPages())
            <div class="border-t border-[#EADFCE] p-4 dark:border-[#563D35]">
                {{ $sales->links() }}
            </div>
        @endif
    </div>
</div>