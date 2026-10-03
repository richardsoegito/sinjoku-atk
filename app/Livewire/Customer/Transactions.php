<?php

namespace App\Livewire\Customer;

use App\Models\Sale;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Transaksi Customer')]
class Transactions extends Component
{
    use WithPagination;

    public bool $historyOnly = false;

    public function mount(string $type): void
    {
        abort_unless(auth()->user()->hasRole('customer'), 403);
        abort_unless(in_array($type, ['active', 'history'], true), 404);

        $this->historyOnly = $type === 'history';
    }

    public function render(): View
    {
        $sales = Sale::query()
            ->where('customer_user_id', auth()->id())
            ->when(
                $this->historyOnly,
                fn ($query) => $query->whereIn('status', ['completed', 'cancelled']),
                fn ($query) => $query->whereIn('status', ['new', 'not_started', 'in_progress']),
            )
            ->with('items')
            ->latest('sale_date')
            ->latest('id')
            ->paginate(10);

        return view('livewire.customer.transactions', [
            'sales' => $sales,
            'title' => $this->historyOnly ? __('History transaksi') : __('Transaksi'),
            'emptyMessage' => $this->historyOnly
                ? __('Belum ada transaksi selesai atau dibatalkan.')
                : __('Tidak ada transaksi baru atau yang sedang diproses.'),
        ]);
    }
}
