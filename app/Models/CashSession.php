<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CashSession extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id',
        'type_caisse',
        'user_id',
        'ouverte_le',
        'fermee_le',
        'fond_caisse_initial',
        'montant_compte',
        'ecart',
        'statut',
    ];

    protected $casts = [
        'ouverte_le' => 'datetime',
        'fermee_le' => 'datetime',
        'fond_caisse_initial' => 'decimal:2',
        'montant_compte' => 'decimal:2',
        'ecart' => 'decimal:2',
    ];

    // === Constantes types de caisse ===

    public const TYPE_ACCUEIL = 'accueil';
    public const TYPE_PHARMACIE = 'pharmacie';

    // === Scopes ===

    /**
     * Scope : sessions de la caisse Accueil uniquement
     */
    public function scopeAccueil($query)
    {
        return $query->where('type_caisse', self::TYPE_ACCUEIL);
    }

    /**
     * Scope : sessions de la caisse Pharmacie uniquement
     */
    public function scopePharmacie($query)
    {
        return $query->where('type_caisse', self::TYPE_PHARMACIE);
    }

    /**
     * Scope : sessions ouvertes
     */
    public function scopeOuvertes($query)
    {
        return $query->where('statut', 'ouverte');
    }

    // === Relations ===

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function pharmacyTickets(): HasMany
    {
        return $this->hasMany(PharmacyTicket::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function cashReport(): HasOne
    {
        return $this->hasOne(CashReport::class);
    }

    // === Helpers métier ===

    public function isOuverte(): bool
    {
        return $this->statut === 'ouverte';
    }

    public function isAccueil(): bool
    {
        return $this->type_caisse === self::TYPE_ACCUEIL;
    }

    public function isPharmacie(): bool
    {
        return $this->type_caisse === self::TYPE_PHARMACIE;
    }

    /**
     * Total des ventes selon le type de caisse
     */
    public function totalVentes(): float
    {
        if ($this->isPharmacie()) {
            return (float) $this->pharmacyTickets()
                ->where('statut', 'actif')
                ->sum('total');
        }

        return (float) $this->tickets()
            ->where('statut', 'actif')
            ->sum('prix_paye');
    }

    /**
     * Total des dépenses de la session
     */
    public function totalDepenses(): float
    {
        return (float) $this->expenses()->sum('montant');
    }

    /**
     * Montant attendu en caisse (fond + ventes - dépenses)
     */
    public function montantAttendu(): float
    {
        return (float) $this->fond_caisse_initial + $this->totalVentes() - $this->totalDepenses();
    }

    /**
     * Libellé lisible du type de caisse
     */
    public function getTypeCaisseLibelleAttribute(): string
    {
        return match($this->type_caisse) {
            self::TYPE_ACCUEIL => 'Accueil',
            self::TYPE_PHARMACIE => 'Pharmacie',
            default => $this->type_caisse,
        };
    }
}
