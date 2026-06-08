<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashReport extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'cash_session_id',
        'contenu_hash',
        'pdf_path',
        'genere_le',
    ];

    protected $casts = [
        'genere_le' => 'datetime',
    ];

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }
}
