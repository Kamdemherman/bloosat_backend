<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'client_id', 'invoice_id', 'start_date',
        'current_cycle_start', 'current_cycle_end',
        'monthly_amount', 'status',
    ];

    protected $casts = [
        'start_date'          => 'date',
        'current_cycle_start' => 'date',
        'current_cycle_end'   => 'date',
    ];

    public function client()     { return $this->belongsTo(Client::class); }
    public function invoice()    { return $this->belongsTo(Invoice::class); }
    public function redevances() { return $this->hasMany(Redevance::class); }

    public function daysUntilExpiry(): int
    {
        return (int) now()->diffInDays($this->current_cycle_end, false);
    }

    public function isExpiringSoon(int $days = 7): bool
    {
        $d = $this->daysUntilExpiry();
        return $d <= $days && $d >= 0;
    }

    public function generateNextRedevance(): Redevance
    {
        $nextStart = $this->current_cycle_end->copy()->addDay();
        $nextEnd   = $nextStart->copy()->addMonth()->subDay();

        $invoice = Invoice::create([
            'client_id'  => $this->client_id,
            'created_by' => 1,
            'type'       => 'redevance',
            'status'     => 'brouillon',
            'issue_date' => now(),
            'due_date'   => $nextEnd,
            'total'      => $this->monthly_amount,
            'subtotal'   => $this->monthly_amount,
        ]);

        return Redevance::create([
            'subscription_id' => $this->id,
            'invoice_id'      => $invoice->id,
            'period_start'    => $nextStart,
            'period_end'      => $nextEnd,
            'status'          => 'non_payee',
        ]);
    }

    public function renewCycle(): void
    {
        $this->update([
            'current_cycle_start' => $this->current_cycle_end->copy()->addDay(),
            'current_cycle_end'   => $this->current_cycle_end->copy()->addMonth(),
        ]);
    }
}
