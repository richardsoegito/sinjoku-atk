<?php

namespace App\Livewire\Sales;

use App\Models\Sale;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Buat transaksi')]
class Create extends Component
{
    use SaleForm;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('sales.create'), 403);

        $this->canManageSale = true;
        $this->canManageDeliveryProof = auth()->user()->can('sales.delivery-proof.manage');
        $this->invoiceNumber = $this->invoiceNumber();
        $this->saleDate = now()->toDateString();
        $this->items = [$this->emptyItem()];
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('sales.create'), 403);

        $this->persistSale(new Sale);

        Flux::toast(variant: 'success', text: __('Transaksi penjualan berhasil dibuat.'));
        $this->redirect(route('sales.index'), navigate: true);
    }

    public function saveDraft(): void
    {
        abort_unless(auth()->user()->can('sales.create'), 403);

        $this->persistSale(new Sale, isDraft: true);

        Flux::toast(variant: 'success', text: __('Transaksi disimpan sebagai draf.'));
        $this->redirect(route('sales.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.sales.create', [
            'statuses' => self::statuses(),
            'isEdit' => false,
            ...$this->referenceData(),
        ]);
    }
}
