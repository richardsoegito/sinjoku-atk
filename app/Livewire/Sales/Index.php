<?php

namespace App\Livewire\Sales;

use App\Models\Sale;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Penjualan')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public ?int $saleToDeleteId = null;

    public string $saleToDeleteInvoice = '';

    public string $trackingUrl = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('sales.view'), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function prepareDelete(int $saleId): void
    {
        abort_unless(auth()->user()->can('sales.delete'), 403);
        $sale = $this->salesQuery()->findOrFail($saleId);

        $this->saleToDeleteId = $sale->id;
        $this->saleToDeleteInvoice = $sale->invoice_number;

        Flux::modal('confirm-sale-deletion')->show();
    }

    public function delete(int $saleId): void
    {
        abort_unless(auth()->user()->can('sales.delete'), 403);
        $this->salesQuery()->findOrFail($saleId)->delete();

        $this->saleToDeleteId = null;
        $this->saleToDeleteInvoice = '';

        Flux::modal('confirm-sale-deletion')->close();
        Flux::toast(variant: 'success', text: __('Transaksi penjualan berhasil dihapus.'));
    }

    public function generateTrackingLink(int $saleId): void
    {
        abort_unless(auth()->user()->can('sales.tracking-link.generate'), 403);
        $sale = $this->salesQuery()->findOrFail($saleId);

        $token = $sale->generateTrackingToken();
        $this->trackingUrl = route('sales.track', $token);

        Flux::modal('sale-tracking-link')->show();
    }

    public function render()
    {
        $sales = $this->salesQuery()
            ->with('items')
            ->when($this->search !== '', function ($query) {
                $query->where(function ($query) {
                    $query->where('store_name', 'like', '%'.$this->search.'%')
                        ->orWhere('invoice_number', 'like', '%'.$this->search.'%')
                        ->orWhere('manual_invoice_number', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->status !== '', function ($query) {
                $statuses = match ($this->status) {
                    'new' => ['new', 'not_started'],
                    'active' => ['new', 'not_started', 'in_progress'],
                    default => [$this->status],
                };

                $query->whereIn('status', $statuses);
            })
            ->latest('sale_date')
            ->latest('id')
            ->paginate(10);

        return view('livewire.sales.index', [
            'sales' => $sales,
            'statuses' => SaleForm::statuses(),
        ]);
    }

    private function salesQuery(): Builder
    {
        $query = Sale::query();

        if (! auth()->user()->can('sales.manage-all') && ! auth()->user()->can('sales.view-all')) {
            $query->where('user_id', auth()->id());
        }

        return $query;
    }
}
