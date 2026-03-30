<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientPriceOverride extends Model
{
    protected $fillable = ['client_id', 'product_id', 'custom_price'];

    public function client()  { return $this->belongsTo(Client::class); }
    public function product() { return $this->belongsTo(Product::class); }
}
