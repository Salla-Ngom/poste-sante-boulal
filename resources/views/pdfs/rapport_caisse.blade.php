<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport de caisse - PS Boulal</title>
    <style>
        @page { margin: 1.5cm; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10pt;
            color: #1f2937;
            line-height: 1.5;
        }
        .header {
            border-bottom: 3px solid #059669;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #059669;
            font-size: 22pt;
            margin: 0 0 5px;
        }
        .header .subtitle {
            color: #9ca3af;
            font-size: 9pt;
            margin: 0 0 8px;
            letter-spacing: 1px;
        }
        .header h2 {
            color: #6b7280;
            font-size: 13pt;
            margin: 0;
            font-weight: normal;
        }
        .meta {
            background: #f0fdf4;
            border: 1px solid #86efac;
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 6px;
        }
        .meta table {
            width: 100%;
            border-collapse: collapse;
        }
        .meta td {
            padding: 4px 0;
            font-size: 10pt;
        }
        .meta td.label {
            color: #6b7280;
            width: 35%;
        }
        .meta td.value {
            font-weight: bold;
        }
        .section-title {
            background: #059669;
            color: white;
            padding: 8px 12px;
            font-size: 11pt;
            font-weight: bold;
            margin: 20px 0 10px;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .recap {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .recap-row {
            display: table;
            width: 100%;
            padding: 6px 0;
            border-bottom: 1px dashed #e5e7eb;
        }
        .recap-row:last-child {
            border-bottom: none;
        }
        .recap-label {
            display: table-cell;
            color: #4b5563;
        }
        .recap-value {
            display: table-cell;
            text-align: right;
            font-weight: bold;
        }
        .recap-total {
            background: #ecfdf5;
            padding: 10px 15px;
            margin-top: 10px;
            border-radius: 4px;
            font-size: 12pt;
        }
        .ecart-zero { color: #059669; }
        .ecart-positif { color: #2563eb; }
        .ecart-negatif { color: #dc2626; }
        table.tickets {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table.tickets th {
            background: #f3f4f6;
            padding: 8px;
            text-align: left;
            font-size: 9pt;
            font-weight: bold;
            color: #4b5563;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border-bottom: 2px solid #d1d5db;
        }
        table.tickets td {
            padding: 6px 8px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 9pt;
        }
        table.tickets tr:nth-child(even) { background: #f9fafb; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .signatures {
            margin-top: 35px;
            display: table;
            width: 100%;
        }
        .signature-block {
            display: table-cell;
            width: 50%;
            padding: 10px 15px;
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #1f2937;
            margin-top: 50px;
            padding-top: 5px;
            font-size: 9pt;
            color: #6b7280;
        }
        .footer {
            margin-top: 30px;
            padding-top: 12px;
            border-top: 1px solid #e5e7eb;
            font-size: 8pt;
            color: #9ca3af;
            text-align: center;
        }
        .hash {
            font-family: 'Courier New', monospace;
            background: #f3f4f6;
            padding: 4px 8px;
            border-radius: 3px;
            word-break: break-all;
            font-size: 7pt;
            color: #4b5563;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>POSTE DE SANTÉ DE BOULAL</h1>
        <p class="subtitle">PS BOULAL</p>
        <h2>RAPPORT DE CAISSE</h2>
    </div>

    <div class="meta">
        <table>
            <tr>
                <td class="label">Caissier :</td>
                <td class="value">{{ $caissier->name }}</td>
            </tr>
            <tr>
                <td class="label">Ouverte le :</td>
                <td class="value">{{ \Carbon\Carbon::parse($session->ouverte_le)->format('d/m/Y à H:i') }}</td>
            </tr>
            <tr>
                <td class="label">Fermée le :</td>
                <td class="value">{{ \Carbon\Carbon::parse($session->fermee_le)->format('d/m/Y à H:i') }}</td>
            </tr>
            <tr>
                <td class="label">Durée :</td>
                <td class="value">{{ $duree }}</td>
            </tr>
        </table>
    </div>

    <div class="section-title">Récapitulatif financier</div>
    <div class="recap">
        <div class="recap-row">
            <div class="recap-label">Fond de caisse initial</div>
            <div class="recap-value">{{ number_format($session->fond_caisse_initial, 0, ',', ' ') }} FCFA</div>
        </div>
        <div class="recap-row">
            <div class="recap-label">Nombre de tickets vendus</div>
            <div class="recap-value">{{ $nombreTickets }} tickets</div>
        </div>
        <div class="recap-row">
            <div class="recap-label">Total des ventes</div>
            <div class="recap-value" style="color: #059669;">+ {{ number_format($totalVentes, 0, ',', ' ') }} FCFA</div>
        </div>
        <div class="recap-row">
            <div class="recap-label">Total des dépenses</div>
            <div class="recap-value" style="color: #dc2626;">- {{ number_format($totalDepenses, 0, ',', ' ') }} FCFA</div>
        </div>
    </div>

    <div class="recap-total">
        <div class="recap-row">
            <div class="recap-label"><strong>Montant attendu en caisse</strong></div>
            <div class="recap-value" style="color: #059669; font-size: 13pt;">
                {{ number_format($montantAttendu, 0, ',', ' ') }} FCFA
            </div>
        </div>
        <div class="recap-row">
            <div class="recap-label"><strong>Montant compté</strong></div>
            <div class="recap-value" style="font-size: 13pt;">
                {{ number_format($session->montant_compte, 0, ',', ' ') }} FCFA
            </div>
        </div>
        <div class="recap-row">
            <div class="recap-label"><strong>ÉCART</strong></div>
            <div class="recap-value
                @if($session->ecart == 0) ecart-zero
                @elseif($session->ecart > 0) ecart-positif
                @else ecart-negatif
                @endif"
                style="font-size: 14pt;">
                {{ $session->ecart > 0 ? '+' : '' }}{{ number_format($session->ecart, 0, ',', ' ') }} FCFA
            </div>
        </div>
    </div>

    @if($nombreTickets > 0)
    <div class="section-title">Détail des tickets vendus</div>
    <table class="tickets">
        <thead>
            <tr>
                <th>N°</th>
                <th>Heure</th>
                <th>Patient</th>
                <th>Prestation</th>
                <th class="text-right">Montant</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tickets as $ticket)
            <tr>
                <td><strong>{{ str_pad($ticket->numero, 4, '0', STR_PAD_LEFT) }}</strong></td>
                <td>{{ \Carbon\Carbon::parse($ticket->emis_le)->format('H:i') }}</td>
                <td>{{ $ticket->patient_prenom }} {{ $ticket->patient_nom }}</td>
                <td>{{ $ticket->ticketType->libelle }}</td>
                <td class="text-right"><strong>{{ number_format($ticket->prix_paye, 0, ',', ' ') }}</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="signatures">
        <div class="signature-block">
            <div class="signature-line">Signature du caissier</div>
            <div style="font-size: 9pt; margin-top: 3px;">{{ $caissier->name }}</div>
        </div>
        <div class="signature-block">
            <div class="signature-line">Signature du superviseur</div>
            <div style="font-size: 9pt; margin-top: 3px; color: #9ca3af;">Date :</div>
        </div>
    </div>

    <div class="footer">
        <p style="margin: 0 0 5px;">
            Document généré le {{ now()->format('d/m/Y à H:i:s') }} — Poste de Santé de Boulal
        </p>
        <div class="hash">{{ $hash }}</div>
    </div>

</body>
</html>
