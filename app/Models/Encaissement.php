<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Loggable;

class Encaissement extends Model
{
    use Loggable;

    protected $fillable = [
        'client_id', 'redevance_id', 'invoice_id', 'created_by',
        'reference', 'amount', 'payment_method', 'payment_date',
        'proof_path', 'notes', 'status', 'is_complete',
    ];

    protected $casts = ['payment_date' => 'date', 'is_complete' => 'boolean'];

    protected static function booted(): void
    {
        static::creating(function (Encaissement $e) {
            $count       = static::count() + 1;
            $e->reference = 'ENC-' . date('Y') . '-' . str_pad($count, 6, '0', STR_PAD_LEFT);
        });

        static::created(function (Encaissement $e) {
            // Load relationships
            $e->load(['invoice', 'redevance.subscription', 'client']);

            $e->invoice->markAsPaid();

            if ($e->redevance && $e->redevance->subscription) {
                $e->redevance->update(['status' => 'payee']);
                $e->client->unsuspend();
                $e->redevance->subscription->renewCycle();
            }
        });
    }

    public function client()    { return $this->belongsTo(Client::class); }
    public function invoice()   { return $this->belongsTo(Invoice::class); }
    public function redevance() { return $this->belongsTo(Redevance::class); }
    public function creator()   { return $this->belongsTo(User::class, 'created_by'); }

    public function isComplete(): bool
    {
        return (bool) $this->is_complete;
    }

    public function getCompletionStatus(): string
    {
        if (!$this->invoice) {
            return 'incomplet';
        }

        $invoiceTotal = (float) $this->invoice->total;
        $encaissedAmount = (float) $this->amount;

        if ($encaissedAmount >= $invoiceTotal) {
            return 'complet';
        }

        return 'incomplet';
    }
}
