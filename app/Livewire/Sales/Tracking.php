<?php

namespace App\Livewire\Sales;

use App\Models\Sale;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.public')]
#[Title('Pelacakan Pesanan')]
class Tracking extends Component
{
    public string $token;

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->sale();
    }

    public function render()
    {
        $sale = $this->sale();
        $items = $sale->items;
        $total = $items->sum(fn ($item): float => (float) $item->quantity * (float) $item->unit_price);
        $quantityTotal = $items->sum('quantity');
        $deliveryCounts = $items->groupBy(fn ($item): string => $item->status ?? 'new')
            ->map(fn ($group): int => (int) $group->sum('quantity'));

        return view('livewire.sales.tracking', [
            'sale' => $sale,
            'total' => $total,
            'quantityTotal' => $quantityTotal,
            'deliveryCounts' => [
                'new' => $deliveryCounts->get('new', 0),
                'loaded' => $deliveryCounts->get('loaded', 0),
                'delivered' => $deliveryCounts->get('delivered', 0),
            ],
            'updatedAt' => now()->format('d/m/Y H:i'),
        ]);
    }

    private function sale(): Sale
    {
        return Sale::query()
            ->with('items')
            ->where('tracking_token_hash', hash('sha256', $this->token))
            ->firstOrFail();
    }
}
