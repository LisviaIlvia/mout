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
		'order' => [ 'key' => 'data', 'order' => 'asc' ],
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
		$document = $this->resolveModel($id);
		
		// Carica le relazioni necessarie
		$document->load([
			'entity',
			'indirizzo',
			'products.product.aliquotaIva',
			'products.product.categories',
			'altro.aliquotaIva',
			'descrizioni',
			'dettagli'
		]);

		// Prepara i dati per il template
		$data = [
			'document' => $document,
			'elementi' => $this->getElementi($document),
			'azienda' => \App\Models\Azienda::first(),
			'aziendaIndirizzi' => \App\Models\AziendaIndirizzo::where('azienda_id', 1)->get(),
		];

		// Genera il PDF
		$pdf = Pdf::view('pdf.ordine-vendita', $data)
			->format('a4')
			->margins(15, 15, 15, 15)
			->name('ordine-vendita-' . $document->numero . '.pdf');

		return $pdf->download();
	}
}
