<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'actor_id', 'actor_name', 'actor_role', 'action',
        'subject_type', 'subject_id', 'description', 'meta', 'ip_address',
    ];

    protected $casts = [
        'meta'       => 'array',
        'created_at' => 'datetime',
    ];

    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'actor_id'); }
    public function subject(): MorphTo { return $this->morphTo(); }

    public static function record(string $action, ?Model $subject, string $description, array $meta = []): self
    {
        $user = Auth::user();

        return self::create([
            'actor_id'     => $user?->id,
            'actor_name'   => $user?->name ?? 'System',
            'actor_role'   => $user?->role?->name ?? 'system',
            'action'       => $action,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id'   => $subject?->id,
            'description'  => $description,
            'meta'         => $meta ?: null,
            'ip_address'   => request()?->ip(),
        ]);
    }
}
