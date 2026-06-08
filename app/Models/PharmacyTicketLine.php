<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PharmacyTicketLine extends Model
{
    use HasFactory;

    protected $table = 'pharmacy_ticket_lines';

    protected $fillable = [
        'pharmacy_ticket_id',
        'medicament_id',
        'libelle_medicament',
        'quantite',
        'prix_unitaire',
        'sous_total',
    ];

    protected $casts = [
        'quantite' => 'integer',
        'prix_unitaire' => 'decimal:2',
        'sous_total' => 'decimal:2',
    ];

    // === Relations ===

    public function pharmacyTicket()
    {
        return $this->belongsTo(PharmacyTicket::class);
    }

    public function medicament()
    {
        return $this->belongsTo(Medicament::class);
    }
}
