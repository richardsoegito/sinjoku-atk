<?php

namespace App\Livewire\Sales;

use App\Models\Barang;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\WithFileUploads;

trait SaleForm
{
    use WithFileUploads;

    public string $storeName = '';

    public string $customerUserId = '';

    public string $invoiceNumber = '';

    public string $manualInvoiceNumber = '';

    public string $saleDate = '';

    public string $description = '';

    public string $deliveryAddress = '';

    public string $status = 'new';

    public $deliveryProof;

    public array $items = [];

    public bool $canManageSale = false;

    public bool $canUpdateItemStatuses = false;

    public bool $canManageDeliveryProof = false;

    public static function statuses(): array
    {
        return [
            'draft' => 'Draf',
            'new' => 'Baru',
            'in_progress' => 'Dalam proses',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
        ];
    }

    /** @return array{customers: Collection<int, User>, products: Collection<int, Barang>} */
    protected function referenceData(): array
    {
        return [
            'customers' => User::query()
                ->whereHas('roles', fn ($query) => $query->where('name', 'customer'))
                ->whereHas('customer')
                ->with('customer')
                ->orderBy('name')
                ->get(),
            'products' => Barang::query()->orderBy('nama_barang')->get(),
        ];
    }

    public function updatedCustomerUserId(string $value): void
    {
        if (! filled($value)) {
            return;
        }

        $customer = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'customer'))
            ->whereHas('customer')
            ->with('customer')
            ->find($value);

        if ($customer?->customer) {
            $this->deliveryAddress = $customer->customer->alamat;
        }
    }

    public function updatedItems(mixed $value, ?string $key = null): void
    {
        if ($key === null) {
            return;
        }

        [$index, $field] = array_pad(explode('.', $key, 2), 2, null);

        if ($field !== 'product_id' || ! isset($this->items[$index])) {
            return;
        }

        $barang = Barang::query()->find($value);

        if ($barang) {
            $this->items[$index]['product_name'] = $barang->nama_barang;
            $this->items[$index]['unit_price'] = (float) $barang->harga_barang;
        }
    }

    protected function saleRules(bool $isUpdate = false, bool $isDraft = false): array
    {
        $canKeepLegacyCustomer = $isUpdate && ! filled($this->customerUserId) && ! $this->sale->customer_user_id;
        $rules = [
            'storeName' => ['nullable', 'string', 'max:255'],
            'customerUserId' => [$isDraft || $canKeepLegacyCustomer ? 'nullable' : 'required', 'integer', Rule::exists('customer', 'user_id')],
            'manualInvoiceNumber' => ['nullable', 'string', 'max:255'],
            'saleDate' => [$isDraft ? 'nullable' : 'required', 'date'],
            'description' => ['nullable', 'string', 'max:5000'],
            'deliveryAddress' => ['nullable', 'string', 'max:1000'],
            'items' => $isDraft ? ['nullable', 'array'] : ['required', 'array', 'min:1'],
            'items.*.status' => ['nullable', 'string', 'in:new,loaded,delivered'],
            'items.*.transport_name' => ['nullable', 'string', 'max:255'],
            'items.*.vehicle_number' => ['nullable', 'string', 'max:50'],
            'deliveryProof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
        ];

        foreach ($this->items as $index => $item) {
            $hasProduct = filled($item['product_id'] ?? null);
            $legacyUnlinkedItem = $isUpdate
                && isset($item['id'])
                && ! $hasProduct
                && SaleItem::query()
                    ->where('sale_id', $this->sale->id)
                    ->where('id', $item['id'])
                    ->whereNull('product_id')
                    ->exists();

            $rules["items.{$index}.product_id"] = [
                $isDraft || $legacyUnlinkedItem ? 'nullable' : 'required',
                'integer',
                Rule::exists('barang', 'id'),
            ];

            $fieldRule = $hasProduct ? 'required' : ($isDraft || $legacyUnlinkedItem ? 'nullable' : 'required');
            $rules["items.{$index}.quantity"] = [$fieldRule, 'integer', 'min:1'];
            $rules["items.{$index}.unit"] = [$fieldRule, 'string', 'max:50'];
            $rules["items.{$index}.unit_price"] = [$fieldRule, 'numeric', 'min:0'];
        }

        return $rules;
    }

    public function itemSubtotal(array $item): float
    {
        $quantity = (float) ($item['quantity'] ?? 0);
        $unitPrice = (float) ($item['unit_price'] ?? 0);

        return round($quantity * $unitPrice, 2);
    }

    public function grandTotal(): float
    {
        return round(array_reduce($this->items, fn (float $total, array $item): float => $total + $this->itemSubtotal($item), 0.0), 2);
    }

    public function deliverySummary(): array
    {
        $total = array_sum(array_map(fn (array $item): int => (int) ($item['quantity'] ?? 0), $this->items));
        $counts = [
            'new' => 0,
            'loaded' => 0,
            'delivered' => 0,
        ];

        foreach ($this->items as $item) {
            $status = $item['status'] ?? 'new';
            $counts[$status] = ($counts[$status] ?? 0) + (int) ($item['quantity'] ?? 0);
        }

        return [
            'total' => $total,
            'new' => $counts['new'],
            'loaded' => $counts['loaded'],
            'delivered' => $counts['delivered'],
            'new_percent' => $total > 0 ? round(($counts['new'] / $total) * 100) : 0,
            'loaded_percent' => $total > 0 ? round(($counts['loaded'] / $total) * 100) : 0,
            'delivered_percent' => $total > 0 ? round(($counts['delivered'] / $total) * 100) : 0,
        ];
    }

    public function addItem(): void
    {
        $this->items[] = $this->emptyItem();
    }

    public function removeItem(int $index): void
    {
        if (count($this->items) === 1) {
            return;
        }

        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    protected function emptyItem(): array
    {
        return [
            'product_name' => '',
            'product_id' => '',
            'quantity' => 1,
            'unit' => 'pcs',
            'unit_price' => 0,
            'status' => 'new',
            'transport_name' => '',
            'vehicle_number' => '',
            'loaded_at' => null,
            'shipped_at' => null,
        ];
    }

    protected function invoiceNumber(): string
    {
        do {
            $invoice = 'INV-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
        } while (Sale::query()->where('invoice_number', $invoice)->exists());

        return $invoice;
    }

    protected function saveItems(Sale $sale, bool $isDraft = false): void
    {
        $existingItems = $sale->items()->get()->keyBy('id');
        $savedIds = [];

        foreach ($this->items as $item) {
            $itemModel = isset($item['id']) ? $existingItems->get($item['id']) : null;
            $hasProduct = filled($item['product_id'] ?? null);

            if ($isDraft && ! $hasProduct && ! $itemModel) {
                continue;
            }

            $itemStatus = $item['status'] ?? 'new';
            $transportIsActive = in_array($itemStatus, ['loaded', 'delivered'], true);
            $barang = filled($item['product_id'] ?? null)
                ? Barang::query()->findOrFail($item['product_id'])
                : null;
            $attributes = [
                'product_id' => $barang?->id,
                'product_name' => $barang?->nama_barang ?? ($item['product_name'] ?? ''),
                'quantity' => $item['quantity'],
                'unit' => $item['unit'],
                'unit_price' => $item['unit_price'],
                'status' => $itemStatus,
                'transport_name' => $transportIsActive ? ($item['transport_name'] ?? null) : null,
                'vehicle_number' => $transportIsActive ? ($item['vehicle_number'] ?? null) : null,
            ];

            if ($itemModel) {
                $previousStatus = $itemModel->status;
                $itemModel->fill($attributes);
                if ($itemModel->status === 'new') {
                    $itemModel->loaded_at = null;
                    $itemModel->shipped_at = null;
                } elseif ($itemModel->status === 'loaded' && ($previousStatus !== 'loaded' || ! $itemModel->loaded_at)) {
                    $itemModel->loaded_at = now();
                    $itemModel->shipped_at = null;
                }
                if ($itemModel->status === 'delivered' && ($previousStatus !== 'delivered' || ! $itemModel->shipped_at)) {
                    $itemModel->loaded_at ??= now();
                    $itemModel->shipped_at = now();
                }
                $itemModel->save();
                $savedIds[] = $itemModel->id;
            } else {
                $newItem = $sale->items()->create($attributes);
                if ($newItem->status === 'loaded') {
                    $newItem->update(['loaded_at' => now()]);
                } elseif ($newItem->status === 'delivered') {
                    $newItem->update(['loaded_at' => now(), 'shipped_at' => now()]);
                }
                $savedIds[] = $newItem->id;
            }
        }

        if ($savedIds === []) {
            $sale->items()->delete();
        } else {
            $sale->items()->whereNotIn('id', $savedIds)->delete();
        }
    }

    protected function transactionStatus(): string
    {
        if (collect($this->items)->every(fn (array $item): bool => ! array_key_exists('status', $item))) {
            return array_key_exists($this->status, self::statuses()) ? $this->status : 'new';
        }

        $totalQuantity = array_sum(array_map(fn (array $item): int => (int) ($item['quantity'] ?? 0), $this->items));
        $processedQuantity = array_sum(array_map(fn (array $item): int => ($item['status'] ?? 'new') !== 'new' ? (int) ($item['quantity'] ?? 0) : 0, $this->items));
        $deliveredQuantity = array_sum(array_map(fn (array $item): int => ($item['status'] ?? 'new') === 'delivered' ? (int) ($item['quantity'] ?? 0) : 0, $this->items));

        if ($processedQuantity === 0) {
            return 'new';
        }

        return $deliveredQuantity === $totalQuantity ? 'completed' : 'in_progress';
    }

    protected function persistSale(Sale $sale, bool $isUpdate = false, bool $isDraft = false): void
    {
        abort_unless(auth()->user()->can($isUpdate ? 'sales.update' : 'sales.create'), 403);

        $this->validateUniqueProducts();
        $validated = $this->validate($this->saleRules($isUpdate, $isDraft));

        if ($this->deliveryProof) {
            abort_unless(auth()->user()->can('sales.delivery-proof.manage'), 403);
        }

        $customer = filled($validated['customerUserId'] ?? null)
            ? User::query()
                ->whereHas('roles', fn ($query) => $query->where('name', 'customer'))
                ->whereHas('customer')
                ->findOrFail($validated['customerUserId'])
            : null;

        DB::transaction(function () use ($sale, $validated, $isUpdate, $isDraft, $customer): void {
            $sale->fill([
                'customer_user_id' => $customer?->id,
                'store_name' => $customer?->name ?? (filled($validated['storeName'] ?? null) ? $validated['storeName'] : ($sale->store_name ?? '')),
                'manual_invoice_number' => filled($validated['manualInvoiceNumber']) ? $validated['manualInvoiceNumber'] : null,
                'sale_date' => filled($validated['saleDate'] ?? null) ? $validated['saleDate'] : ($sale->sale_date ?? now()->toDateString()),
                'description' => $validated['description'] ?: null,
                'delivery_address' => $validated['deliveryAddress'] ?: null,
                'status' => $isDraft ? 'draft' : $this->transactionStatus(),
            ]);

            if (! $isUpdate) {
                $sale->user_id = auth()->id();
                $sale->invoice_number = $this->invoiceNumber;
            }

            $sale->save();

            if ($isUpdate) {
                $sale->items()->delete();
            }

            $this->saveItems($sale, $isDraft);

            if ($this->deliveryProof && ! $sale->delivery_proof_path) {
                $sale->delivery_proof_path = $this->deliveryProof->store('sales/'.$sale->id, 'public');
                $sale->save();
            }
        });
    }

    private function validateUniqueProducts(): void
    {
        $seenProductIds = [];

        foreach ($this->items as $index => $item) {
            $productId = $item['product_id'] ?? null;

            if (! filled($productId)) {
                continue;
            }

            $productId = (string) $productId;

            if (isset($seenProductIds[$productId])) {
                throw ValidationException::withMessages([
                    "items.{$index}.product_id" => __('Barang yang sama hanya dapat ditambahkan satu kali.'),
                ]);
            }

            $seenProductIds[$productId] = true;
        }
    }
}
