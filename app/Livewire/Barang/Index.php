<?php

namespace App\Livewire\Barang;

use App\Models\Barang as BarangModel;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Barang')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('products.view') || auth()->user()->can('products.manage'), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $barang = BarangModel::query()
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query->where('kode_barang', 'like', '%'.$this->search.'%')
                        ->orWhere('nama_barang', 'like', '%'.$this->search.'%');
                });
            })
            ->orderBy('nama_barang')
            ->paginate(10);

        return view('livewire.barang.index', [
            'barang' => $barang,
            'canManage' => auth()->user()->can('products.manage'),
        ]);
    }
}
