<main wire:poll.60s class="min-h-screen bg-[#F3EBDD] px-4 py-8 text-[#241916] sm:px-6 sm:py-12">
    @php
        $status = match ($sale->status) {
            'completed' => ['Selesai', '#E5F0E5', '#285C3B'],
            'in_progress' => ['Dalam pengiriman', '#FFF0C9', '#78510B'],
            default => ['Pesanan baru', '#E9ECE9', '#43554B'],
        };
        $processedQuantity = $deliveryCounts['loaded'] + $deliveryCounts['delivered'];
        $processedPercent = $quantityTotal > 0 ? round(($processedQuantity / $quantityTotal) * 100) : 0;
    @endphp

    <article class="mx-auto max-w-6xl overflow-hidden rounded-2xl border border-[#D5C8B7] bg-white shadow-[0_18px_60px_rgba(58,35,24,0.12)]">
        <div class="h-2 bg-[#4B150F]"></div>

        <header class="px-5 pb-6 pt-7 sm:px-10 sm:pt-9">
            <img src="{{ asset('images/logo.png') }}" alt="Sinjoku" class="mx-auto h-20 w-auto max-w-[180px] object-contain" />
            <div class="mt-6 flex flex-col gap-4 border-y border-[#E8DFD4] py-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase text-[#786A60]">Informasi transaksi</p>
                    <h1 class="mt-1 break-words text-2xl font-semibold leading-tight sm:text-3xl">{{ $sale->store_name }}</h1>
                    <p class="mt-2 text-sm text-[#71645B]">Nomor faktur <span class="font-semibold text-[#35241F]">{{ $sale->invoice_number }}</span></p>
                </div>
                <span class="inline-flex w-fit items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold" style="background-color: {{ $status[1] }}; color: {{ $status[2] }}">
                    <span class="size-2 rounded-full" style="background-color: {{ $status[2] }}"></span>
                    {{ $status[0] }}
                </span>
            </div>

            <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-xs text-[#786A60]">Tanggal transaksi</dt>
                    <dd class="mt-1 font-medium">{{ $sale->sale_date->format('d/m/Y') }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-[#786A60]">Perkembangan pesanan</dt>
                    <dd class="mt-1 flex items-center gap-3">
                        <span class="h-2 flex-1 overflow-hidden rounded-full bg-[#EEE8DE]">
                            <span class="block h-full rounded-full bg-[#53765D] transition-all duration-500" style="width: {{ $processedPercent }}%"></span>
                        </span>
                        <span class="font-semibold">{{ $processedPercent }}%</span>
                    </dd>
                </div>
            </dl>
        </header>

        <div class="space-y-8 px-5 pb-8 sm:px-10 sm:pb-10">
            <section class="grid gap-5 border-y border-[#E8DFD4] py-5 sm:grid-cols-[1.2fr_1fr]">
                <div>
                    <h2 class="text-xs font-semibold uppercase text-[#786A60]">Alamat pengiriman</h2>
                    <p class="mt-2 whitespace-pre-line text-sm leading-6 text-[#352B26]">{{ $sale->delivery_address ?: 'Alamat pengiriman belum tersedia.' }}</p>
                </div>
                @if ($sale->description)
                    <div class="sm:border-l sm:border-[#E8DFD4] sm:pl-5">
                        <h2 class="text-xs font-semibold uppercase text-[#786A60]">Catatan transaksi</h2>
                        <p class="mt-2 whitespace-pre-line text-sm leading-6 text-[#352B26]">{{ $sale->description }}</p>
                    </div>
                @endif
            </section>

            <section>
                <div class="mb-4 flex flex-wrap items-end justify-between gap-2">
                    <div>
                        <h2 class="text-lg font-semibold">Daftar barang</h2>
                        <p class="mt-1 text-sm text-[#786A60]">{{ $sale->items->count() }} jenis barang · {{ $quantityTotal }} unit</p>
                    </div>
                    <p class="text-xs text-[#786A60]">Status dihitung dari jumlah unit</p>
                </div>

                <div class="mb-4 grid grid-cols-3 divide-x divide-[#E8DFD4] rounded-lg border border-[#E8DFD4] bg-[#FBF9F5] py-3 text-center">
                    <div class="px-2">
                        <p class="text-xs text-[#786A60]">Menunggu</p>
                        <p class="mt-1 font-semibold">{{ $deliveryCounts['new'] }} <span class="text-xs font-normal text-[#786A60]">unit</span></p>
                    </div>
                    <div class="px-2">
                        <p class="text-xs text-[#786A60]">Perjalanan</p>
                        <p class="mt-1 font-semibold">{{ $deliveryCounts['loaded'] }} <span class="text-xs font-normal text-[#786A60]">unit</span></p>
                    </div>
                    <div class="px-2">
                        <p class="text-xs text-[#786A60]">Terkirim</p>
                        <p class="mt-1 font-semibold">{{ $deliveryCounts['delivered'] }} <span class="text-xs font-normal text-[#786A60]">unit</span></p>
                    </div>
                </div>

                <div class="hidden overflow-x-auto rounded-lg border border-[#D8CBBE] md:block">
                    <table class="w-full min-w-[850px] table-fixed text-left text-sm">
                        <thead class="bg-[#F5F0E8] text-xs uppercase text-[#65564D]">
                            <tr>
                                <th class="w-[25%] px-4 py-3 font-semibold">Nama barang</th>
                                <th class="w-[8%] px-3 py-3 font-semibold">Jumlah</th>
                                <th class="w-[21%] px-3 py-3 font-semibold">Transportasi</th>
                                <th class="w-[15%] px-3 py-3 font-semibold">Status</th>
                                <th class="w-[15%] px-3 py-3 font-semibold">Pembaruan</th>
                                <th class="w-[16%] px-4 py-3 text-right font-semibold">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E8DFD4]">
                            @foreach ($sale->items as $item)
                                @php
                                    $itemStatus = match ($item->status) {
                                        'delivered' => ['Terkirim', '#E5F0E5', '#285C3B'],
                                        'loaded' => ['Perjalanan', '#FFF0C9', '#78510B'],
                                        default => ['Menunggu', '#E9ECE9', '#43554B'],
                                    };
                                    $lineSubtotal = (float) $item->quantity * (float) $item->unit_price;
                                    $itemUpdatedAt = $item->shipped_at ?? $item->loaded_at;
                                @endphp
                                <tr wire:key="tracking-item-{{ $item->id }}" class="align-top even:bg-[#FCFBF9]">
                                    <td class="break-words px-4 py-3 font-medium">
                                        {{ $item->product_name }}
                                        <span class="mt-1 block text-xs font-normal text-[#786A60]">Rp {{ number_format((float) $item->unit_price, 0, ',', '.') }} / {{ $item->unit }}</span>
                                    </td>
                                    <td class="px-3 py-3">{{ $item->quantity }} {{ $item->unit }}</td>
                                    <td class="break-words px-3 py-3 text-[#574A43]">{{ $item->transport_name ?: '—' }}@if ($item->vehicle_number)<span class="mt-1 block text-xs text-[#786A60]">{{ $item->vehicle_number }}</span>@endif</td>
                                    <td class="px-3 py-3"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" style="background-color: {{ $itemStatus[1] }}; color: {{ $itemStatus[2] }}">{{ $itemStatus[0] }}</span></td>
                                    <td class="px-3 py-3 text-xs leading-5 text-[#65564D]">
                                        @if ($item->loaded_at)<span class="block">Muat {{ $item->loaded_at->format('d/m/Y H:i') }}</span>@endif
                                        @if ($item->shipped_at)<span class="block">Kirim {{ $item->shipped_at->format('d/m/Y H:i') }}</span>@endif
                                        @if (! $itemUpdatedAt) — @endif
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium">Rp {{ number_format($lineSubtotal, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="space-y-3 md:hidden">
                    @foreach ($sale->items as $item)
                        @php
                            $itemStatus = match ($item->status) {
                                'delivered' => ['Terkirim', '#E5F0E5', '#285C3B'],
                                'loaded' => ['Perjalanan', '#FFF0C9', '#78510B'],
                                default => ['Menunggu', '#E9ECE9', '#43554B'],
                            };
                            $lineSubtotal = (float) $item->quantity * (float) $item->unit_price;
                        @endphp
                        <article wire:key="tracking-item-mobile-{{ $item->id }}" class="rounded-lg border border-[#E8DFD4] bg-white p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h3 class="break-words font-semibold">{{ $item->product_name }}</h3>
                                    <p class="mt-1 text-xs text-[#786A60]">{{ $item->quantity }} {{ $item->unit }} × Rp {{ number_format((float) $item->unit_price, 0, ',', '.') }}</p>
                                </div>
                                <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold" style="background-color: {{ $itemStatus[1] }}; color: {{ $itemStatus[2] }}">{{ $itemStatus[0] }}</span>
                            </div>
                            <dl class="mt-3 grid grid-cols-2 gap-x-3 gap-y-2 border-t border-[#EEE8DE] pt-3 text-xs">
                                <div><dt class="text-[#786A60]">Transportasi</dt><dd class="mt-0.5 break-words font-medium">{{ $item->transport_name ?: '—' }}</dd></div>
                                <div><dt class="text-[#786A60]">Nomor kendaraan</dt><dd class="mt-0.5 break-words font-medium">{{ $item->vehicle_number ?: '—' }}</dd></div>
                                <div class="col-span-2"><dt class="text-[#786A60]">Pembaruan</dt><dd class="mt-0.5 font-medium">@if ($item->shipped_at)Kirim {{ $item->shipped_at->format('d/m/Y H:i') }}@elseif ($item->loaded_at)Muat {{ $item->loaded_at->format('d/m/Y H:i') }}@else Belum ada pembaruan @endif</dd></div>
                                <div class="col-span-2 flex justify-between gap-3 border-t border-[#EEE8DE] pt-2"><dt class="text-[#786A60]">Jumlah</dt><dd class="font-semibold">Rp {{ number_format($lineSubtotal, 0, ',', '.') }}</dd></div>
                            </dl>
                        </article>
                    @endforeach
                </div>

                <dl class="ml-auto mt-4 max-w-sm border-t border-[#D8CBBE] pt-4 text-sm">
                    <div class="flex justify-between gap-4 text-base font-semibold"><dt>Total transaksi</dt><dd>Rp {{ number_format($total, 0, ',', '.') }}</dd></div>
                </dl>
            </section>

            @if ($sale->delivery_proof_path)
                @php($proofExtension = strtolower(pathinfo($sale->delivery_proof_path, PATHINFO_EXTENSION)))
                <section class="border-t border-[#E8DFD4] pt-6">
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 class="font-semibold">Bukti pengiriman</h2>
                            <p class="mt-1 text-xs text-[#786A60]">Dokumen pengiriman untuk transaksi ini</p>
                        </div>
                        <a href="{{ route('sales.track.delivery-proof', $token) }}" target="_blank" rel="noopener" class="text-sm font-semibold text-[#4B150F] underline decoration-[#B7A28E] underline-offset-4 hover:text-[#7A2A1F]">Buka dokumen</a>
                    </div>
                    @if (in_array($proofExtension, ['jpg', 'jpeg', 'png', 'webp'], true))
                        <img src="{{ route('sales.track.delivery-proof', $token) }}" alt="Bukti pengiriman" class="max-h-[32rem] w-full rounded-lg border border-[#E8DFD4] bg-[#F8F5EF] object-contain" />
                    @elseif ($proofExtension === 'pdf')
                        <iframe src="{{ route('sales.track.delivery-proof', $token) }}" title="Bukti pengiriman PDF" class="h-[28rem] w-full rounded-lg border border-[#E8DFD4] bg-[#F8F5EF] sm:h-[36rem]"></iframe>
                    @endif
                </section>
            @endif
        </div>

        <footer class="border-t border-[#E8DFD4] bg-[#FBF9F5] px-5 py-5 text-center sm:px-10">
            <img src="{{ asset('images/logo.png') }}" alt="Sinjoku" class="mx-auto h-12 w-auto max-w-[120px] object-contain" />
            <p class="mt-3 text-xs text-[#786A60]">Terakhir diperbarui {{ $updatedAt }} · status diperbarui otomatis setiap 1 menit</p>
        </footer>
        <div class="h-2 bg-[#4B150F]"></div>
    </article>
</main>