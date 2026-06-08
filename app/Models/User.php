<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'role',
        'actif',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'actif' => 'boolean',
    ];

    // === Relations ===

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function cashSessions(): HasMany
    {
        return $this->hasMany(CashSession::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function pharmacyTickets(): HasMany
    {
        return $this->hasMany(PharmacyTicket::class);
    }

    // === Helpers de rôle ===

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isSuperviseur(): bool
    {
        return $this->role === 'superviseur';
    }

    public function isCaissier(): bool
    {
        return $this->role === 'caissier';
    }

    public function isPharmacien(): bool
    {
        return $this->role === 'pharmacien';
    }

    /**
     * Peut effectuer des opérations (vente, caisse, dépenses).
     * Le superviseur est exclu (lecture seule).
     */
    public function canOperate(): bool
    {
        return in_array($this->role, ['admin', 'caissier', 'pharmacien']);
    }

    /**
     * Peut opérer la caisse Accueil (vente de tickets).
     */
    public function canOperateAccueil(): bool
    {
        return in_array($this->role, ['admin', 'caissier']);
    }

    /**
     * Peut opérer la caisse Pharmacie (vente de médicaments).
     */
    public function canOperatePharmacie(): bool
    {
        return in_array($this->role, ['admin', 'pharmacien']);
    }
}
