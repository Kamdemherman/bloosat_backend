<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Loggable;

class Client extends Model
{
    use SoftDeletes, Loggable;

    protected $fillable = [
        'commercial_id', 'type', 'status', 'nom', 'prenom', 'raison_sociale',
        'nature', 'email', 'telephone', 'adresse', 'ville', 'pays',
        'ninea', 'rccm', 'commercial_email', 'is_suspended', 'notes',
    ];

    protected $casts = [
        'is_suspended' => 'boolean',
    ];

    public function commercial()    { return $this->belongsTo(User::class, 'commercial_id'); }
    public function sites()         { return $this->hasMany(Site::class); }
    public function invoices()      { return $this->hasMany(Invoice::class); }
    public function subscriptions() { return $this->hasMany(Subscription::class); }
    public function encaissements() { return $this->hasMany(Encaissement::class); }
    public function priceOverrides(){ return $this->hasMany(ClientPriceOverride::class); }

    public function scopeClients($q)       { return $q->where('status', 'client'); }
    public function scopeProspects($q)     { return $q->where('status', 'prospect'); }
    public function scopeGrandsComptes($q) { return $q->where('type', 'grand_compte'); }
    public function scopeOrdinaires($q)    { return $q->where('type', 'ordinaire'); }

    public function getDisplayNameAttribute(): string
    {
        return $this->nature === 'morale'
            ? ($this->raison_sociale ?? $this->nom)
            : trim("{$this->prenom} {$this->nom}");
    }

    public function isGrandCompte(): bool { return $this->type === 'grand_compte'; }
    public function isClient(): bool      { return $this->status === 'client'; }
    public function isProspect(): bool    { return $this->status === 'prospect'; }

    public function promoteToClient(): void
    {
        $this->update(['status' => 'client']);
    }

    public function suspend(): void
    {
        $this->update(['is_suspended' => true]);
        dispatch(new \App\Jobs\SuspendClientApiJob($this));
    }

    public function unsuspend(): void
    {
        $this->update(['is_suspended' => false]);
    }

    public function priceFor(Product $product): float
    {
        $override = $this->priceOverrides()->where('product_id', $product->id)->first();
        return $override ? (float) $override->custom_price : (float) $product->price;
    }
}
