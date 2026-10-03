<?php

namespace App\Livewire\Barang;

use App\Models\Barang;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Ubah barang')]
class Edit extends Component
{
    #[Locked]
    public int $barangId;

    #[Locked]
    public string $kodeBarang = '';

    public string $namaBarang = '';

    public string $hargaBarang = '';

    public function mount(Barang $barang): void
    {
        abort_unless(auth()->user()->can('products.manage'), 403);

        $this->barangId = $barang->id;
        $this->kodeBarang = $barang->kode_barang;
        $this->namaBarang = $barang->nama_barang;
        $this->hargaBarang = (string) $barang->harga_barang;
    }

    public function update(): void
    {
        abort_unless(auth()->user()->can('products.manage'), 403);

        $validated = $this->validate([
            'namaBarang' => ['required', 'string', 'max:255', Rule::unique('barang', 'nama_barang')->ignore($this->barangId)],
            'hargaBarang' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
        ], [
            'namaBarang.unique' => __('Nama barang sudah digunakan.'),
        ]);

        DB::transaction(function () use ($validated): void {
            $barang = Barang::query()->findOrFail($this->barangId);
            $hargaSebelumnya = number_format((float) $barang->harga_barang, 2, '.', '');
            $hargaBaru = number_format((float) $validated['hargaBarang'], 2, '.', '');

            $barang->update([
                'nama_barang' => $validated['namaBarang'],
                'harga_barang' => $hargaBaru,
            ]);

            if ($hargaSebelumnya !== $hargaBaru) {
                $barang->hargaHistory()->create(['harga_barang' => $hargaBaru]);
            }
        });

        Flux::toast(variant: 'success', text: __('Barang berhasil diperbarui.'));
        $this->redirect(route('barang.index'), navigate: true);
    }

    public function render(): View
    {
        $barang = Barang::query()
            ->with(['hargaHistory' => fn ($query) => $query->latest('created_at')->latest('id')])
            ->findOrFail($this->barangId);
        $timezone = auth()->user()->timezone ?? 'Asia/Jakarta';

        return view('livewire.barang.edit', [
            'barang' => $barang,
            'timezone' => $timezone,
        ]);
    }
}
