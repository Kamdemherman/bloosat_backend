<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Loggable;

class Invoice extends Model
{
    use Loggable;

    protected $fillable = [
        'client_id', 'created_by', 'validated_by', 'number', 'type',
        'status', 'issue_date', 'due_date', 'subtotal', 'tax_amount',
        'discount_amount', 'total', 'notes', 'validated_at', 'is_locked', 'locked_at',
    ];

    protected $casts = [
        'issue_date'   => 'date',
        'due_date'     => 'date',
        'validated_at' => 'datetime',
        'locked_at'    => 'datetime',
        'is_locked'    => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            $prefix = match ($invoice->type) {
                'pro_forma'  => 'PF',
                'definitive' => 'FA',
                'redevance'  => 'RD',
                default      => 'INV',
            };

            $currentYear = date('Y');
            $pattern = "{$prefix}-{$currentYear}-";
            $substrStart = strlen($pattern) + 1;

            $lastNumber = static::where('type', $invoice->type)
                ->where('number', 'like', $pattern . '%')
                ->selectRaw("MAX(CAST(SUBSTRING(number, {$substrStart}) AS UNSIGNED)) AS max_seq")
                ->value('max_seq');

            $nextSequence = ((int) $lastNumber) + 1;
            $invoice->number = $prefix . '-' . $currentYear . '-' . str_pad($nextSequence, 5, '0', STR_PAD_LEFT);

            // Safety fallback in case of racing duplicates (conflict key) on unique index
            while (static::where('number', $invoice->number)->exists()) {
                $nextSequence++;
                $invoice->number = $prefix . '-' . $currentYear . '-' . str_pad($nextSequence, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function client()         { return $this->belongsTo(Client::class); }
    public function creator()        { return $this->belongsTo(User::class, 'created_by'); }
    public function validator()      { return $this->belongsTo(User::class, 'validated_by'); }
    public function items()          { return $this->hasMany(InvoiceItem::class); }
    public function redevances()     { return $this->hasMany(Redevance::class); }
    public function encaissements()  { return $this->hasMany(Encaissement::class); }
    public function unlockRequests() { return $this->hasMany(InvoiceUnlockRequest::class); }

    public function recalculate(): void
    {
        $this->loadMissing('items');
        $subtotal = $this->items->sum('subtotal');
        $tax      = $this->items->sum(fn ($i) => $i->subtotal * ($i->tax_rate / 100));
        $this->updateQuietly([
            'subtotal'   => $subtotal,
            'tax_amount' => $tax,
            'total'      => $subtotal + $tax - $this->discount_amount,
        ]);
    }

    public function validate(User $user): void
    {
        $this->update([
            'status'       => 'validee',
            'validated_by' => $user->id,
            'validated_at' => now(),
        ]);
    }

    public function lock(): void
    {
        $this->update(['is_locked' => true, 'locked_at' => now(), 'status' => 'verrouillee']);
    }

    public function unlock(): void
    {
        $this->update(['is_locked' => false]);
    }

    public function markAsPaid(): void
    {
        $this->update(['status' => 'payee']);
    }

    public function isProForma(): bool   { return $this->type === 'pro_forma'; }
    public function isDefinitive(): bool { return $this->type === 'definitive'; }
    public function isRedevance(): bool  { return $this->type === 'redevance'; }
    public function isPaid(): bool       { return $this->status === 'payee'; }
    public function isLocked(): bool     { return (bool) $this->is_locked; }
}
