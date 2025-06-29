<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Base\AbstractDocumentController;
use App\Exports\OrdineVenditaExport;
use Spatie\LaravelPdf\Facades\Pdf;

class OrdineVenditaController extends AbstractDocumentController
{
	protected string $prefix_code = 'ODV';
	protected string $pattern = 'ordini-vendita';
	protected array $intestatari = ['clienti'];
	protected array $tipi_intestatari = [
		['title' => 'Cliente', 'value' => 'clienti']
	];
	protected bool $spedizione_active = false;
	protected bool $metodo_pagamento_active = false;
	protected bool $rate_active = false;
	protected bool $dettagli_active = true;
	protected bool $activeYear = false;
	protected bool $export = true;
	protected bool $pdf = true;
	protected bool $clone = true;
	protected bool $magic = true;

	protected array $indexSetup = [
		'plural' => 'Ordini Vendita',
		'single' => 'Ordine Vendita',
		'type' => 'm',
		'icon' => 'custom:ordini-vendita',
		'order' => ['key' => 'data', 'order' => 'asc'],
		'nameDialog' => 'numero',
		'headers' => [
			['title' => 'Numero', 'key' => 'numero', 'sortable' => true],
			['title' => 'Data', 'key' => 'data', 'sortable' => true],
			['title' => 'Destinatario', 'key' => 'destinatario', 'sortable' => true],
			['title' => 'Imponibile', 'key' => 'imponibile', 'sortable' => true],
			['title' => 'Azioni', 'key' => 'actions', 'sortable' => false, 'align' => 'end']
		]
	];

	protected array $types_relation = [
		['title' => 'DDT Uscita', 'value' => 'DdtUscitaController', 'type' => 'ddt-uscita'],
		['title' => 'Fattura Proforma', 'value' => 'FatturaProformaController', 'type' => 'fatture-proforma'],
		['title' => 'Fattura Vendita', 'value' => 'FatturaVenditaController', 'type' => 'fatture-vendita']
	];

	protected array $exportSetup = [
		'class' => OrdineVenditaExport::class
	];

	/**
	 * Genera il PDF dell'ordine vendita
	 *
	 * @param int $id ID dell'ordine vendita
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function pdf($id)
	{
		try {
			// 1. Recupera il modello dell'ordine vendita tramite l'ID
			//    Usa un metodo del controller base per trovare il record (es. Document::findOrFail($id))
			$document = $this->resolveModel($id);

			// 2. Carica tutte le relazioni necessarie per il PDF in un'unica query (Eager Loading)
			//    - entity: il cliente destinatario
			//    - indirizzo: indirizzo di spedizione/fatturazione
			//    - products.product.aliquotaIva: prodotti, con relativa aliquota IVA
			//    - products.product.categories: categorie dei prodotti
			//    - altro.aliquotaIva: altri elementi con aliquota IVA
			//    - descrizioni: eventuali righe descrittive
			//    - dettagli: dettagli tecnici dell'ordine
			//    - media: allegati (es. immagini)
			$document->load([
				'entity',
				'indirizzo',
				'products.product.aliquotaIva',
				'products.product.categories',
				'altro.aliquotaIva',
				'descrizioni',
				'dettagli',
				'media'
			]);

			// 3. Prepara le immagini degli allegati per l'inclusione nel PDF
			//    - Per ogni media di tipo immagine, carica il file dal filesystem
			//    - Codifica l'immagine in base64 e la aggiunge come proprietà all'oggetto media
			$document->media->each(function ($media) {
				if (str_starts_with($media->mime_type, 'image/')) {
					$imagePath = storage_path('app/private/media/ordini-vendita/' . $media->name);
					if (file_exists($imagePath)) {
						$media->base64_data = base64_encode(file_get_contents($imagePath));
					}
				}
			});

			// 4. Recupera tutti gli elementi dell'ordine (prodotti, altro, descrizioni, ecc.)
			//    - Usa un metodo helper del controller base
			$elementi = $this->getElementi($document);

			// 5. Raggruppa gli elementi per categoria (es. per visualizzazione ordinata nel PDF)
			$elementiPerCategoria = $elementi->groupBy(fn($item) => $item['categoria']['nome'] ?? 'Senza categoria');

			// 6. Prepara i dati da passare al template Blade del PDF
			//    - document: il modello ordine con tutte le relazioni
			//    - elementi: tutti gli elementi dell'ordine
			//    - elementiPerCategoria: elementi raggruppati per categoria
			//    - azienda: dati dell'azienda (mittente)
			//    - aziendaIndirizzi: indirizzi dell'azienda
			$data = [
				'document' => $document,
				'elementi' => $elementi,
				'elementiPerCategoria' => $elementiPerCategoria,
				'azienda' => \App\Models\Azienda::first(),
				'aziendaIndirizzi' => \App\Models\AziendaIndirizzo::where('azienda_id', 1)->get(),
			];

			// 7. Genera il PDF usando la facade Spatie\LaravelPdf
			//    - Usa il template Blade 'pdf.ordine-vendita'
			//    - Passa i dati preparati
			//    - Imposta formato A4 e margini
			//    - Imposta il nome del file PDF
			$pdf = Pdf::view('pdf.ordine-vendita', $data)
				->format('a4')
				->margins(15, 15, 15, 15)
				->name('ordine-vendita-' . $document->numero . '.pdf');

			// 8. Restituisce il PDF come download al browser dell'utente
			return $pdf->download();
			
		} catch (\Exception $e) {
			// 9. Gestione errori: logga l'errore e restituisce una risposta JSON con errore 500
			\Log::error('Errore nella generazione PDF ordine vendita ID ' . $id . ': ' . $e->getMessage());
			\Log::error('Stack trace: ' . $e->getTraceAsString());
			
			return response()->json([
				'error' => 'Errore nella generazione del PDF',
				'message' => $e->getMessage()
			], 500);
		}
	}
}
