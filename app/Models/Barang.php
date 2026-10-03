<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['kode_barang', 'nama_barang', 'harga_barang'])]
class Barang extends Model
{
    protected $table = 'barang';

    /** @return HasMany<HargaBarang, $this> */
    public function hargaHistory(): HasMany
    {
        return $this->hasMany(HargaBarang::class, 'kode_barang', 'kode_barang');
    }

    /** @return HasMany<SaleItem, $this> */
    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class, 'product_id');
    }

    protected function casts(): array
    {
        return [
            'harga_barang' => 'decimal:2',
        ];
    }
}
