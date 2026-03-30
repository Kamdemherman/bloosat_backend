<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Redevance extends Model
{
    protected $fillable = [
        'subscription_id', 'invoice_id', 'period_start', 'period_end', 'status',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end'   => 'date',
    ];

    public function subscription() { return $this->belongsTo(Subscription::class); }
    public function invoice()      { return $this->belongsTo(Invoice::class); }
    public function encaissements(){ return $this->hasMany(Encaissement::class); }

    public function isPaid(): bool    { return $this->status === 'payee'; }
    public function isOverdue(): bool { return $this->status === 'non_payee' && $this->period_end->isPast(); }
}
