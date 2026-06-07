<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'child_id', 'package_id', 'enrollment_id',
        'transaction_code', 'amount', 'status',
        'payment_method', 'payment_proof', 'payment_notes', 'admin_notes',
        'verified_by', 'paid_at', 'expired_at',
    ];

    protected $casts = [
        'amount'     => 'integer',
        'paid_at'    => 'datetime',
        'expired_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (Transaction $t) {
            if (empty($t->transaction_code)) {
                $t->transaction_code = 'TRX-' . strtoupper(Str::random(8));
            }
        });
    }

    public function user(): BelongsTo       { return $this->belongsTo(User::class); }
    public function child(): BelongsTo      { return $this->belongsTo(Child::class); }
    public function package(): BelongsTo    { return $this->belongsTo(Package::class); }
    public function enrollment(): BelongsTo { return $this->belongsTo(Enrollment::class); }
    public function verifiedBy(): BelongsTo { return $this->belongsTo(User::class, 'verified_by'); }
}
