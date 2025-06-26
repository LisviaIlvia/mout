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
			$document = $this->resolveModel($id);

			// Carica le relazioni necessarie
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

			// Prepara le immagini per il PDF
			$document->media->each(function ($media) {
				if (str_starts_with($media->mime_type, 'image/')) {
					$imagePath = storage_path('app/private/media/ordini-vendita/' . $media->name);
					if (file_exists($imagePath)) {
						$media->base64_data = base64_encode(file_get_contents($imagePath));
					}
				}
			});

			// Recupera e raggruppa gli elementi
			$elementi = $this->getElementi($document);
			$elementiPerCategoria = $elementi->groupBy(fn($item) => $item['categoria']['nome'] ?? 'Senza categoria');

			// Prepara i dati per il template
			$data = [
				'document' => $document,
				'elementi' => $elementi,
				'elementiPerCategoria' => $elementiPerCategoria,
				'azienda' => \App\Models\Azienda::first(),
				'aziendaIndirizzi' => \App\Models\AziendaIndirizzo::where('azienda_id', 1)->get(),
			];

			// Genera il PDF
			$pdf = Pdf::view('pdf.ordine-vendita', $data)
				->format('a4')
				->margins(15, 15, 15, 15)
				->name('ordine-vendita-' . $document->numero . '.pdf');

			return $pdf->download();
			
		} catch (\Exception $e) {
			\Log::error('Errore nella generazione PDF ordine vendita ID ' . $id . ': ' . $e->getMessage());
			\Log::error('Stack trace: ' . $e->getTraceAsString());
			
			return response()->json([
				'error' => 'Errore nella generazione del PDF',
				'message' => $e->getMessage()
			], 500);
		}
	}
}
