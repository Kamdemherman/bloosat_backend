<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Loggable;

class StockMovement extends Model
{
    use Loggable;

    protected $fillable = [
        'product_id', 'from_warehouse_id', 'to_warehouse_id', 'created_by',
        'site_id', 'type', 'quantity', 'reason', 'movement_date',
    ];

    protected $casts = ['movement_date' => 'date'];

    protected static function booted(): void
    {
        static::created(function (StockMovement $m) {
            $m->updateStockLevels();
        });
    }

    public function product()       { return $this->belongsTo(Product::class); }
    public function fromWarehouse() { return $this->belongsTo(Warehouse::class, 'from_warehouse_id'); }
    public function toWarehouse()   { return $this->belongsTo(Warehouse::class, 'to_warehouse_id'); }
    public function creator()       { return $this->belongsTo(User::class, 'created_by'); }
    public function site()          { return $this->belongsTo(Site::class); }

    private function updateStockLevels(): void
    {
        switch ($this->type) {
            case 'entree':
            case 'retour':
                StockItem::firstOrCreate(
                    ['product_id' => $this->product_id, 'warehouse_id' => $this->to_warehouse_id]
                )->increment('quantity', $this->quantity);
                break;

            case 'sortie':
            case 'installation':
                StockItem::where([
                    'product_id'   => $this->product_id,
                    'warehouse_id' => $this->from_warehouse_id,
                ])->decrement('quantity', $this->quantity);
                break;

            case 'transfert':
                StockItem::where([
                    'product_id'   => $this->product_id,
                    'warehouse_id' => $this->from_warehouse_id,
                ])->decrement('quantity', $this->quantity);

                StockItem::firstOrCreate(
                    ['product_id' => $this->product_id, 'warehouse_id' => $this->to_warehouse_id]
                )->increment('quantity', $this->quantity);
                break;
        }
    }

    public function reverseStockLevels(): void
    {
        switch ($this->type) {
            case 'entree':
            case 'retour':
                StockItem::where([
                    'product_id'   => $this->product_id,
                    'warehouse_id' => $this->to_warehouse_id,
                ])->increment('quantity', -$this->quantity);
                break;

            case 'sortie':
            case 'installation':
                StockItem::firstOrCreate(
                    ['product_id' => $this->product_id, 'warehouse_id' => $this->from_warehouse_id]
                )->increment('quantity', $this->quantity);
                break;

            case 'transfert':
                StockItem::firstOrCreate(
                    ['product_id' => $this->product_id, 'warehouse_id' => $this->from_warehouse_id]
                )->increment('quantity', $this->quantity);

                StockItem::where([
                    'product_id'   => $this->product_id,
                    'warehouse_id' => $this->to_warehouse_id,
                ])->increment('quantity', -$this->quantity);
                break;
        }
    }
}

