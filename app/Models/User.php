<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'role_id', 'name', 'email', 'password',
        'google_id', 'avatar', 'profile_photo',
        'whatsapp_number', 'address', 'occupation',
        'is_active', 'registration_status',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'is_active'           => 'boolean',
        'password'            => 'hashed',
        'registration_status' => 'string',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(Child::class);
    }

    public function coach(): HasOne
    {
        return $this->hasOne(Coach::class);
    }

    public function appNotifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function hasRole(string $role): bool
    {
        return $this->role?->name === $role;
    }

    public function isApproved(): bool
    {
        return $this->registration_status === 'approved';
    }

    public function redirectRouteName(): string
    {
        return match ($this->role?->name) {
            'super_admin' => 'superadmin.dashboard',
            'admin'       => 'admin.dashboard',
            'coach'       => 'coach.dashboard',
            'parent'      => 'parent.dashboard',
            default       => 'login',
        };
    }
}
