<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Base\AbstractDocumentController;
use App\Exports\OrdineAcquistoExport;

class OrdineAcquistoController extends AbstractDocumentController
{
	protected string $prefix_code = 'ODA';
	protected string $pattern = 'ordini-acquisto';
	protected array $intestatari = ['fornitori'];
	protected array $tipi_intestatari = [
		['title' => 'Fornitori', 'value' => 'fornitori']
	];
	protected bool $spedizione_active = false;
	protected bool $metodo_pagamento_active = false;
	protected bool $rate_active = false;
	protected bool $dettagli_active = false;
	protected bool $activeYear = false;
	protected bool $export = true;
	protected bool $pdf = false;
	protected bool $clone = false;
	protected bool $magic = false;
	
	protected array $indexSetup = [
		'plural' => 'Ordini Acquisto',
		'single' => 'Ordine Acquisto',
		'type' => 'm',
		'icon' => 'custom:ordini-acquisto',
		'order' => [ 'key' => 'data', 'order' => 'asc' ],
		'nameDialog' => 'numero',
		'headers' => [
			['title' => 'Numero', 'key' => 'numero', 'sortable' => true],
			['title' => 'Data', 'key' => 'data', 'sortable' => true],
			['title' => 'Mittente', 'key' => 'mittente', 'sortable' => true],
			['title' => 'Imponibile', 'key' => 'imponibile', 'sortable' => true],
			['title' => 'Azioni', 'key' => 'actions', 'sortable' => false, 'align' => 'end']
		]
	];
	
	protected array $exportSetup = [
		'class' => OrdineAcquistoExport::class
	];
}