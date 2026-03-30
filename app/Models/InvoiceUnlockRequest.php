<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceUnlockRequest extends Model
{
    protected $fillable = [
        'invoice_id', 'requested_by', 'approved_by', 'reason', 'status', 'resolved_at',
    ];

    protected $casts = ['resolved_at' => 'datetime'];

    public function invoice()   { return $this->belongsTo(Invoice::class); }
    public function requester() { return $this->belongsTo(User::class, 'requested_by'); }
    public function approver()  { return $this->belongsTo(User::class, 'approved_by'); }
}
