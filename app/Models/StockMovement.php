<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;

    protected $table = 'stock_movements';

    protected $fillable = [
        'tenant_id',
        'medicament_id',
        'user_id',
        'type',
        'quantite',
        'quantite_avant',
        'quantite_apres',
        'motif',
        'reference_externe',
        'pharmacy_ticket_id',
        'survenu_le',
    ];

    protected $casts = [
        'quantite' => 'integer',
        'quantite_avant' => 'integer',
        'quantite_apres' => 'integer',
        'survenu_le' => 'datetime',
    ];

    // === Constantes types de mouvement ===

    public const TYPE_ENTREE = 'entree';
    public const TYPE_SORTIE_VENTE = 'sortie_vente';
    public const TYPE_REGUL_POSITIVE = 'regularisation_positive';
    public const TYPE_REGUL_NEGATIVE = 'regularisation_negative';

    public const TYPES = [
        self::TYPE_ENTREE => 'Entrée de stock',
        self::TYPE_SORTIE_VENTE => 'Sortie pour vente',
        self::TYPE_REGUL_POSITIVE => 'Régularisation positive',
        self::TYPE_REGUL_NEGATIVE => 'Régularisation négative',
    ];

    // === Helpers métier ===

    public function getTypeLibelleAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function isEntree(): bool
    {
        return in_array($this->type, [
            self::TYPE_ENTREE,
            self::TYPE_REGUL_POSITIVE,
        ]);
    }

    public function isSortie(): bool
    {
        return in_array($this->type, [
            self::TYPE_SORTIE_VENTE,
            self::TYPE_REGUL_NEGATIVE,
        ]);
    }

    public function isVente(): bool
    {
        return $this->type === self::TYPE_SORTIE_VENTE;
    }

    public function isRegularisation(): bool
    {
        return in_array($this->type, [
            self::TYPE_REGUL_POSITIVE,
            self::TYPE_REGUL_NEGATIVE,
        ]);
    }

    /**
     * Variation signée (+ ou -) pour affichage
     */
    public function getVariationAttribute(): int
    {
        return $this->isEntree() ? $this->quantite : -$this->quantite;
    }

    // === Scopes ===

    /**
     * Scope : entrées uniquement
     */
    public function scopeEntrees($query)
    {
        return $query->whereIn('type', [
            self::TYPE_ENTREE,
            self::TYPE_REGUL_POSITIVE,
        ]);
    }

    /**
     * Scope : sorties uniquement
     */
    public function scopeSorties($query)
    {
        return $query->whereIn('type', [
            self::TYPE_SORTIE_VENTE,
            self::TYPE_REGUL_NEGATIVE,
        ]);
    }

    /**
     * Scope : régularisations uniquement (manuelles)
     */
    public function scopeRegularisations($query)
    {
        return $query->whereIn('type', [
            self::TYPE_REGUL_POSITIVE,
            self::TYPE_REGUL_NEGATIVE,
        ]);
    }

    // === Relations ===

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function medicament(): BelongsTo
    {
        return $this->belongsTo(Medicament::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pharmacyTicket(): BelongsTo
    {
        return $this->belongsTo(PharmacyTicket::class);
    }
}
