<?php

namespace App\Livewire\Customer;

use App\Models\Sale;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Detail transaksi')]
class TransactionDetails extends Component
{
    public int $saleId;

    public function mount(Sale $sale): void
    {
        abort_unless(auth()->user()->hasRole('customer'), 403);
        abort_unless($sale->customer_user_id === auth()->id(), 404);

        $this->saleId = $sale->id;
    }

    public function render(): View
    {
        $sale = Sale::query()
            ->with('items')
            ->where('customer_user_id', auth()->id())
            ->findOrFail($this->saleId);

        return view('livewire.customer.transaction-details', [
            'sale' => $sale,
            'total' => $sale->items->sum(fn ($item): float => (float) $item->quantity * (float) $item->unit_price),
            'timezone' => auth()->user()->timezone ?? 'Asia/Jakarta',
        ]);
    }
}
