<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['kode_barang', 'harga_barang'])]
class HargaBarang extends Model
{
    protected $table = 'harga_barang';

    /** @return BelongsTo<Barang, $this> */
    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'kode_barang', 'kode_barang');
    }

    protected function casts(): array
    {
        return [
            'harga_barang' => 'decimal:2',
        ];
    }
}
