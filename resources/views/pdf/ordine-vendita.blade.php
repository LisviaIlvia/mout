<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ordine Vendita {{ $document->numero }}</title>
    <style>
        @page {
            size: A3 landscape;
            margin: 20mm 15mm 20mm 15mm;

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

        /* Layout principale */
        .container {
            width: 100%;
            max-width: 100%;
        }

        /* Layout principale */
        .main-layout {
            display: flex;
            gap: 10px;
            min-height: 600px;
            /* margin-top: 10px; */
        }

        .left-column {
            flex: 3;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .right-column {
            flex: 2;
            padding: 10px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #e0e0e0;
            height: 820px;
        }

        /* Dettagli tecnici e note */
        .details-section {
            display: flex;
            gap: 10px;
        }

        .details-table-container {
            flex: 1;
        }

        .notes-container {
            flex: 1;
            padding: 10px;
            background: #fafbfc;
            width: 20%;
            border: 1px solid #e0e0e0;
        }

        .notes-title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .notes-text {
            font-size: 11px;
        }

        /* Tabella componenti */
        .components-table-container {
            flex: 1;
            margin-top: 10px;
            margin-bottom: 200px;
        }

        /* Tabella prodotti */
        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }

        .table th {
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            padding: 4px;
            font-weight: bold;
            font-size: 9px;
        }

        .table td {
            border: 1px solid #ddd;
            padding: 3px;
            font-size: 9px;
        }

        .rif,
        .qt {
            width: 7%;
            text-align: center;
        }

        .componenti,
        .fornitore,
        .note {
            text-align: left;
        }

        .text-center {
            text-align: center;
        }

        .categoria-header {
            background-color: #e0e0e0;
            font-weight: bold;
            font-size: 10px;
        }

        /* Immagine esploso */
        .esploso-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            margin: 0 auto;
            display: block;
        }

        .esploso-placeholder {
            color: #999;
            font-style: italic;
        }

        /* Sezione firme */
       

        .signatures-section {
            display: flex;
            margin-top: 50px;
            min-height: 60px;
        }

        .signature-column {
            flex: 1;
            text-align: center;
            padding: 0 24px;
        }

        .signature-title {
            font-weight: bold;
            font-size: 10px;
            margin-bottom: 10px;
            letter-spacing: 0.5px;
            color: #222;
        }

        .signature-line {
            border-bottom: 1.5px solid #222;
            width: 35%;
            margin: 0px auto 0 auto;
            height: 32px;
        }
    </style>
</head>

<body>
    <div class="container">

        <!-- LAYOUT PRINCIPALE: CONTENUTO E IMMAGINE ESPLOSO -->
        <div class="main-layout">
            <!-- COLONNA SINISTRA: CONTENUTO (60%) -->
            <div class="left-column">
                <!-- DETTAGLI TECNICI E NOTE -->
                <div class="details-section">
                    <!-- Tabella dettagli tecnici -->
                    <div class="details-table-container">
                        <table class="table">
                            <tbody>
                                <!-- PRIMA RIGA: Dettagli principali -->
                                <tr>
                                    <th>MOD. POLTRONA:</th>
                                    <td>{{ $document->dettagli->mod_poltrona ?? '' }}</td>
                                    <th>QUANTITA'</th>
                                    <td>{{ $document->dettagli->quantita ?? '' }}</td>
                                </tr>
                                <tr>
                                    <th>FIANCHI FINALI</th>
                                    <td>{{ $document->dettagli->fianchi_finali ?? '' }}</td>
                                    <th>INTERASSE CM</th>
                                    <td>{{ $document->dettagli->interasse_cm ?? '' }}</td>
                                </tr>
                                <tr>
                                    <th>LARGH. BRACCIOLO CM</th>
                                    <td>{{ $document->dettagli->largh_bracciolo_cm ?? '' }}</td>
                                    <th>RIVESTIMENTO</th>
                                    <td>{{ $document->dettagli->rivestimento ?? '' }}</td>
                                </tr>

                                <!-- SECONDA RIGA: Dettagli aggiuntivi -->
                                <tr>
                                    <th>RICAMO LOGO</th>
                                    <td>@if($document->dettagli->ricamo_logo) SI @else NO @endif</td>
                                    <th>PENDENZA</th>
                                    <td>@if($document->dettagli->pendenza) SI @else NO @endif</td>
                                </tr>
                                <tr>
                                    <th>FISSAGGIO A PAV.</th>
                                    <td>@if($document->dettagli->fissaggio_pavimento) SI @else NO @endif</td>
                                    <th>MONTAGGIO</th>
                                    <td>@if($document->dettagli->montaggio) SI @else NO @endif</td>
                                </tr>
                                <tr>
                                    <th>DATA DI EVASIONE PREVISTA</th>
                                    <td>{{ $document->dettagli->data_evasione ? \Carbon\Carbon::parse($document->dettagli->data_evasione)->format('d/m/Y') : '' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Note -->
                    <div class="notes-container">
                        <div class="notes-title">Note:</div>
                        <div class="notes-text">{{ $document->note ?? '' }}</div>
                    </div>
                </div>

                <!-- TABELLA COMPONENTI -->
                <div class="components-table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="componenti">COMPONENTI</th>
                                <th class="fornitore">FORNITORE</th>
                                <th class="rif">RIF. DIS.</th>
                                <th class="qt">Q.TA'</th>
                                <th class="note">NOTE</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($elementiPerCategoria as $categoria => $prodotti)
                            <tr>
                                <td colspan="7" class="categoria-header">
                                    {{ strtoupper($categoria) }}
                                </td>
                            </tr>
                            @foreach($prodotti as $elemento)
                            @if($elemento['tipo'] !== 'descrizione')
                            <tr>
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
                                <td class="text-center">{{ $elemento['quantita'] ?? '-' }}</td>
                                <td class="text-center">{{ $elemento['riferimento'] ?? '-' }}</td>
                                <td>{{ $elemento['note'] ?? '-' }}</td>
                            </tr>
                            @endif
                            @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- COLONNA DESTRA: IMMAGINE ESPLOSO (40%) -->
            <div class="right-column">
                @if($document->media && $document->media->count() > 0)
                @php
                $esplosoImage = $document->media->first(function($media) {
                return str_starts_with($media->mime_type, 'image/');
                });
                @endphp
                @if($esplosoImage && isset($esplosoImage->base64_data))
                <img src="data:{{ $esplosoImage->mime_type }};base64,{{ $esplosoImage->base64_data }}"
                    alt="Immagine esploso" class="esploso-image" />
                @else
                <p class="esploso-placeholder">Immagine esploso non disponibile</p>
                @endif
                @else
                <p class="esploso-placeholder">Nessuna immagine esploso caricata</p>
                @endif
            </div>
        </div>

        <!-- FIRME -->
            <div class="signatures-section">
            <div class="signature-column">
                <div class="signature-title">FIRMA UFF.ACQUISTI</div>
                <div class="signature-line"></div>
            </div>
            <div class="signature-column">
                <div class="signature-title">FIRMA RESP.PRODUZIONE</div>
                <div class="signature-line"></div>
            </div>
        </div>
    </div>
</body>

</html>