<?php

namespace App\Livewire\Sales;

use App\Models\Sale;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Ubah transaksi')]
class Edit extends Component
{
    use SaleForm;

    public Sale $sale;

    public string $timezoneLabel = 'WIB';

    #[Locked]
    public bool $canManageSale = false;

    #[Locked]
    public bool $canUpdateItemStatuses = false;

    #[Locked]
    public bool $canManageDeliveryProof = false;

    public function mount(Sale $sale): void
    {
        $user = auth()->user();
        $this->canManageSale = $user->can('sales.update');
        $this->canUpdateItemStatuses = $user->can('sales.item-status.update');
        $this->canManageDeliveryProof = $user->can('sales.delivery-proof.manage');

        abort_unless(
            $user->can('sales.manage-all') || $user->can('sales.view-all') || ($sale->user_id === $user->id && ($user->can('sales.view') || $this->canUpdateItemStatuses || $this->canManageDeliveryProof)),
            403,
        );

        $this->sale = $sale->load('items');
        $this->storeName = $sale->customer?->name ?? $sale->store_name;
        $this->customerUserId = (string) ($sale->customer_user_id ?? '');
        $this->invoiceNumber = $sale->invoice_number;
        $this->manualInvoiceNumber = $sale->manual_invoice_number ?? '';
        $this->saleDate = $sale->sale_date->toDateString();
        $this->description = $sale->description ?? '';
        $this->deliveryAddress = $sale->delivery_address ?? '';
        $this->status = $sale->status === 'not_started' ? 'new' : $sale->status;
        $this->deliveryProof = null;
        $timezone = auth()->user()->timezone ?? 'Asia/Jakarta';
        $this->timezoneLabel = match ($timezone) {
            'Asia/Makassar' => 'WITA',
            'Asia/Jayapura' => 'WIT',
            default => 'WIB',
        };
        $this->items = $sale->items->map(fn ($item) => [
            'id' => $item->id,
            'product_id' => (string) ($item->product_id ?? ''),
            'product_name' => $item->product_name,
            'quantity' => $item->quantity,
            'unit' => $item->unit,
            'unit_price' => (float) $item->unit_price,
            'status' => $item->status,
            'transport_name' => $item->transport_name ?? '',
            'vehicle_number' => $item->vehicle_number ?? '',
            'loaded_at' => $item->loaded_at?->copy()->timezone($timezone)->format('d/m/Y H:i'),
            'shipped_at' => $item->shipped_at?->copy()->timezone($timezone)->format('d/m/Y H:i'),
        ])->all();
    }

    public function update(): void
    {
        abort_unless(auth()->user()->can('sales.update'), 403);
        abort_unless($this->sale->status !== 'cancelled', 403);

        $this->persistSale($this->sale, isUpdate: true);

        Flux::toast(variant: 'success', text: __('Transaksi penjualan berhasil diperbarui.'));
        $this->redirect(route('sales.index'), navigate: true);
    }

    public function saveDraft(): void
    {
        abort_unless(auth()->user()->can('sales.update'), 403);
        abort_unless(in_array($this->sale->status, ['draft', 'new', 'not_started', 'in_progress'], true), 403);

        $this->persistSale($this->sale, isUpdate: true, isDraft: true);

        Flux::toast(variant: 'success', text: __('Transaksi disimpan sebagai draf.'));
        $this->redirect(route('sales.index'), navigate: true);
    }

    public function cancelSale(): void
    {
        abort_unless(auth()->user()->can('sales.update'), 403);

        $sale = Sale::query()
            ->when(! auth()->user()->can('sales.manage-all'), fn ($query) => $query->where('user_id', auth()->id()))
            ->findOrFail($this->sale->id);

        abort_unless(in_array($sale->status, ['draft', 'new', 'not_started', 'in_progress'], true), 403);

        $sale->update(['status' => 'cancelled']);
        Flux::modal('confirm-sale-cancellation')->close();
        Flux::toast(variant: 'success', text: __('Transaksi berhasil dibatalkan.'));
        $this->redirect(route('sales.index'), navigate: true);
    }

    public function updateItemStatuses(): void
    {
        abort_unless(auth()->user()->can('sales.item-status.update'), 403);
        abort_unless($this->sale->status !== 'cancelled', 403);

        $validated = $this->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer'],
            'items.*.status' => ['required', 'string', 'in:new,loaded,delivered'],
        ]);
        $sale = Sale::query()
            ->when(! auth()->user()->can('sales.view-all') && ! auth()->user()->can('sales.manage-all'), fn ($query) => $query->where('user_id', auth()->id()))
            ->with('items')
            ->findOrFail($this->sale->id);

        DB::transaction(function () use ($sale, $validated): void {
            foreach ($validated['items'] as $data) {
                $item = $sale->items->firstWhere('id', (int) $data['id']);
                abort_unless($item, 404);

                if ($item->status === $data['status']) {
                    continue;
                }

                $item->status = $data['status'];
                if ($item->status === 'new') {
                    $item->loaded_at = null;
                    $item->shipped_at = null;
                } elseif ($item->status === 'loaded') {
                    $item->loaded_at ??= now();
                    $item->shipped_at = null;
                } else {
                    $item->loaded_at ??= now();
                    $item->shipped_at = now();
                }
                $item->save();
            }

            $this->sale = $sale->refresh()->load('items');
            $this->sale->status = $this->transactionStatus();
            $this->sale->save();
        });

        Flux::toast(variant: 'success', text: __('Status barang berhasil diperbarui.'));
        $this->redirect(route('sales.index'), navigate: true);
    }

    public function deleteDeliveryProof(): void
    {
        abort_unless(auth()->user()->can('sales.delivery-proof.manage'), 403);

        $sale = Sale::query()
            ->when(! auth()->user()->can('sales.view-all') && ! auth()->user()->can('sales.manage-all'), fn ($query) => $query->where('user_id', auth()->id()))
            ->findOrFail($this->sale->id);
        $path = $sale->delivery_proof_path;

        abort_unless($path, 404);

        DB::transaction(function () use ($sale): void {
            $sale->update(['delivery_proof_path' => null]);
        });

        Storage::disk('public')->delete($path);
        $this->sale = $sale->refresh();

        Flux::modal('confirm-delivery-proof-deletion')->close();
        Flux::toast(variant: 'success', text: __('Bukti pengiriman berhasil dihapus.'));
    }

    public function render()
    {
        return view('livewire.sales.edit', [
            'statuses' => self::statuses(),
            'isEdit' => true,
            ...$this->referenceData(),
        ]);
    }
}
