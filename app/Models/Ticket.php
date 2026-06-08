<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
    'tenant_id',
    'date_emission',
    'numero',
    'cash_session_id',
    'ticket_type_id',
    'user_id',
    'patient_nom',
    'patient_prenom',
    'patient_age',
    'patient_adresse',
    'prix_paye',
    'statut',
    'emis_le',
];

  protected $casts = [
    'date_emission' => 'date',
    'emis_le' => 'datetime',
    'prix_paye' => 'decimal:2',
    'numero' => 'integer',
    'patient_age' => 'integer',
];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }

    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cancellation(): HasOne
    {
        return $this->hasOne(Cancellation::class);
    }

    // === Helpers ===

    public function isActif(): bool
    {
        return $this->statut === 'actif';
    }

    public function isAnnule(): bool
    {
        return $this->statut === 'annule';
    }

    public function numeroFormate(): string
    {
        return $this->date_emission->format('Ymd') . '-' . str_pad($this->numero, 4, '0', STR_PAD_LEFT);
    }
}
