<div class="space-y-6">
    <div>
        <flux:heading size="xl">Selamat Datang, {{ auth()->user()->name }}</flux:heading>
        <flux:subheading>Ringkasan transaksi Anda.</flux:subheading>
    </div>

    <section class="grid gap-4 sm:grid-cols-2">
        <article class="rounded-lg border border-[#EADFCE] bg-[#FFFDF8] p-5 dark:border-[#563D35] dark:bg-[#2B1B18]">
            <p class="text-sm text-[#70574D] dark:text-[#D8C8B6]">Transaksi aktif</p>
            <p class="mt-2 text-3xl font-semibold">{{ number_format($activeCount) }}</p>
        </article>
        <article class="rounded-lg border border-[#EADFCE] bg-[#FFFDF8] p-5 dark:border-[#563D35] dark:bg-[#2B1B18]">
            <p class="text-sm text-[#70574D] dark:text-[#D8C8B6]">Transaksi selesai</p>
            <p class="mt-2 text-3xl font-semibold">{{ number_format($completedCount) }}</p>
        </article>
    </section>

    <section class="space-y-3">
        <div class="flex items-center justify-between gap-3">
            <flux:heading size="lg">Transaksi terbaru</flux:heading>
            <flux:button variant="ghost" size="sm" :href="route('customer.transactions', ['type' => 'active'])" wire:navigate>
                Lihat transaksi
            </flux:button>
        </div>

        <div class="overflow-x-auto rounded-lg border border-[#EADFCE] bg-[#FFFDF8] dark:border-[#563D35] dark:bg-[#2B1B18]">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-[#EADFCE] bg-[#F9F3E5] text-[#70574D] dark:border-[#563D35] dark:bg-[#211311] dark:text-[#D8C8B6]">
                    <tr>
                        <th class="px-4 py-3 font-medium">Nomor transaksi</th>
                        <th class="px-4 py-3 font-medium">Tanggal</th>
                        <th class="px-4 py-3 text-right font-medium">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#EADFCE] dark:divide-[#563D35]">
                    @forelse ($recentSales as $sale)
                        <tr wire:key="customer-dashboard-sale-{{ $sale->id }}">
                            <td class="px-4 py-3 font-medium">{{ $sale->manual_invoice_number ?: $sale->invoice_number }}</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $sale->sale_date->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-right">{{ match ($sale->status) {
                                'new', 'not_started' => 'Baru',
                                'in_progress' => 'Diproses',
                                'completed' => 'Selesai',
                                'cancelled' => 'Dibatalkan',
                                default => 'Draf',
                            } }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center text-[#70574D] dark:text-[#D8C8B6]">Belum ada transaksi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>