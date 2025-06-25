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

        /* Stili per gli allegati */
        .attachments {
            margin-top: 30px;
        }

        .attachments-list {
            margin-top: 10px;
        }

        .attachment-item {
            margin-bottom: 10px;
            padding: 8px;
            
        }

        .attachment-image {
            max-width: 400px;
            max-height: 700px;
            margin: 5px 0;
            border: 1px solid #ccc;
        }

        .attachment-info {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }

        /* Stili per i dettagli */
        .details-section {
            margin-top: 20px;
            margin-bottom: 20px;
        }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 10px;
        }

        .detail-item {
            margin-bottom: 8px;
        }

        .detail-label {
            font-weight: bold;
            color: #333;
            font-size: 11px;
        }

        .detail-value {
            color: #666;
            font-size: 11px;
        }

        .detail-checkbox {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 1px solid #333;
            margin-right: 5px;
            text-align: center;
            line-height: 10px;
            font-size: 8px;
        }

        .detail-checkbox.checked {
            background-color: #333;
            color: white;
        }
    </style>
</head>

<body>
    <!-- Header con informazioni azienda e documento -->
    <div class="header">
        <div class="company-info">
            @if($azienda)
            <h1>{{ $azienda->ragione_sociale ?? 'Nome Azienda' }}</h1>
            @if($aziendaIndirizzi->count() > 0)
            @php $indirizzo = $aziendaIndirizzi->first(); @endphp
            <p>
                {{ $indirizzo->indirizzo ?? '' }}<br>
                {{ $indirizzo->cap ?? '' }} {{ $indirizzo->comune ?? '' }} ({{ $indirizzo->provincia ?? '' }})<br>
                Tel: {{ $indirizzo->telefono ?? '' }}<br>
                Email: {{ $azienda->pec ?? '' }}
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
            <p>CF: {{ $document->entity->codice_fiscale }}</p>
            @endif
            @if($document->entity->email)
            <p>Email: {{ $document->entity->email }}</p>
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

    <!-- Dettagli specifici -->
    @if($document->dettagli && ($document->dettagli->data_evasione || $document->dettagli->mod_poltrona || $document->dettagli->quantita || $document->dettagli->fianchi_finali || $document->dettagli->interasse_cm || $document->dettagli->largh_bracciolo_cm || $document->dettagli->rivestimento || $document->dettagli->ricamo_logo || $document->dettagli->pendenza || $document->dettagli->fissaggio_pavimento || $document->dettagli->montaggio))
    <div class="section details-section" style="margin-top: 25px;">
        <div class="section-title">DETTAGLI TECNICI</div>
        <div class="details-grid">
            
            @if($document->dettagli->mod_poltrona)
            <div class="detail-item">
                <div class="detail-label">Modello Poltrona:</div>
                <div class="detail-value">{{ $document->dettagli->mod_poltrona }}</div>
            </div>
            @endif

            @if($document->dettagli->quantita)
            <div class="detail-item">
                <div class="detail-label">Quantità:</div>
                <div class="detail-value">{{ $document->dettagli->quantita }}</div>
            </div>
            @endif

            @if($document->dettagli->fianchi_finali)
            <div class="detail-item">
                <div class="detail-label">Fianchi Finali:</div>
                <div class="detail-value">{{ $document->dettagli->fianchi_finali }}</div>
            </div>
            @endif

            @if($document->dettagli->interasse_cm)
            <div class="detail-item">
                <div class="detail-label">Interasse:</div>
                <div class="detail-value">{{ $document->dettagli->interasse_cm }} cm</div>
            </div>
            @endif

            @if($document->dettagli->largh_bracciolo_cm)
            <div class="detail-item">
                <div class="detail-label">Larghezza Bracciolo:</div>
                <div class="detail-value">{{ $document->dettagli->largh_bracciolo_cm }} cm</div>
            </div>
            @endif

            @if($document->dettagli->rivestimento)
            <div class="detail-item">
                <div class="detail-label">Rivestimento:</div>
                <div class="detail-value">{{ $document->dettagli->rivestimento }}</div>
            </div>
            @endif
        </div>

        <!-- Opzioni booleane -->
        @if($document->dettagli->ricamo_logo || $document->dettagli->pendenza || $document->dettagli->fissaggio_pavimento || $document->dettagli->montaggio)
        <div style="margin-top: 15px; border-top: 1px solid #eee; padding-top: 10px;">
            <div class="detail-label" style="margin-bottom: 8px;">Opzioni Aggiuntive:</div>
            <div style="display: flex; flex-wrap: wrap; gap: 15px;">
                @if($document->dettagli->ricamo_logo)
                <div class="detail-item" style="display: flex; align-items: center;">
                    <span class="detail-checkbox checked">✓</span>
                    <span class="detail-label" style="margin-left: 5px;">Ricamo Logo</span>
                </div>
                @endif
                @if($document->dettagli->pendenza)
                <div class="detail-item" style="display: flex; align-items: center;">
                    <span class="detail-checkbox checked">✓</span>
                    <span class="detail-label" style="margin-left: 5px;">Pendenza</span>
                </div>
                @endif
                @if($document->dettagli->fissaggio_pavimento)
                <div class="detail-item" style="display: flex; align-items: center;">
                    <span class="detail-checkbox checked">✓</span>
                    <span class="detail-label" style="margin-left: 5px;">Fissaggio Pavimento</span>
                </div>
                @endif
                @if($document->dettagli->montaggio)
                <div class="detail-item" style="display: flex; align-items: center;">
                    <span class="detail-checkbox checked">✓</span>
                    <span class="detail-label" style="margin-left: 5px;">Montaggio</span>
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>
    @endif

    <!-- Tabella elementi -->
    <div class="section">
        <!-- <div class="section-title">ELEMENTI ORDINATI</div> -->
        <table class="table">
            <thead>
                <tr>
                    <th>Codice</th>
                    <th>Componenti</th>
                    <th>Fornitore</th>
                    <th>Rif.</th>
                    <th class="text-center">Q.tà</th>
                    <th class="text-center">U.M.</th>
                    <th class="text-right">Prezzo Unit.</th>
                    <th class="text-center">IVA</th>
                    <th class="text-right">Importo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($elementiPerCategoria as $categoria => $prodotti)
                <tr>
                    <td colspan="9" style="font-weight: bold; border-top: 2px solid #000;">
                        {{ strtoupper($categoria) }}
                    </td>
                </tr>
                @foreach($prodotti as $elemento)
                <tr>
                    <td>
                        @if($elemento['tipo'] === 'merci' || $elemento['tipo'] === 'servizi')
                        {{ $elemento['codice'] ?? '-' }}
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

    <!-- Allegati -->
    @if($document->media && $document->media->count() > 0)
    <div class="section attachments">
        <div class="section-title">ALLEGATI</div>
        <div class="attachments-list">
            @foreach($document->media as $media)
            <div class="attachment-item">
                
                
                @if(str_starts_with($media->mime_type, 'image/') && isset($media->base64_data))
                <!-- Mostra l'immagine se è un file immagine -->
                <img src="data:{{ $media->mime_type }};base64,{{ $media->base64_data }}" 
                     alt="{{ $media->name }}" 
                     class="attachment-image">
                @elseif(str_starts_with($media->mime_type, 'image/'))
                <p style="color: #999; font-style: italic;">Immagine non disponibile</p>
                @else
                <!-- Per file non immagine, mostra un'icona o indicazione -->
                <p style="color: #666; font-style: italic;">
                    📎 File allegato: {{ $media->extension }} 
                    @if(str_starts_with($media->mime_type, 'application/pdf'))
                        (PDF)
                    @elseif(str_starts_with($media->mime_type, 'application/'))
                        (Documento)
                    @else
                        ({{ $media->mime_type }})
                    @endif
                </p>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>Documento generato automaticamente il {{ now()->format('d/m/Y H:i') }}</p>
        <p>Ordine Vendita {{ $document->numero }} - Pagina 1 di 1</p>
    </div>
</body>

</html>