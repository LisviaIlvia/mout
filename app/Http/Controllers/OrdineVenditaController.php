<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Base\AbstractDocumentController;
use App\Exports\OrdineVenditaExport;
use App\Imports\OrdineVenditaImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Spatie\LaravelPdf\Facades\Pdf;
use Illuminate\Support\Facades\Log;

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
	protected bool $import = true;
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

	// Configurazione PDF personalizzata per ordini vendita
	protected array $pdfSetup = [
		'template' => 'pdf.ordine-vendita',
		'format' => 'a3',
		'landscape' => true,
		'margins' => [15, 15, 15, 15],
		'filename_prefix' => 'ordine-vendita'
	];

	// /**
	// OPZIONALE
	//  * Personalizza i componenti PDF per ordini vendita.
	//  * Sovrascrive il metodo dell'AbstractDocumentController.
	//  *
	//  * @return array
	//  */
	// protected function setComponentsPdf()
	// {
	// 	return [
	// 		'pdf' => 'documents/OrdiniVenditaPdf',
	// 		'content' => 'documents/OrdiniVenditaPdfContent'
	// 	];
	// }

	/**
	 * Importa ordini vendita da file CSV
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function import(Request $request)
	{
		try {
			// Validazione del file
			$request->validate([
				'csv_file' => 'required|file|mimes:csv,txt|max:10240', // Max 10MB
				'year' => 'nullable|integer|min:2020|max:2030'
			], [
				'csv_file.required' => 'Il file CSV è obbligatorio',
				'csv_file.file' => 'Il file caricato non è valido',
				'csv_file.mimes' => 'Il file deve essere in formato CSV',
				'csv_file.max' => 'Il file non può superare 10MB'
			]);

			$year = $request->input('year', date('Y'));
			$import = new OrdineVenditaImport($year);

			// Esegui l'importazione
			Excel::import($import, $request->file('csv_file'));

			// Ottieni i risultati
			$importedCount = $import->getImportedCount();
			$errors = $import->getErrors();

			if (!empty($errors)) {
				return response()->json([
					'success' => false,
					'message' => 'Importazione completata con errori',
					'imported_count' => $importedCount,
					'errors' => $errors
				], 422);
			}

			return response()->json([
				'success' => true,
				'message' => "Importazione completata con successo. Importati {$importedCount} ordini vendita.",
				'imported_count' => $importedCount
			]);

		} catch (\Exception $e) {
			Log::error('Errore importazione CSV ordini vendita: ' . $e->getMessage());
			
			return response()->json([
				'success' => false,
				'message' => 'Errore durante l\'importazione: ' . $e->getMessage()
			], 500);
		}
	}

}
