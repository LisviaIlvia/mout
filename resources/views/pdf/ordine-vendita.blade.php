<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ordine Vendita {{ $document->numero }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 0;
        }
        
        .header {
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .company-info {
            float: left;
            width: 50%;
        }
        
        .document-info {
            float: right;
            width: 45%;
            text-align: right;
        }
        
        .clear {
            clear: both;
        }
        
        .section {
            margin-bottom: 25px;
        }
        
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #333;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }
        
        .customer-info {
            float: left;
            width: 60%;
        }
        
        .order-details {
            float: right;
            width: 35%;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .table th {
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-weight: bold;
        }
        
        .table td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        
        .table .text-right {
            text-align: right;
        }
        
        .table .text-center {
            text-align: center;
        }
        
        .totals {
            float: right;
            width: 300px;
            margin-top: 20px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        
        .total-row.final {
            font-weight: bold;
            font-size: 14px;
            border-top: 2px solid #333;
            padding-top: 10px;
            margin-top: 10px;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ccc;
            font-size: 10px;
            color: #666;
        }
        
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <!-- Header con informazioni azienda e documento -->
    <div class="header">
        <div class="company-info">
            @if($azienda)
                <h1>{{ $azienda->nome ?? 'Nome Azienda' }}</h1>
                @if($aziendaIndirizzi->count() > 0)
                    @php $indirizzo = $aziendaIndirizzi->first(); @endphp
                    <p>
                        {{ $indirizzo->indirizzo ?? '' }}<br>
                        {{ $indirizzo->cap ?? '' }} {{ $indirizzo->comune ?? '' }} ({{ $indirizzo->provincia ?? '' }})<br>
                        Tel: {{ $azienda->telefono ?? '' }}<br>
                        Email: {{ $azienda->email ?? '' }}
                    </p>
                @endif
            @else
                <h1>Nome Azienda</h1>
                <p>Indirizzo azienda<br>
                Tel: Telefono<br>
                Email: email@azienda.com</p>
            @endif
        </div>
        
        <div class="document-info">
            <h2>ORDINE DI VENDITA</h2>
            <p><strong>Numero:</strong> {{ $document->numero }}</p>
            <p><strong>Data:</strong> {{ \Carbon\Carbon::parse($document->data)->format('d/m/Y') }}</p>
            <p><strong>Stato:</strong> {{ $document->stato ?? 'Aperto' }}</p>
        </div>
        
        <div class="clear"></div>
    </div>

    <!-- Informazioni cliente e dettagli ordine -->
    <div class="section">
        <div class="customer-info">
            <div class="section-title">DESTINATARIO</div>
            @if($document->entity)
                <p><strong>{{ $document->entity->nome }}</strong></p>
                @if($document->entity->partita_iva)
                    <p>P.IVA: {{ $document->entity->partita_iva }}</p>
                @endif
                @if($document->entity->codice_fiscale)
                    <p>Codice Fiscale: {{ $document->entity->codice_fiscale }}</p>
                @endif
                @if($document->indirizzo)
                    <p>
                        {{ $document->indirizzo->indirizzo ?? '' }}<br>
                        {{ $document->indirizzo->cap ?? '' }} {{ $document->indirizzo->comune ?? '' }} ({{ $document->indirizzo->provincia ?? '' }})
                    </p>
                @endif
            @else
                <p>Cliente non specificato</p>
            @endif
        </div>
        
        <div class="order-details">
            <div class="section-title">DETTAGLI ORDINE</div>
            <p><strong>Data Ordine:</strong> {{ \Carbon\Carbon::parse($document->data)->format('d/m/Y') }}</p>
            @if($document->dettagli && $document->dettagli->data_evasione)
                <p><strong>Data Evasione:</strong> {{ \Carbon\Carbon::parse($document->dettagli->data_evasione)->format('d/m/Y') }}</p>
            @endif
        </div>
        
        <div class="clear"></div>
    </div>

    <!-- Tabella elementi -->
    <div class="section">
        <div class="section-title">ELEMENTI ORDINATI</div>
        
        <table class="table">
            <thead>
                <tr>
                    <th>Codice</th>
                    <th>Descrizione</th>
                    <th>Fornitore</th>
                    <th>Riferimento</th>
                    <th class="text-center">Q.tà</th>
                    <th class="text-center">U.M.</th>
                    <th class="text-right">Prezzo Unit.</th>
                    <th class="text-center">IVA</th>
                    <th class="text-right">Importo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($elementi as $elemento)
                    <tr>
                        <td>
                            @if($elemento['tipo'] === 'merci' || $elemento['tipo'] === 'servizi')
                                {{ $elemento['product_id'] ?? '-' }}
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $elemento['nome'] ?? $elemento['descrizione'] ?? '-' }}</td>
                        <td>
                            @if(isset($elemento['fornitore_id']) && $elemento['fornitore_id'])
                                @php
                                    $fornitore = \App\Models\Entity::find($elemento['fornitore_id']);
                                @endphp
                                {{ $fornitore ? $fornitore->nome : '-' }}
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $elemento['riferimento'] ?? '-' }}</td>
                        <td class="text-center">{{ $elemento['quantita'] ?? '-' }}</td>
                        <td class="text-center">{{ $elemento['unita_misura'] ?? 'NR' }}</td>
                        <td class="text-right">{{ number_format($elemento['prezzo'] ?? 0, 2, ',', '.') }} €</td>
                        <td class="text-center">{{ $elemento['iva']['aliquota'] ?? '-' }}%</td>
                        <td class="text-right">{{ number_format($elemento['importo'] ?? 0, 2, ',', '.') }} €</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Totali -->
    <div class="totals">
        @php
            $imponibile = collect($elementi)->sum('importo');
            $iva = collect($elementi)->sum(function($elemento) {
                $aliquota = $elemento['iva']['aliquota'] ?? 0;
                $importo = $elemento['importo'] ?? 0;
                return ($importo * $aliquota) / 100;
            });
            $totale = $imponibile + $iva;
        @endphp
        
        <div class="total-row">
            <span>Imponibile:</span>
            <span>{{ number_format($imponibile, 2, ',', '.') }} €</span>
        </div>
        <div class="total-row">
            <span>IVA:</span>
            <span>{{ number_format($iva, 2, ',', '.') }} €</span>
        </div>
        <div class="total-row final">
            <span>TOTALE:</span>
            <span>{{ number_format($totale, 2, ',', '.') }} €</span>
        </div>
    </div>

    <div class="clear"></div>

    <!-- Note -->
    @if($document->note)
        <div class="section">
            <div class="section-title">NOTE</div>
            <p>{{ $document->note }}</p>
        </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>Documento generato automaticamente il {{ now()->format('d/m/Y H:i') }}</p>
        <p>Ordine Vendita {{ $document->numero }} - Pagina 1 di 1</p>
    </div>
</body>
</html> 