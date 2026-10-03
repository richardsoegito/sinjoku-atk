<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <flux:heading size="xl">Dasbor</flux:heading>
            <flux:subheading>Ringkasan penjualan dan pemrosesan pesanan Anda.</flux:subheading>
        </div>
        <flux:button variant="primary" icon="plus" :href="route('sales.create')" wire:navigate>
            Buat transaksi
        </flux:button>
    </div>

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-lg border border-[#EADFCE] border-t-4 border-t-[#4B150F] bg-[#FFFDF8] p-4 dark:border-[#563D35] dark:bg-[#2B1B18]">
            <p class="text-sm text-[#70574D] dark:text-[#D8C8B6]">Penjualan bulan ini</p>
            <p class="mt-2 text-2xl font-semibold text-[#3D2923] dark:text-[#FFF4ED]">Rp {{ number_format($monthRevenue, 0, ',', '.') }}</p>
            <p class="mt-2 text-xs text-[#70574D] dark:text-[#D8C8B6]">
                @if ($revenueChange === null)
                    Belum ada data pembanding bulan lalu
                @else
                    <span class="font-semibold {{ $revenueChange >= 0 ? 'text-[#39704B]' : 'text-[#A13D2D]' }}">{{ $revenueChange > 0 ? '+' : '' }}{{ $revenueChange }}%</span>
                    dibanding bulan lalu
                @endif
            </p>
        </article>

        <article class="rounded-lg border border-[#EADFCE] border-t-4 border-t-[#8A6B36] bg-[#FFFDF8] p-4 dark:border-[#563D35] dark:bg-[#2B1B18]">
            <p class="text-sm text-[#70574D] dark:text-[#D8C8B6]">Transaksi bulan ini</p>
            <p class="mt-2 text-2xl font-semibold text-[#3D2923] dark:text-[#FFF4ED]">{{ number_format($monthOrders) }}</p>
            <p class="mt-2 text-xs text-[#70574D] dark:text-[#D8C8B6]">Berdasarkan tanggal transaksi</p>
        </article>

        <article class="rounded-lg border border-[#EADFCE] border-t-4 border-t-[#58775B] bg-[#FFFDF8] p-4 dark:border-[#563D35] dark:bg-[#2B1B18]">
            <p class="text-sm text-[#70574D] dark:text-[#D8C8B6]">Pesanan aktif</p>
            <p class="mt-2 text-2xl font-semibold text-[#3D2923] dark:text-[#FFF4ED]">{{ number_format($activeOrders) }}</p>
            <p class="mt-2 text-xs text-[#70574D] dark:text-[#D8C8B6]">Baru atau sedang diproses</p>
        </article>

        <article class="rounded-lg border border-[#EADFCE] border-t-4 border-t-[#9B563E] bg-[#FFFDF8] p-4 dark:border-[#563D35] dark:bg-[#2B1B18]">
            <p class="text-sm text-[#70574D] dark:text-[#D8C8B6]">Unit belum terkirim</p>
            <p class="mt-2 text-2xl font-semibold text-[#3D2923] dark:text-[#FFF4ED]">{{ number_format($deliveryCounts['new'] + $deliveryCounts['loaded']) }}</p>
            <p class="mt-2 text-xs text-[#70574D] dark:text-[#D8C8B6]">{{ number_format($deliveryCounts['new']) }} menunggu · {{ number_format($deliveryCounts['loaded']) }} perjalanan</p>
        </article>
    </section>

    <section class="grid gap-4 xl:grid-cols-[minmax(0,1.6fr)_minmax(18rem,1fr)]">
        <article class="min-w-0 rounded-lg border border-[#EADFCE] bg-[#FFFDF8] p-4 dark:border-[#563D35] dark:bg-[#2B1B18] sm:p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="font-semibold text-[#3D2923] dark:text-[#FFF4ED]">Tren penjualan</h2>
                    <p class="mt-1 text-sm text-[#70574D] dark:text-[#D8C8B6]">Nilai penjualan enam bulan terakhir</p>
                </div>
                <span class="text-xs text-[#70574D] dark:text-[#D8C8B6]">Berdasarkan harga satuan × jumlah</span>
            </div>

            <div class="mt-6 grid h-48 grid-cols-6 items-end gap-2 sm:h-56 sm:gap-4">
                @foreach ($monthlyTrend as $month)
                    @php($barHeight = $month['revenue'] > 0 ? max(7, ($month['revenue'] / $maxMonthlyRevenue) * 100) : 3)
                    <div class="flex h-full min-w-0 flex-col items-center justify-end gap-2">
                        <span class="w-full truncate text-center text-[10px] text-[#70574D] dark:text-[#D8C8B6] sm:text-xs">{{ $month['revenue'] > 0 ? number_format($month['revenue'] / 1000000, 1, ',', '.').' juta' : '—' }}</span>
                        <div class="flex h-[72%] w-full items-end overflow-hidden rounded-t-md bg-[#F2EBDD] dark:bg-[#211311]">
                            <div class="w-full rounded-t-md bg-[#6D8A68] transition-all" style="height: {{ $barHeight }}%" title="Rp {{ number_format($month['revenue'], 0, ',', '.') }}"></div>
                        </div>
                        <span class="text-xs font-medium text-[#70574D] dark:text-[#D8C8B6]">{{ $month['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </article>

        <article class="rounded-lg border border-[#EADFCE] bg-[#FFFDF8] p-4 dark:border-[#563D35] dark:bg-[#2B1B18] sm:p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="font-semibold text-[#3D2923] dark:text-[#FFF4ED]">Status pengiriman</h2>
                    <p class="mt-1 text-sm text-[#70574D] dark:text-[#D8C8B6]">Rincian berdasarkan unit barang</p>
                </div>
                <a href="{{ route('sales.index') }}" wire:navigate class="text-sm font-medium text-[#6C3429] hover:underline dark:text-[#E1A88C]">Lihat penjualan</a>
            </div>

            @php($deliveryTotal = $deliveryCounts['new'] + $deliveryCounts['loaded'] + $deliveryCounts['delivered'])
            <div class="mt-6 space-y-5">
                @foreach ([['label' => 'Menunggu diproses', 'key' => 'new', 'color' => '#A58B59'], ['label' => 'Dalam perjalanan', 'key' => 'loaded', 'color' => '#557A75'], ['label' => 'Terkirim', 'key' => 'delivered', 'color' => '#63805D']] as $deliveryStatus)
                    @php($deliveryPercent = $deliveryTotal > 0 ? ($deliveryCounts[$deliveryStatus['key']] / $deliveryTotal) * 100 : 0)
                    <div>
                        <div class="mb-2 flex justify-between gap-3 text-sm">
                            <span class="text-[#4B3A32] dark:text-[#F1E4D8]">{{ $deliveryStatus['label'] }}</span>
                            <span class="font-semibold">{{ number_format($deliveryCounts[$deliveryStatus['key']]) }} <span class="font-normal text-[#70574D] dark:text-[#D8C8B6]">unit</span></span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-[#F2EBDD] dark:bg-[#211311]">
                            <div class="h-full rounded-full" style="width: {{ $deliveryPercent }}%; background-color: {{ $deliveryStatus['color'] }}"></div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 border-t border-[#EADFCE] pt-4 dark:border-[#563D35]">
                <p class="text-sm text-[#70574D] dark:text-[#D8C8B6]">Pesanan selesai</p>
                <p class="mt-1 text-xl font-semibold">{{ number_format($completedOrders) }} <span class="text-sm font-normal text-[#70574D] dark:text-[#D8C8B6]">transaksi</span></p>
            </div>
        </article>
    </section>

    <section class="grid gap-4 xl:grid-cols-[minmax(0,1.6fr)_minmax(18rem,1fr)]">
        <article class="min-w-0 rounded-lg border border-[#EADFCE] bg-[#FFFDF8] dark:border-[#563D35] dark:bg-[#2B1B18]">
            <div class="flex items-center justify-between gap-3 border-b border-[#EADFCE] px-4 py-4 dark:border-[#563D35] sm:px-5">
                <div>
                    <h2 class="font-semibold text-[#3D2923] dark:text-[#FFF4ED]">Transaksi terbaru</h2>
                    <p class="mt-1 text-sm text-[#70574D] dark:text-[#D8C8B6]">Enam transaksi terakhir</p>
                </div>
                <a href="{{ route('sales.index') }}" wire:navigate class="shrink-0 text-sm font-medium text-[#6C3429] hover:underline dark:text-[#E1A88C]">Semua penjualan</a>
            </div>

            <div class="hidden grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)_8rem_10rem] gap-4 bg-[#F9F3E5] px-5 py-3 text-xs font-medium text-[#70574D] dark:bg-[#211311] dark:text-[#D8C8B6] md:grid">
                <span>Faktur / toko</span>
                <span>Tanggal</span>
                <span>Status</span>
                <span class="text-right">Total</span>
            </div>

            <div class="divide-y divide-[#EADFCE] dark:divide-[#563D35]">
                <?php if ($recentSales->isEmpty()): ?>
                    <p class="px-4 py-8 text-center text-sm text-[#70574D] dark:text-[#D8C8B6]">Belum ada transaksi.</p>
                <?php else: ?>
                    <?php foreach ($recentSales as $recentSale): ?>
                        <?php
                            $saleTotal = $recentSale->items->sum(fn ($item): float => (float) $item->quantity * (float) $item->unit_price);
                            $saleStatus = match ($recentSale->status) {
                                'completed' => ['Selesai', '#E5F0E5', '#285C3B'],
                                'in_progress' => ['Diproses', '#FFF0C9', '#78510B'],
                                default => ['Pesanan baru', '#E9ECE9', '#43554B'],
                            };
                        ?>
                        <div class="grid gap-3 p-4 md:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)_8rem_10rem] md:items-center md:gap-4 md:px-5">
                            <div class="flex min-w-0 items-start justify-between gap-3 md:block">
                                <div class="min-w-0">
                                    <p class="truncate font-medium">{{ $recentSale->store_name }}</p>
                                    <p class="mt-1 text-xs text-[#70574D] dark:text-[#D8C8B6]">{{ $recentSale->invoice_number }}</p>
                                </div>
                                <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium md:hidden" style="background-color: {{ $saleStatus[1] }}; color: {{ $saleStatus[2] }}">{{ $saleStatus[0] }}</span>
                            </div>
                            <p class="text-xs text-[#70574D] dark:text-[#D8C8B6] md:text-sm">{{ $recentSale->sale_date->format('d M Y') }}</p>
                            <span class="hidden w-fit rounded-full px-2.5 py-1 text-xs font-medium md:inline-flex" style="background-color: {{ $saleStatus[1] }}; color: {{ $saleStatus[2] }}">{{ $saleStatus[0] }}</span>
                            <p class="text-sm font-semibold md:text-right">Rp {{ number_format($saleTotal, 0, ',', '.') }}</p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </article>

        <article class="rounded-lg border border-[#EADFCE] bg-[#FFFDF8] dark:border-[#563D35] dark:bg-[#2B1B18]">
            <div class="border-b border-[#EADFCE] px-4 py-4 dark:border-[#563D35] sm:px-5">
                <h2 class="font-semibold text-[#3D2923] dark:text-[#FFF4ED]">Produk terlaris</h2>
                <p class="mt-1 text-sm text-[#70574D] dark:text-[#D8C8B6]">Urut berdasarkan jumlah unit</p>
            </div>
            <div class="divide-y divide-[#EADFCE] dark:divide-[#563D35]">
                @forelse ($topProducts as $product)
                    <div class="flex items-center gap-3 px-4 py-4 sm:px-5">
                        <span class="grid size-8 shrink-0 place-items-center rounded-md bg-[#F2EBDD] text-sm font-semibold text-[#6C3429] dark:bg-[#211311] dark:text-[#E1A88C]">{{ $loop->iteration }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">{{ $product->product_name }}</p>
                            <p class="mt-1 text-xs text-[#70574D] dark:text-[#D8C8B6]">{{ number_format((int) $product->units) }} unit terjual</p>
                        </div>
                        <div class="h-1.5 w-12 shrink-0 overflow-hidden rounded-full bg-[#F2EBDD] dark:bg-[#211311] sm:w-16">
                            <div class="h-full rounded-full bg-[#6D8A68]" style="width: {{ max(8, ((int) $product->units / $maxProductUnits) * 100) }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="px-4 py-8 text-center text-sm text-[#70574D] dark:text-[#D8C8B6]">Produk akan muncul setelah transaksi dicatat.</p>
                @endforelse
            </div>
        </article>
    </section>
</div>