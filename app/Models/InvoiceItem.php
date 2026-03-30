<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id', 'product_id', 'site_id', 'description',
        'quantity', 'unit_price', 'tax_rate', 'discount', 'subtotal',
    ];

    protected static function booted(): void
    {
        static::saving(function (InvoiceItem $item) {
            $base        = $item->quantity * $item->unit_price;
            $item->subtotal = $base - ($base * $item->discount / 100);
        });

        $recalc = fn ($item) => $item->invoice->recalculate();
        static::saved($recalc);
        static::deleted($recalc);
    }

    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function product() { return $this->belongsTo(Product::class); }
    public function site()    { return $this->belongsTo(Site::class); }
}
