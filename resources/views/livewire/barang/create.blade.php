<section>
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Tambah barang') }}</flux:heading>
        <flux:subheading>{{ __('Masukkan informasi barang dan harga awal.') }}</flux:subheading>
    </div>

    @include('livewire.barang.form', ['isEdit' => false])
</section>