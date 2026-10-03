<form wire:submit="{{ $isEdit ? ($canManageSale ? 'update' : 'updateItemStatuses') : 'save' }}" class="space-y-8">
    @if ($isEdit)
        @php($deliverySummary = $this->deliverySummary())
        <div class="space-y-4 rounded-xl border border-[#EADFCE] bg-[#FFFDF8] p-5 dark:border-[#563D35] dark:bg-[#2B1B18]">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <flux:heading size="lg">{{ __('Progres pengiriman') }}</flux:heading>
                    <flux:subheading>{{ __('Progres dihitung berdasarkan jumlah unit barang.') }}</flux:subheading>
                </div>
                <div class="text-right text-sm text-[#694F45] dark:text-[#D9BEB0]">
                    <div>{{ __('Jumlah unit') }}</div>
                    <div class="text-lg font-semibold text-[#3D2923] dark:text-[#FFF4ED]">{{ $deliverySummary['total'] }}</div>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-lg border border-[#EADFCE] bg-[#F9F3E5] p-4 dark:border-[#563D35] dark:bg-[#211311]">
                    <div class="text-sm text-[#694F45] dark:text-[#D9BEB0]">{{ __('Baru') }}</div>
                    <div class="mt-1 text-xl font-semibold">{{ $deliverySummary['new'] }}</div>
                    <div class="text-sm text-[#694F45] dark:text-[#D9BEB0]">{{ $deliverySummary['new_percent'] }}%</div>
                </div>
                <div class="rounded-lg border border-[#EADFCE] bg-[#F9F3E5] p-4 dark:border-[#563D35] dark:bg-[#211311]">
                    <div class="text-sm text-[#694F45] dark:text-[#D9BEB0]">{{ __('Muat') }}</div>
                    <div class="mt-1 text-xl font-semibold">{{ $deliverySummary['loaded'] }}</div>
                    <div class="text-sm text-[#694F45] dark:text-[#D9BEB0]">{{ $deliverySummary['loaded_percent'] }}%</div>
                </div>
                <div class="rounded-lg border border-[#EADFCE] bg-[#F9F3E5] p-4 dark:border-[#563D35] dark:bg-[#211311]">
                    <div class="text-sm text-[#694F45] dark:text-[#D9BEB0]">{{ __('Terkirim') }}</div>
                    <div class="mt-1 text-xl font-semibold">{{ $deliverySummary['delivered'] }}</div>
                    <div class="text-sm text-[#694F45] dark:text-[#D9BEB0]">{{ $deliverySummary['delivered_percent'] }}%</div>
                </div>
            </div>
        </div>
    @endif

    <div class="grid gap-6 md:grid-cols-2">
        <flux:select wire:model.live="customerUserId" :label="__('Nama Customer')" :disabled="!$canManageSale" :required="!$isEdit || filled($customerUserId)" placeholder="Pilih customer" autofocus>
            @if ($isEdit && !$customerUserId && filled($sale->store_name))
                <flux:select.option value="">{{ __('Customer lama: :name', ['name' => $sale->store_name]) }}</flux:select.option>
            @endif
            @foreach ($customers as $customer)
                <flux:select.option value="{{ $customer->id }}">{{ $customer->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model="invoiceNumber" :label="__('Nomor transaksi')" readonly />
        <flux:input wire:model="manualInvoiceNumber" :label="__('Nomor Invoice')" :disabled="!$canManageSale" />
        <flux:input wire:model="saleDate" :label="__('Tanggal pengiriman')" type="date" :disabled="!$canManageSale" required />
        <flux:input :label="__('Status')" :value="$statuses[$status] ?? __('Belum dimulai')" readonly />
    </div>

    <flux:textarea wire:model="description" :label="__('Catatan transaksi')" :placeholder="__('Tambahkan catatan untuk transaksi ini...')" :disabled="!$canManageSale" rows="3" />

    <div class="space-y-4 rounded-xl border border-[#EADFCE] bg-[#FFFDF8] p-5 dark:border-[#563D35] dark:bg-[#2B1B18]">
        <div>
            <flux:heading size="lg">{{ __('Detail pengiriman') }}</flux:heading>
            <flux:subheading>{{ __('Tambahkan alamat tujuan dan informasi transportasi untuk transaksi ini.') }}</flux:subheading>
        </div>

        <flux:textarea wire:model="deliveryAddress" :label="__('Alamat pengiriman')" :placeholder="__('Masukkan alamat pengiriman lengkap...')" :disabled="!$canManageSale" rows="3" />

    </div>

    <div class="space-y-4">
        <div class="flex items-center justify-between gap-4">
            <div>
                <flux:heading size="lg">{{ __('Rincian barang') }}</flux:heading>
                <flux:subheading>{{ __('Tambahkan barang yang termasuk dalam transaksi ini.') }}</flux:subheading>
            </div>
            @if ($canManageSale && (!$isEdit || in_array($sale->status, ['draft', 'new', 'not_started', 'in_progress'], true)))
                <flux:button type="button" variant="outline" icon="plus" wire:click="addItem">
                    {{ __('Tambah barang') }}
                </flux:button>
            @endif
        </div>

        <div class="space-y-3">
            @foreach ($items as $index => $item)
                <div wire:key="sale-item-{{ $index }}" class="grid gap-4 rounded-lg border border-[#EADFCE] bg-[#FFFDF8] p-4 dark:border-[#563D35] dark:bg-[#2B1B18] md:grid-cols-2 md:items-start xl:grid-cols-[minmax(0,1fr)_6rem_7rem_9rem_11rem_auto]">
                    <flux:select wire:model.live="items.{{ $index }}.product_id" :label="__('Nama barang')" :disabled="!$canManageSale" required placeholder="Pilih barang">
                        @php($productsSelectedElsewhere = collect($items)->except($index)->pluck('product_id')->map(fn ($productId) => (string) $productId)->all())
                        @foreach ($products as $product)
                            @if (!in_array((string) $product->id, $productsSelectedElsewhere, true))
                                <flux:select.option value="{{ $product->id }}">{{ $product->nama_barang }}</flux:select.option>
                            @endif
                        @endforeach
                    </flux:select>
                    <flux:input wire:model.live="items.{{ $index }}.quantity" :label="__('Jumlah')" type="number" min="1" step="1" :disabled="!$canManageSale" required />
                    <flux:input wire:model.live="items.{{ $index }}.unit" :label="__('Satuan')" placeholder="pcs" :disabled="!$canManageSale" required />
                    <flux:input wire:model.live="items.{{ $index }}.unit_price" :label="__('Harga satuan')" type="number" min="0" step="0.01" :disabled="!$canManageSale" required />
                    @if ($isEdit)
                        <flux:select wire:model.live="items.{{ $index }}.status" :label="__('Status pengiriman')" :disabled="!$canManageSale && !$canUpdateItemStatuses" required>
                            <flux:select.option value="new">{{ __('Baru') }}</flux:select.option>
                            <flux:select.option value="loaded">{{ __('Dalam perjalanan') }}</flux:select.option>
                            <flux:select.option value="delivered">{{ __('Terkirim') }}</flux:select.option>
                        </flux:select>
                    @endif
                    @if ($canManageSale)
                        <flux:button type="button" variant="ghost" icon="trash" class="justify-self-end md:mt-7" wire:click="removeItem({{ $index }})" :disabled="count($items) === 1" :aria-label="__('Hapus barang')" />
                    @endif
                    @if (in_array($item['status'] ?? 'new', ['loaded', 'delivered'], true))
                        <div class="grid gap-4 border-t border-[#EADFCE] pt-4 dark:border-[#563D35] md:col-span-2 md:grid-cols-2 xl:col-span-6">
                            <flux:input wire:model.live="items.{{ $index }}.transport_name" :label="__('Nama transportasi')" :placeholder="__('Contoh: Truk Ujang')" :disabled="!$canManageSale" />
                            <flux:input wire:model.live="items.{{ $index }}.vehicle_number" :label="__('Nomor kendaraan')" :placeholder="__('Contoh: L 4354 WP')" :disabled="!$canManageSale" />
                        </div>
                    @endif
                    <div class="space-y-1 text-sm text-[#694F45] dark:text-[#D9BEB0] md:col-span-2 md:text-right xl:col-span-6">
                        {{ __('Jumlah barang') }}: <span class="font-semibold">{{ number_format($this->itemSubtotal($item), 2, ',', '.') }}</span>
                        @if (!empty($item['loaded_at']))
                            <div>{{ __('Tanggal dimuat') }} ({{ $timezoneLabel }}): {{ $item['loaded_at'] }}</div>
                        @endif
                        @if (!empty($item['shipped_at']))
                            <div>{{ __('Tanggal dikirim') }} ({{ $timezoneLabel }}): {{ $item['shipped_at'] }}</div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="space-y-2 border-t border-[#EADFCE] pt-4 text-right dark:border-[#563D35]">
            <div class="text-lg font-semibold text-[#3D2923] dark:text-[#FFF4ED]">
                {{ __('Total transaksi') }}: {{ number_format($this->grandTotal(), 2, ',', '.') }}
            </div>
        </div>
    </div>

    @if ($isEdit)
        <div class="space-y-3">
            <flux:heading size="lg">{{ __('Bukti pengiriman') }}</flux:heading>
            @if (isset($sale) && $sale->delivery_proof_path)
            @php($deliveryProofExtension = strtolower(pathinfo($sale->delivery_proof_path, PATHINFO_EXTENSION)))
            <div class="space-y-3">
                @if (in_array($deliveryProofExtension, ['jpg', 'jpeg', 'png', 'webp'], true))
                    <a href="{{ route('sales.delivery-proof.view', $sale) }}" target="_blank" rel="noopener" class="inline-block">
                        <img src="{{ route('sales.delivery-proof.view', $sale) }}" alt="{{ __('Bukti pengiriman') }}" class="max-h-80 rounded-lg border border-[#EADFCE] bg-[#FFFDF8] object-contain" />
                    </a>
                @elseif ($deliveryProofExtension === 'pdf')
                    <iframe src="{{ route('sales.delivery-proof.view', $sale) }}" title="{{ __('Bukti pengiriman') }}" class="h-96 w-full rounded-lg border border-[#EADFCE] bg-[#FFFDF8]"></iframe>
                @else
                    <span class="text-sm">{{ __('Dokumen bukti pengiriman sudah diunggah') }}</span>
                @endif

                <div class="flex flex-wrap gap-2">
                    <flux:button variant="outline" icon="eye" :href="route('sales.delivery-proof.view', $sale)" target="_blank" rel="noopener">
                        {{ __('Lihat bukti pengiriman') }}
                    </flux:button>
                    <flux:button variant="ghost" icon="arrow-down-tray" :href="route('sales.delivery-proof.download', $sale)" target="_blank">
                        {{ __('Unduh bukti pengiriman') }}
                    </flux:button>
                    @if ($canManageDeliveryProof)
                        <flux:modal.trigger name="confirm-delivery-proof-deletion">
                            <flux:button type="button" variant="danger" icon="trash">
                                {{ __('Hapus bukti pengiriman') }}
                            </flux:button>
                        </flux:modal.trigger>
                    @endif
                </div>
            </div>
        @elseif ($canManageDeliveryProof)
            <flux:input wire:model="deliveryProof" type="file" accept="image/*,.pdf" :label="__('Unggah gambar atau PDF')" />
            <div wire:loading wire:target="deliveryProof" class="text-sm">{{ __('Sedang mengunggah...') }}</div>
        @else
            <p class="text-sm text-[#694F45] dark:text-[#D9BEB0]">Belum ada bukti pengiriman.</p>
            @endif
        </div>

        <flux:modal name="confirm-delivery-proof-deletion" focusable class="max-w-lg">
            <div class="space-y-6">
                <div class="space-y-2">
                    <flux:heading size="lg">{{ __('Hapus bukti pengiriman?') }}</flux:heading>
                    <flux:subheading>{{ __('Dokumen yang diunggah akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.') }}</flux:subheading>
                </div>

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="filled">{{ __('Batal') }}</flux:button>
                    </flux:modal.close>

                    <flux:button type="button" variant="danger" icon="trash" wire:click="deleteDeliveryProof">
                        {{ __('Hapus bukti pengiriman') }}
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif

    @if (($canManageSale && (!$isEdit || $sale->status !== 'cancelled')) || ($isEdit && $canUpdateItemStatuses && $sale->status !== 'cancelled'))
        <div class="flex items-center justify-end gap-3 border-t border-[#EADFCE] pt-6 dark:border-[#563D35]">
            @if ($canManageSale)
                <flux:button variant="outline" type="button" icon="document-text" wire:click="saveDraft">
                    {{ __('Simpan sebagai draft') }}
                </flux:button>
            @endif
            @if ($isEdit && $canManageSale && in_array($sale->status, ['draft', 'new', 'not_started', 'in_progress'], true))
                <flux:modal.trigger name="confirm-sale-cancellation">
                    <flux:button variant="danger" type="button" icon="x-circle">
                        {{ __('Batalkan transaksi') }}
                    </flux:button>
                </flux:modal.trigger>
            @endif
            <flux:button variant="ghost" type="button" :href="route('sales.index')" wire:navigate>{{ __('Batal') }}</flux:button>
            <flux:button variant="primary" type="submit">{{ $isEdit && !$canManageSale ? __('Perbarui status barang') : ($isEdit ? __('Simpan perubahan') : __('Simpan transaksi')) }}</flux:button>
        </div>
    @endif

    @if ($isEdit && $canManageSale && in_array($sale->status, ['draft', 'new', 'not_started', 'in_progress'], true))
        <flux:modal name="confirm-sale-cancellation" focusable class="max-w-lg">
            <div class="space-y-6">
                <div class="space-y-2">
                    <flux:heading size="lg">{{ __('Batalkan transaksi?') }}</flux:heading>
                    <flux:subheading>{{ __('Transaksi akan ditandai dibatalkan dan tidak dihapus.') }}</flux:subheading>
                </div>
                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="filled">{{ __('Kembali') }}</flux:button>
                    </flux:modal.close>
                    <flux:button variant="danger" wire:click="cancelSale">{{ __('Ya, batalkan') }}</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</form>
