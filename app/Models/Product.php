<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Loggable;

class Product extends Model
{
    use SoftDeletes, Loggable;

    protected $fillable = [
        'category', 'type', 'name', 'description', 'price', 'tax_rate', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function invoiceItems() { return $this->hasMany(InvoiceItem::class); }
    public function stockItems()   { return $this->hasMany(StockItem::class); }

    public function isUsedInDefinitiveInvoice(): bool
    {
        return $this->invoiceItems()
            ->whereHas('invoice', fn ($q) => $q->where('type', 'definitive'))
            ->exists();
    }

    public function isRenouvelable(): bool { return $this->type === 'renouvelable'; }
    public function isEquipement(): bool   { return $this->category === 'produit'; }
}
