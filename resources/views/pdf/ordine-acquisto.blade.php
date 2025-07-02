<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <title>Ordine di Acquisto - {{ $document->numero }}</title>
    <style>
        @page {
            size: A4;
            margin: 20mm 15mm 15mm 15mm;

            @top-center {
                content: element(page-header);
            }
        }

        body {
            counter-reset: page;
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            line-height: 1.3;
            color: #333;
            margin: 0;
        }

        #page-header {
            position: running(page-header);
        }

        .header-bar {
            background: #f5f5f5;
            font-weight: bold;
            text-transform: uppercase;
            padding: 10px 0 10px 0;
            font-size: 15px;
            letter-spacing: 1px;
            border-bottom: 2px solid #333;
        }

        .doc-info {
            display: flex;
            justify-content: flex-end;
            gap: 30px;
            font-size: 10px;
            margin-bottom: 8px;
            margin-top: 2px;
        }

        .doc-info span {
            display: inline-block;
            min-width: 90px;
        }

        .section-row {
            display: flex;
            border-bottom: 2px solid #e0e0e0;
            background: #fafafa;
        }

        .section-box {
            flex: 1;
            border-right: 1px solid #ccc;
            padding: 10px;
            font-size: 10px;
        }

        .section-box:last-child {
            border-right: none;
        }

        .section-title {
            font-weight: bold;
            margin-bottom: 4px;
            text-transform: uppercase;
            font-size: 10px;
            color: #333;
            letter-spacing: 1px;
        }

        .section-text {
            font-size: 11px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .table th,
        .table td {
            border: 1px solid #e0e0e0;
            padding: 6px 4px;
            font-size: 10px;
        }

        .table th {
            background: #f5f5f5;
            text-transform: uppercase;
            font-weight: bold;
        }

        .prezzo,
        .qta,
        .importo,
        .iva,
        .totale {

            text-align: center;
        }

        .codice, .desc {
            text-align: left;
        }

        .text-center {
            text-align: center;
        }

        .content-wrapper {
            min-height: 90vh;
            display: flex;
            flex-direction: column;
        }

        .push-footer {
            margin-top: auto;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .footer-table td {
            border: 1px solid #bbb;
            padding: 8px 6px;
            font-size: 10px;
        }

        .footer-table .label {
            background: #f5f5f5;
            font-weight: bold;
            width: 15%;
            text-align: center;
            text-transform: uppercase;
        }


        .notes-container {
            flex: 1;
            margin-top: 20px;
            padding: 10px;
            background: #fafbfc;
            width: 100%;
            border: 1px solid #e0e0e0;
        }

        .notes-title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .notes-text {
            font-size: 11px;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            margin-left: auto;
            width: 300px;
        }

        .summary-table td {
            border: 1px solid #e0e0e0;
            padding: 8px 12px;
            font-size: 11px;
        }

        .summary-table .label {
            background: #f5f5f5;
            font-weight: bold;
            text-align: left;
            width: 40%;
        }

        .summary-table .value {
            text-align: center;
            font-weight: bold;
            width: 60%;
        }

        .summary-table .total-row {
            background: #e0e0e0;
            font-weight: bold;
            font-size: 12px;
        }

        .summary-table th {
            background: #f5f5f5;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            font-size: 10px;
            border: 1px solid #e0e0e0;
            padding: 8px 12px;
        }

        .summary-table tbody tr:nth-child(even) {
            background: #fafafa;
        }

        .summary-table tbody tr:hover {
            background: #f0f0f0;
        }
    </style>
</head>

<body>
    <div class="content-wrapper">
        <div>
            <div class="section-row">
                <div class="section-box">
                    <div class="section-title">Destinatario</div>
                    <div class="section-text">{{ $azienda->ragione_sociale }}</div>
                    <div class="section-text">{{ $aziendaIndirizzi->first()->indirizzo ?? '' }}</div>
                    <div class="section-text">{{ $aziendaIndirizzi->first()->cap ?? '' }} {{ $aziendaIndirizzi->first()->comune ?? '' }} ({{ $aziendaIndirizzi->first()->provincia ?? '' }})</div>
                    <div class="section-text">P.IVA: {{ $azienda->partita_iva }}</div>
                    <div class="section-text">Tel: {{ $aziendaIndirizzi->first()->telefono ?? '' }}</div>
                    <div class="section-text">Email: {{ $azienda->pec }}</div>
                </div>
                <div class="section-box">
                    <div class="section-title">Fornitore</div>
                    <div class="section-text">{{ $document->entity->ragione_sociale ?? $document->entity->nome ?? '' }}</div>
                    <div class="section-text">{{ $document->indirizzo->indirizzo ?? '' }}</div>
                    <div class="section-text">{{ $document->indirizzo->cap ?? '' }} {{ $document->indirizzo->comune ?? '' }} ({{ $document->indirizzo->provincia ?? '' }})</div>
                    <div class="section-text">P.IVA: {{ $document->entity->partita_iva ?? '' }}</div>
                    <div class="section-text">Tel: {{ $document->entity->telefono ?? '' }}</div>
                    <div class="section-text">Email: {{ $document->entity->email ?? '' }}</div>
                </div>
                <!-- <div class="section-box">
                    <div class="section-title">Luogo di consegna</div>
                    <div class="section-text">{{ $aziendaIndirizzi->first()->indirizzo ?? '' }}</div>
                    <div class="section-text">{{ $aziendaIndirizzi->first()->cap ?? '' }} {{ $aziendaIndirizzi->first()->comune ?? '' }} ({{ $aziendaIndirizzi->first()->provincia ?? '' }})</div>
                </div> -->
            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th class="codice">Codice</th>
                        <th class="desc">Descrizione</th>
                        <th class="prezzo">Prezzo</th>
                        <th class="qta">Q.tà</th>
                        <th class="importo">Importo</th>
                        <th class="iva">IVA</th>
                        <th class="totale">Totale</th>
                    </tr>
                </thead>
                <tbody>
                                        @foreach($elementi as $elemento)
                        @if($elemento['tipo'] !== 'descrizione')
                            @php
                                $importo = $elemento['importo'] ?? 0;
                                $aliquotaIva = $elemento['iva']['aliquota'] ?? 0;
                                $ivaAmount = $importo * ($aliquotaIva / 100);
                                $totale = $importo + $ivaAmount;
                            @endphp
                            <tr>
                                <td>{{ $elemento['codice'] ?? '' }}</td>
                                <td>{{ $elemento['nome'] ?? '' }}</td>
                                <td class="prezzo">{{ number_format($elemento['prezzo'], 2, ',', '.') }}</td>
                                <td class="qta">{{ $elemento['quantita'] }}</td>
                                <td class="importo">{{ number_format($importo, 2, ',', '.') }}</td>
                                <td class="iva">{{ $aliquotaIva }}%</td>
                                <td class="totale">{{ number_format($totale, 2, ',', '.') }}</td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>

            <!-- Raggruppamento per aliquota IVA -->
            @php
                $ivaGroups = [];
                $totaleImponibile = 0;
                $totaleIva = 0;
                
                // Raggruppa per aliquota IVA
                foreach($elementi as $elemento) {
                    if($elemento['tipo'] !== 'descrizione') {
                        $importo = $elemento['importo'] ?? 0;
                        $aliquotaIva = $elemento['iva']['aliquota'] ?? 0;
                        $ivaAmount = $importo * ($aliquotaIva / 100);
                        
                        // Crea chiave per il raggruppamento
                        $ivaKey = $aliquotaIva . '%';
                        
                        if (!isset($ivaGroups[$ivaKey])) {
                            $ivaGroups[$ivaKey] = [
                                'aliquota' => $aliquotaIva,
                                'imponibile' => 0,
                                'iva' => 0
                            ];
                        }
                        
                        $ivaGroups[$ivaKey]['imponibile'] += $importo;
                        $ivaGroups[$ivaKey]['iva'] += $ivaAmount;
                        
                        $totaleImponibile += $importo;
                        $totaleIva += $ivaAmount;
                    }
                }
                
                $totaleDocumento = $totaleImponibile + $totaleIva;
            @endphp

            <!-- Tabella riassuntiva per aliquota IVA -->
            <table class="summary-table" style="margin-bottom: 20px;">
                <thead>
                    <tr>
                        <th class="label">Imponibile</th>
                        <th class="label">Aliquota</th>
                        <th class="label">IVA</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ivaGroups as $ivaKey => $group)
                        <tr>
                            <td class="value">{{ number_format($group['imponibile'], 2, ',', '.') }} €</td>
                            <td class="value">{{ $group['aliquota'] }}%</td>
                            <td class="value">{{ number_format($group['iva'], 2, ',', '.') }} €</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Tabella totali generali -->
            <table class="summary-table">
                <tr>
                    <td class="label">Totale Imponibile:</td>
                    <td class="value">{{ number_format($totaleImponibile, 2, ',', '.') }} €</td>
                </tr>
                <tr>
                    <td class="label">Totale IVA:</td>
                    <td class="value">{{ number_format($totaleIva, 2, ',', '.') }} €</td>
                </tr>
                <tr class="total-row">
                    <td class="label">TOTALE DOCUMENTO:</td>
                    <td class="value">{{ number_format($totaleDocumento, 2, ',', '.') }} €</td>
                </tr>
            </table>
        </div>
        <div class="push-footer">
            <!-- Note -->
            <div class="notes-container">
                <div class="notes-title">Note:</div>
                <div class="notes-text">{{ $document->note ?? '' }}</div>
            </div>
        </div>
    </div>
</body>

</html>