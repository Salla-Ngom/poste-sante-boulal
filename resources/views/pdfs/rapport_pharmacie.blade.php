<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport Pharmacie - PS Boulal</title>
    <style>
        @page { margin: 1.5cm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11pt; color: #333; line-height: 1.4; }
        h1 { margin: 0; font-size: 20pt; color: #7c3aed; }
        h2 { margin: 20px 0 10px 0; font-size: 13pt; color: #6b21a8; border-bottom: 2px solid #ddd; padding-bottom: 4px; }
        .header { border-bottom: 3px double #7c3aed; padding-bottom: 12px; margin-bottom: 20px; }
        .header-subtitle { color: #9ca3af; font-size: 9pt; margin-top: 2px; letter-spacing: 1px; }
        .subtitle { font-size: 11pt; color: #666; margin-top: 8px; }
        .meta { display: table; width: 100%; margin: 15px 0; }
        .meta-row { display: table-row; }
        .meta-cell { display: table-cell; padding: 4px 8px; font-size: 10pt; }
        .meta-label { color: #666; width: 35%; }
        .meta-value { font-weight: bold; color: #222; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 10pt; }
        thead { background-color: #f3e8ff; }
        th { padding: 8px; text-align: left; font-weight: bold; color: #6b21a8; border-bottom: 2px solid #ddd6fe; }
        td { padding: 6px 8px; border-bottom: 1px solid #eee; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row { background-color: #faf5ff; font-weight: bold; }
        .summary { background-color: #faf5ff; border: 1px solid #ddd6fe; padding: 15px; border-radius: 4px; margin: 15px 0; }
        .summary-row { display: table; width: 100%; padding: 6px 0; }
        .summary-label { display: table-cell; color: #666; }
        .summary-value { display: table-cell; text-align: right; font-weight: bold; }
        .ecart-positif { color: #047857; }
        .ecart-negatif { color: #dc2626; }
        .ecart-nul { color: #4b5563; }
        .footer { margin-top: 30px; padding-top: 15px; border-top: 1px solid #ddd; font-size: 9pt; color: #888; }
        .hash { font-family: monospace; font-size: 8pt; word-break: break-all; background: #f5f5f5; padding: 6px; border-radius: 3px; }
    </style>
</head>
<body>

<div class="header">
    <h1>POSTE DE SANTÉ DE BOULAL</h1>
    <div class="header-subtitle">PS BOULAL</div>
    <div class="subtitle">RAPPORT DE CLÔTURE — CAISSE PHARMACIE</div>
</div>

<div class="meta">
    <div class="meta-row">
        <div class="meta-cell meta-label">Pharmacien</div>
        <div class="meta-cell meta-value">{{ $pharmacien->name }}</div>
    </div>
    <div class="meta-row">
        <div class="meta-cell meta-label">Session ouverte le</div>
        <div class="meta-cell meta-value">{{ \Carbon\Carbon::parse($session->ouverte_le)->format('d/m/Y à H:i') }}</div>
    </div>
    <div class="meta-row">
        <div class="meta-cell meta-label">Session clôturée le</div>
        <div class="meta-cell meta-value">{{ \Carbon\Carbon::parse($session->fermee_le)->format('d/m/Y à H:i') }}</div>
    </div>
    <div class="meta-row">
        <div class="meta-cell meta-label">Durée</div>
        <div class="meta-cell meta-value">{{ $duree }}</div>
    </div>
</div>

<h2>Synthèse financière</h2>

<div class="summary">
    <div class="summary-row">
        <div class="summary-label">Fond de caisse initial</div>
        <div class="summary-value">{{ number_format($session->fond_caisse_initial, 0, ',', ' ') }} FCFA</div>
    </div>
    <div class="summary-row">
        <div class="summary-label">Total des ventes ({{ $nombreTickets }} tickets)</div>
        <div class="summary-value">{{ number_format($totalVentes, 0, ',', ' ') }} FCFA</div>
    </div>
    @if(($totalCmu ?? 0) > 0)
    <div class="summary-row">
        <div class="summary-label">dont prises en charge CMU ({{ $nombreCmu }} tickets)</div>
        <div class="summary-value" style="color:#1d4ed8;">− {{ number_format($totalCmu, 0, ',', ' ') }} FCFA</div>
    </div>
    @endif
    <div class="summary-row">
        <div class="summary-label">Ventes en espèces</div>
        <div class="summary-value" style="color:#047857;">+ {{ number_format($ventesEspeces ?? $totalVentes, 0, ',', ' ') }} FCFA</div>
    </div>
    @if($totalDepenses > 0)
    <div class="summary-row">
        <div class="summary-label">Total des dépenses</div>
        <div class="summary-value" style="color:#dc2626;">− {{ number_format($totalDepenses, 0, ',', ' ') }} FCFA</div>
    </div>
    @endif
    <div class="summary-row" style="border-top:1px solid #ddd6fe; margin-top:8px; padding-top:10px;">
        <div class="summary-label"><strong>Montant attendu</strong></div>
        <div class="summary-value" style="color:#6b21a8; font-size:13pt;">{{ number_format($montantAttendu, 0, ',', ' ') }} FCFA</div>
    </div>
    <div class="summary-row">
        <div class="summary-label">Montant compté</div>
        <div class="summary-value">{{ number_format($session->montant_compte, 0, ',', ' ') }} FCFA</div>
    </div>
    <div class="summary-row" style="border-top:1px solid #ddd6fe; margin-top:8px; padding-top:10px;">
        <div class="summary-label"><strong>Écart</strong></div>
        <div class="summary-value @if($session->ecart == 0) ecart-nul @elseif($session->ecart > 0) ecart-positif @else ecart-negatif @endif" style="font-size:13pt;">
            @if($session->ecart > 0)+@endif{{ number_format($session->ecart, 0, ',', ' ') }} FCFA
        </div>
    </div>
</div>

@if($nombreTickets > 0)
<h2>Détail des tickets pharmacie</h2>
<table>
    <thead>
        <tr>
            <th>N°</th>
            <th>Heure</th>
            <th>Patient</th>
            <th class="text-center">Lignes</th>
            <th class="text-right">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($tickets as $ticket)
        <tr>
            <td>{{ str_pad($ticket->numero, 4, '0', STR_PAD_LEFT) }}</td>
            <td>{{ \Carbon\Carbon::parse($ticket->emis_le)->format('H:i') }}</td>
            <td>{{ $ticket->patient_prenom }} {{ $ticket->patient_nom }}</td>
            <td class="text-center">{{ $ticket->lines->count() }}</td>
            <td class="text-right">{{ number_format($ticket->total, 0, ',', ' ') }} FCFA</td>
        </tr>
        @endforeach
        <tr class="total-row">
            <td colspan="4"><strong>TOTAL</strong></td>
            <td class="text-right"><strong>{{ number_format($totalVentes, 0, ',', ' ') }} FCFA</strong></td>
        </tr>
    </tbody>
</table>
@endif

<div class="footer">
    <p><strong>Authentification du rapport :</strong></p>
    <div class="hash">{{ $hash }}</div>
    <p style="margin-top:10px;">Rapport généré le {{ \Carbon\Carbon::now()->format('d/m/Y à H:i:s') }} — Poste de Santé de Boulal</p>
</div>

</body>
</html>
