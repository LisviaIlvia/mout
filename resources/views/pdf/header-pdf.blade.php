<style>
    /* Header PDF */
    .page-header {
        width: 100%;
        /* border-bottom: 2px solid #333; */
        padding: 0 60px 10px 60px;
    }

    .header-container {
        display: flex;
        align-items: flex-end;
        border: 3px solid #e0e0e0;
        border-radius: 4px;
        padding: 8px 12px;
        position: relative;
    }

    .header-bg-element {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #e0e0e0;
        z-index: -1;
        border-radius: 4px;
    }

    .header-title-section {
        flex: 2;
    }

    .header-title {
        font-size: 20px;
        font-weight: bold;
        letter-spacing: 1px;
        color: #222;
    }

    .header-details-section {
        flex: 3;
        display: flex;
        justify-content: flex-end;
        gap: 18px;
    }

    .header-detail-item {
        /* Container per ogni dettaglio */
    }

    .header-detail-label {
        font-size: 9px;
        color: #666;
    }

    .header-detail-value {
        font-size: 12px;
        font-weight: bold;
    }

    .header-detail-value.page-info {
        color: #333;
    }
</style>

<div id="page-header" class="page-header">
    <div class="header-container">
        <div class="header-bg-element"></div>
        <div class="header-title-section">
            <div class="header-title">
                @if(isset($document->type) && $document->type === 'ordini-vendita')
                    ORDINE DI VENDITA
                @else
                    ORDINE DI ACQUISTO
                @endif
            </div>
        </div>
        <div class="header-details-section">
            <div class="header-detail-item">
                @if(isset($document->type) && $document->type === 'ordini-vendita')
                <div class="header-detail-label">CLIENTE</div>
                <div class="header-detail-value">{{ $azienda->ragione_sociale }}</div>
                @endif
            </div>
            <div class="header-detail-item">
                <div class="header-detail-label">NUMERO DOCUMENTO</div>
                <div class="header-detail-value">{{ $document->numero }}</div>
            </div>
            <div class="header-detail-item">
                <div class="header-detail-label">DATA</div>
                <div class="header-detail-value">
                    {{ \Carbon\Carbon::parse($document->data)->format('d/m/Y') }}
                </div>
            </div>
            <div class="header-detail-item">
                <div class="header-detail-label">PAGINA</div>
                <div class="header-detail-value page-info">
                    Pagina @pageNumber di @totalPages
                </div>
            </div>
        </div>
    </div>
</div>