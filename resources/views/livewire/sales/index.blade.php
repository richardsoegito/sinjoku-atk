<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('Transaksi Pengiriman') }}</flux:heading>
            <flux:subheading>{{ __('Kelola transaksi pengiriman dan rincian barang.') }}</flux:subheading>
        </div>

        @can('sales.create')
            <flux:button variant="primary" icon="plus" :href="route('sales.create')" wire:navigate>
                {{ __('Buat transaksi') }}
            </flux:button>
        @endcan
    </div>

    <div class="flex flex-col gap-3 sm:flex-row">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Cari transaksi atau customer...')" />
        <flux:select wire:model.live="status" class="sm:w-48" :placeholder="__('Semua status')">
            @foreach ($statuses as $value => $label)
                <flux:select.option value="{{ $value }}">{{ __($label) }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="overflow-hidden rounded-xl border border-[#EADFCE] bg-[#FFFDF8] dark:border-[#563D35] dark:bg-[#2B1B18]">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-[#EADFCE] bg-[#F9F3E5] text-[#70574D] dark:border-[#563D35] dark:bg-[#211311] dark:text-[#D8C8B6]">
                    <tr>
                        <th class="px-5 py-3 font-medium">{{ __('No Transaksi') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Nomor Invoice') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Customer') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Tanggal Pengiriman') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Barang') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Status') }}</th>
                        <th class="px-5 py-3 text-right font-medium">{{ __('Tindakan') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#EADFCE] dark:divide-[#563D35]">
                    @forelse ($sales as $sale)
                        <tr wire:key="sale-{{ $sale->id }}" class="hover:bg-[#F9F3E5]/60 dark:hover:bg-[#211311]/60">
                            <td class="whitespace-nowrap px-5 py-4 font-medium">{{ $sale->invoice_number }}</td>
                            <td class="whitespace-nowrap px-5 py-4">{{ $sale->manual_invoice_number ?: '-' }}</td>
                            <td class="px-5 py-4">{{ $sale->store_name }}</td>
                            <td class="whitespace-nowrap px-5 py-4">{{ $sale->sale_date->format('d M Y') }}</td>
                            <td class="px-5 py-4">{{ $sale->items->count() }}</td>
                            <td class="px-5 py-4">
                                <flux:badge :color="match ($sale->status) {
                                    'draft' => 'zinc',
                                    'cancelled' => 'red',
                                    'new', 'not_started' => 'amber',
                                    'in_progress' => 'blue',
                                    'completed' => 'green',
                                    default => 'zinc',
                                }">
                                    {{ $statuses[$sale->status] ?? match ($sale->status) {
                                        'draft' => __('Draf'),
                                        'cancelled' => __('Dibatalkan'),
                                        'not_started' => __('Baru'),
                                        default => ucfirst(str_replace('_', ' ', $sale->status)),
                                    } }}
                                </flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    @can('sales.tracking-link.generate')
                                        <flux:button variant="ghost" size="sm" icon="link" wire:click="generateTrackingLink({{ $sale->id }})" :aria-label="__('Buat tautan pelacakan pelanggan')" />
                                    @endcan
                                    @if (auth()->user()->can('sales.update') || auth()->user()->can('sales.item-status.update') || auth()->user()->can('sales.delivery-proof.manage'))
                                        <flux:button variant="ghost" size="sm" icon="pencil-square" :href="route('sales.edit', $sale)" wire:navigate :aria-label="__('Ubah transaksi')" />
                                    @endif
                                    @can('sales.delete')
                                        <flux:modal.trigger name="confirm-sale-deletion">
                                            <flux:button variant="ghost" size="sm" icon="trash" wire:click="prepareDelete({{ $sale->id }})" :aria-label="__('Hapus transaksi')" />
                                        </flux:modal.trigger>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center">
                                <flux:heading>{{ __('Transaksi tidak ditemukan') }}</flux:heading>
                                <flux:subheading>{{ __('Buat transaksi pertama untuk melihatnya di sini.') }}</flux:subheading>
                            </td>
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

    <flux:modal name="sale-tracking-link" class="max-w-xl">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Tautan pelacakan pelanggan') }}</flux:heading>
                <flux:subheading>{{ __('Siapa pun yang memiliki tautan ini dapat melihat transaksi. Membuat tautan baru akan menonaktifkan tautan sebelumnya.') }}</flux:subheading>
            </div>

            <div x-data="{ copied: false, async copyLink(input) { if (this.copied) return; try { await navigator.clipboard.writeText(input.value); } catch { input.select(); document.execCommand('copy'); } this.copied = true; window.setTimeout(() => this.copied = false, 2500); } }" class="flex flex-col gap-2 sm:flex-row">
                <input x-ref="trackingLink" value="{{ $trackingUrl }}" readonly class="min-w-0 flex-1 rounded-lg border border-[#EADFCE] bg-[#F9F3E5] px-3 py-2 text-sm text-[#4B150F] focus:outline-none focus:ring-2 focus:ring-[#A13D2D]" />
                <flux:button icon="clipboard-document" x-on:click="copyLink($refs.trackingLink)" x-bind:disabled="copied"><span x-text="copied ? '{{ __('Tersalin') }}' : '{{ __('Salin tautan') }}'"></span></flux:button>
            </div>

            <div class="flex justify-end">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Tutup') }}</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="confirm-sale-deletion" focusable class="max-w-lg">
        <div class="space-y-6">
            <div class="space-y-2">
                <flux:heading size="lg">{{ __('Hapus transaksi pengiriman?') }}</flux:heading>
                <flux:subheading>
                    {{ __('Faktur :invoice beserta seluruh barangnya akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.', ['invoice' => $saleToDeleteInvoice]) }}
                </flux:subheading>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Batal') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="delete({{ $saleToDeleteId ?? 0 }})">
                    {{ __('Hapus transaksi') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
