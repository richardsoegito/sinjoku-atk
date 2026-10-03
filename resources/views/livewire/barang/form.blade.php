<form wire:submit="{{ $isEdit ? 'update' : 'save' }}" class="space-y-6">
    <div class="grid gap-6 md:grid-cols-2">
        <flux:input wire:model="kodeBarang" :label="__('Kode barang')" readonly />
        <flux:input wire:model="namaBarang" :label="__('Nama barang')" required autofocus />
        <flux:input wire:model="hargaBarang" :label="__('Harga barang')" type="number" min="0" step="0.01" required />
    </div>

    <div class="flex justify-end gap-2 border-t border-[#EADFCE] pt-4 dark:border-[#563D35]">
        <flux:button variant="filled" :href="route('barang.index')" wire:navigate>
            {{ __('Kembali') }}
        </flux:button>
        <flux:button variant="primary" type="submit">
            {{ __('Simpan') }}
        </flux:button>
    </div>
</form>