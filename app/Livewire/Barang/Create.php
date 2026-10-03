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

#[Title('Tambah barang')]
class Create extends Component
{
    #[Locked]
    public string $kodeBarang = '';

    public string $namaBarang = '';

    public string $hargaBarang = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('products.manage'), 403);

        $this->kodeBarang = $this->nextKodeBarang();
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('products.manage'), 403);

        $validated = $this->validate([
            'kodeBarang' => ['required', 'string', 'max:255', Rule::unique('barang', 'kode_barang')],
            'namaBarang' => ['required', 'string', 'max:255', Rule::unique('barang', 'nama_barang')],
            'hargaBarang' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
        ], [
            'namaBarang.unique' => __('Nama barang sudah digunakan.'),
        ]);

        DB::transaction(function () use ($validated): void {
            $barang = Barang::query()->create([
                'kode_barang' => $validated['kodeBarang'],
                'nama_barang' => $validated['namaBarang'],
                'harga_barang' => number_format((float) $validated['hargaBarang'], 2, '.', ''),
            ]);
            $barang->hargaHistory()->create(['harga_barang' => $barang->harga_barang]);
        });

        Flux::toast(variant: 'success', text: __('Barang berhasil ditambahkan.'));
        $this->redirect(route('barang.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.barang.create');
    }

    private function nextKodeBarang(): string
    {
        $nomorBerikutnya = Barang::query()
            ->where('kode_barang', 'like', 'BRG%')
            ->pluck('kode_barang')
            ->reduce(function (int $nomorTertinggi, string $kodeBarang): int {
                if (preg_match('/^BRG(\d+)$/', $kodeBarang, $matches) !== 1) {
                    return $nomorTertinggi;
                }

                return max($nomorTertinggi, (int) $matches[1]);
            }, 0) + 1;

        return 'BRG'.str_pad((string) $nomorBerikutnya, 4, '0', STR_PAD_LEFT);
    }
}
