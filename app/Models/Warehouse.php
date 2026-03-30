<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $fillable = ['name', 'location', 'is_active'];
    protected $casts    = ['is_active' => 'boolean'];

    public function stockItems()    { return $this->hasMany(StockItem::class); }
    public function movementsFrom() { return $this->hasMany(StockMovement::class, 'from_warehouse_id'); }
    public function movementsTo()   { return $this->hasMany(StockMovement::class, 'to_warehouse_id'); }
}
