<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Loggable;

class Site extends Model
{
    use Loggable;

    protected $fillable = [
        'client_id', 'name', 'adresse', 'ville',
        'description', 'latitude', 'longitude',
        'contact_nom', 'contact_tel', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'latitude'  => 'float',
        'longitude' => 'float',
    ];

    public function client()         { return $this->belongsTo(Client::class); }
    public function invoiceItems()   { return $this->hasMany(InvoiceItem::class); }
    public function stockMovements() { return $this->hasMany(StockMovement::class); }
}