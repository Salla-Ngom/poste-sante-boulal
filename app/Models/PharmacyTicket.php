<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PharmacyTicket extends Model
{
    use HasFactory;

    protected $table = 'pharmacy_tickets';

    protected $fillable = [
        'tenant_id',
        'cash_session_id',
        'user_id',
        'numero',
        'date_emission',
        'emis_le',
        'total',
        'patient_nom',
        'patient_prenom',
        'patient_age',
        'est_cmu',
        'statut',
    ];

    protected $casts = [
        'numero' => 'integer',
        'date_emission' => 'date',
        'emis_le' => 'datetime',
        'total' => 'decimal:2',
        'patient_age' => 'integer',
        'est_cmu' => 'boolean',
    ];

    /**
     * Numéro formaté pour affichage (ex: 0042)
     */
    public function getNumeroFormatAttribute(): string
    {
        return str_pad($this->numero, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Nom complet du patient
     */
    public function getPatientCompletAttribute(): string
    {
        return trim("{$this->patient_prenom} {$this->patient_nom}");
    }

    /**
     * Scope : tickets actifs uniquement
     */
    public function scopeActifs($query)
    {
        return $query->where('statut', 'actif');
    }

    // === Relations ===

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function cashSession()
    {
        return $this->belongsTo(CashSession::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lines()
    {
        return $this->hasMany(PharmacyTicketLine::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }
}
