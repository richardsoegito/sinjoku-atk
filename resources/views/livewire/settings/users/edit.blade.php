<section>
    <div class="mb-6">
        <flux:heading size="xl">Ubah pengguna</flux:heading>
        <flux:subheading>Perbarui informasi akun, alamat customer, dan perannya.</flux:subheading>
    </div>

    @include('livewire.settings.users.form', ['isEdit' => true])
</section>