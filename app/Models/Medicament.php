<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medicament extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'medicaments';

    protected $fillable = [
        'tenant_id',
        'libelle',
        'forme_conditionnement',
        'prix',
        'quantite_stock',
        'seuil_alerte',
        'actif',
    ];

    protected $casts = [
        'prix' => 'decimal:2',
        'quantite_stock' => 'integer',
        'seuil_alerte' => 'integer',
        'actif' => 'boolean',
    ];

    /**
     * Scope : médicaments actifs uniquement
     */
    public function scopeActifs($query)
    {
        return $query->where('actif', true);
    }

    /**
     * Scope : médicaments en alerte de stock
     */
    public function scopeEnAlerte($query)
    {
        return $query->whereColumn('quantite_stock', '<=', 'seuil_alerte')
                     ->where('actif', true);
    }

    /**
     * Le médicament est-il en alerte de stock ?
     */
    public function getEnAlerteAttribute(): bool
    {
        return $this->quantite_stock <= $this->seuil_alerte;
    }

    /**
     * Le médicament est-il en rupture ?
     */
    public function getEnRuptureAttribute(): bool
    {
        return $this->quantite_stock <= 0;
    }

    // === Relations ===

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function ticketLines()
    {
        return $this->hasMany(PharmacyTicketLine::class);
    }
}
