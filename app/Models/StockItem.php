<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockItem extends Model
{
    protected $fillable = ['product_id', 'warehouse_id', 'quantity', 'min_quantity'];

    public function product()   { return $this->belongsTo(Product::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }

    public function isLow(): bool { return $this->quantity <= $this->min_quantity; }
}
