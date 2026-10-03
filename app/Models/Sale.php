<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $customer_user_id
 * @property string $store_name
 * @property string $invoice_number
 * @property string|null $manual_invoice_number
 * @property Carbon $sale_date
 * @property string|null $description
 * @property string|null $delivery_address
 * @property string $status
 * @property string|null $delivery_proof_path
 */
#[Fillable(['user_id', 'customer_user_id', 'store_name', 'invoice_number', 'manual_invoice_number', 'sale_date', 'description', 'delivery_address', 'status', 'delivery_proof_path'])]
class Sale extends Model
{
    public function generateTrackingToken(): string
    {
        $token = Str::random(64);

        $this->forceFill(['tracking_token_hash' => hash('sha256', $token)])->save();

        return $token;
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_user_id');
    }

    /** @return HasMany<SaleItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    protected function casts(): array
    {
        return [
            'sale_date' => 'date',
        ];
    }
}
