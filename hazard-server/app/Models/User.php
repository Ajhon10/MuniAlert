<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $attributes = ['role' => 'citizen', 'is_active' => true, 'security_version' => 0, 'mfa_failures' => 0];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'password_enabled',
        'mfa_secret',
        'mfa_recovery_codes',
        'mfa_last_step',
        'mfa_failures',
        'mfa_locked_until',
        'mfa_enrollment_until',
        'security_version',
        'is_active',
    ];

    protected $appends = ['has_password', 'mfa_enabled'];

    public function getMfaEnabledAttribute(): bool
    {
        return $this->mfa_confirmed_at !== null && $this->mfa_secret !== null;
    }

    public function getHasPasswordAttribute(): bool
    {
        return (bool) ($this->attributes['password_enabled'] ?? true);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'security_version' => 'integer',
            'mfa_secret' => 'encrypted',
            'mfa_recovery_codes' => 'encrypted:array',
            'mfa_confirmed_at' => 'datetime',
            'mfa_enrollment_until' => 'datetime',
            'mfa_locked_until' => 'datetime',
            'mfa_failures' => 'integer',
            'local_email_verified_at' => 'datetime',
            'reading_preferences' => 'array',
            'email_report_updates' => 'boolean',
        ];
    }
}
