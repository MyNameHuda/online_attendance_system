<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_KARYAWAN = 'karyawan';
    public const ROLE_KADIV = 'kepala_divisi';
    public const ROLE_ADMIN = 'admin';

    protected $fillable = [
        'nik',
        'name',
        'email',
        'password',
        'division_id',
        'position',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isKadiv(): bool
    {
        return $this->role === self::ROLE_KADIV;
    }

    public function isKaryawan(): bool
    {
        return $this->role === self::ROLE_KARYAWAN;
    }

    /**
     * Apakah user ini adalah Kepala Divisi untuk divisi tertentu.
     */
    public function isKadivOf(int $divisionId): bool
    {
        return $this->role === self::ROLE_KADIV && $this->division_id === $divisionId;
    }
}
