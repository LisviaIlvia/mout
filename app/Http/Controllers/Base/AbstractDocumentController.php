<?php

namespace App\Http\Controllers\Base;

use App\Http\Controllers\Base\AbstractCrudController;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Helpers\FunctionsHelper;
use App\Models\Document;
use App\Models\DocumentIndirizzo;
use App\Models\DocumentProduct;
use App\Models\DocumentAltro;
use App\Models\DocumentDescrizione;
use App\Models\DocumentDettagli;
use App\Models\AliquotaIva;
use App\Models\Entity;
use App\Models\Product;

use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\Browsershot\Browsershot;
use Spatie\LaravelPdf\Enums\Unit;

abstract class AbstractDocumentController extends AbstractCrudController
{
	protected string $prefix_code = '';
	protected bool $entrata =  false;
	protected string $pattern;
	protected array $intestatari;
	protected array $tipi_intestatari;
	protected string $model = Document::class;
	protected bool $spedizione_active = false;
	protected bool $trasporto_active = false;
	protected bool $metodo_pagamento_active = false;
	protected bool $rate_active = false;
    protected bool $dettagli_active = false;
	protected bool $activeYear = true;
	protected array $stati = ['Aperto', 'Chiuso'];
	protected string $stato_iniziale = 'Aperto';
	protected array $types_relation = [];

	protected array $dialogSetup = [
		'create' => [
			'width' => '100%',
			'fullscreen' => true,
			'scrim' => false
		],
		'show' => [
			'width' => '100%',
			'fullscreen' => true,
			'scrim' => false
		],
		'edit' => [
			'width' => '100%',
			'fullscreen' => true,
			'scrim' => false
		]
	];

	public function indexDocFilter(Collection $collection)
	{
		return $this->getPropsIndex($collection);
	}

	protected function getCollectionIndex()
	{
		return $this->model::getDocuments($this->pattern)->get();
	}

	protected function setComponents()
	{
		return [
			'index' => 'Crud/CrudIndex',
			'create' => 'Crud/CrudCreate',
			'show' => 'documents/DocumentsShow',
			'edit' => 'Crud/CrudEdit',
			'content' => 'documents/DocumentsContent'
		];
	}

	protected function beforeStore(&$validatedData)
	{
		$validatedData['type'] =  $this->pattern;

		// if(isset($validatedData['metodo_pagamento_id'])) {
		// 	if($validatedData['metodo_pagamento_id'] == '0' || $validatedData['metodo_pagamento_id'] == null) unset($validatedData['metodo_pagamento_id']);
		// }

		// if(isset($validatedData['conto_bancario_id'])) {
		// 	if($validatedData['conto_bancario_id'] == '0' || $validatedData['conto_bancario_id'] == null) unset($validatedData['conto_bancario_id']);
		// }

	}

	protected function afterStore(&$object, $validatedData)
	{
		DocumentIndirizzo::create(array_merge(['document_id' => $object->id], $validatedData['indirizzo']));

		// if(array_key_exists('rate', $validatedData) && is_array($validatedData['rate']) && !empty($validatedData['rate'])) {
		// 	foreach ($validatedData['rate'] as $rata) {
		// 		$object->rate()->create([
		// 			'data' => isset($rata['data']) ? $rata['data'] : $validatedData['data'],
		// 			'percentuale' => $rata['percentuale'],
		// 			'importo' => $rata['importo']
		// 		]);
		// 	}
		// }

		if (array_key_exists('allegati', $validatedData) && is_array($validatedData['allegati']) && !empty($validatedData['allegati'])) {
			$pattern_name = $this->pattern;

			$files = request()->file('allegati');
			foreach ($files as $index => $value) {
				$file = $value['file'];
				$extension = $file->getClientOriginalExtension();
				$filename = $pattern_name . '-num' . $validatedData['numero'] . '-' . $validatedData['data'] . '-' . $index . '.' . $extension;
				$file->storeAs('private/media/' . $pattern_name, $filename);

				$object->media()->create([
					'name' => $filename,
					'extension' => $file->getClientOriginalExtension(),
					'mime_type' => $file->getClientMimeType(),
					'url' => '/media/' . $pattern_name . '/' . $filename,
					'relationable_id' => $object->id,
					'relationable_type' => get_class($object)
				]);
			}
		}

		if (!empty($validatedData['elementi'])) {
			foreach ($validatedData['elementi'] as $key => $element) {

				switch($element['tipo']) {
					case 'merci':
					case 'servizi':
						DocumentProduct::create([
							'document_id' => $object->id,
							'product_id' => request()->elementi[$key]['id'],
							'type' =>  $element['tipo'],
							'quantita' => $element['quantita'],
							'prezzo' => $element['prezzo'],
							// 'tipo_sconto' => $element['tipo_sconto'],
							// 'sconto' => $element['sconto'],
							'aliquota_iva_id' => $element['iva']['aliquota_iva_id'],
							'order' => $key
						]);
						break;
					case 'altro':
						DocumentAltro::create([
							'document_id' => $object->id,
							'nome' => $element['nome'],
							'quantita' => $element['quantita'],
							'unita_misura' => request()->elementi[$key]['unita_misura'],
							'prezzo' => $element['prezzo'],
							// 'sconto' => $element['sconto'],
							// 'tipo_sconto' => $element['tipo_sconto'],
							'aliquota_iva_id' => $element['iva']['aliquota_iva_id'],
							'order' => $key
						]);
						break;
					case 'descrizione':
						DocumentDescrizione::create([
							'document_id' => $object->id,
							'descrizione' => $element['descrizione'],
							'order' => $key
						]);
						break;
				}
			}
		}

		// if($this->spedizione_active === true) {
		// 	if (isset($validatedData['spedizione']) && !empty($validatedData['spedizione'])) {
		// 		if($validatedData['spedizione']['spedizione_id'] != 0) {
		// 			DocumentSpedizione::create([
		// 				'document_id' => $object->id,
		// 				'prezzo' => $validatedData['spedizione']['prezzo'],
		// 				'sconto' => $validatedData['spedizione']['sconto'],
		// 				'spedizione_id' => $validatedData['spedizione']['spedizione_id'],
		// 				'aliquota_iva_id' => $validatedData['spedizione']['iva']['aliquota_iva_id']

		// 			]);
		// 		}
		// 	}
		// }

		// if($this->trasporto_active === true) {
		// 	if(isset($validatedData['trasporto']) && !empty($validatedData['trasporto'])) {
		// 		DocumentTrasporto::create([
		// 			'document_id' => $object->id,
		// 			'colli' => $validatedData['trasporto']['colli'] ?? null,
		// 			'peso' => $validatedData['trasporto']['peso'] ?? null,
		// 			'causale' => $validatedData['trasporto']['causale'] ?? null,
		// 			'porto' => $validatedData['trasporto']['porto'] ?? null,
		// 			'a_cura' => $validatedData['trasporto']['a_cura'] ?? null,
		// 			'vettore' => $validatedData['trasporto']['vettore'] ?? null,
		// 			'annotazioni' => $validatedData['trasporto']['annotazioni'] ?? null
		// 		]);
		// 	}
		// }

        // if($this->dettagli_active === true) {
        //     if (isset($validatedData['dettagli']) && !empty($validatedData['dettagli'])) {
        //         $dettaglio = $validatedData['dettagli'];
        //         DocumentDettagli::create([
        //             'document_id' => $object->id,
        //             'data_evasione' => $dettaglio['data_evasione'] ?? null,
        //             'mod_poltrona' => $dettaglio['mod_poltrona'] ?? null,
        //             'quantita' => $dettaglio['quantita'] ?? null,
        //             'fianchi_finali' => $dettaglio['fianchi_finali'] ?? null,
        //             'interasse_cm' => $dettaglio['interasse_cm'] ?? null,
        //             'largh_bracciolo_cm' => $dettaglio['largh_bracciolo_cm'] ?? null,
        //             'rivestimento' => $dettaglio['rivestimento'] ?? null,
        //             'ricamo_logo' => filter_var($dettaglio['ricamo_logo'], FILTER_VALIDATE_BOOLEAN) ?? false,
        //             'pendenza' => filter_var($dettaglio['pendenza'], FILTER_VALIDATE_BOOLEAN) ?? false,
        //             'fissaggio_pavimento' => filter_var($dettaglio['fissaggio_pavimento'], FILTER_VALIDATE_BOOLEAN) ?? false,
        //             'montaggio' => filter_var($dettaglio['montaggio'], FILTER_VALIDATE_BOOLEAN) ?? false
        //         ]);
        //     }
        // }

	}

	protected function beforeUpdate(&$validatedData)
	{
		$validatedData['type'] =  $this->pattern;

		// if(isset($validatedData['metodo_pagamento_id'])) {
		// 	if($validatedData['metodo_pagamento_id'] == '0' || $validatedData['metodo_pagamento_id'] == null) unset($validatedData['metodo_pagamento_id']);
		// }

		// if(isset($validatedData['conto_bancario_id'])) {
		// 	if($validatedData['conto_bancario_id'] == '0' || $validatedData['conto_bancario_id'] == null) unset($validatedData['conto_bancario_id']);
		// }

	}

	protected function afterUpdate(&$object, $validatedData)
	{
		if (isset($validatedData['indirizzo'])) {
			$object->indirizzo()->delete();
			DocumentIndirizzo::create(array_merge(['document_id' => $object->id], $validatedData['indirizzo']));
		}

		if (array_key_exists('allegati', $validatedData) && is_array($validatedData['allegati']) && !empty($validatedData['allegati'])) {
			$pattern_name = $this->pattern;

			// $allegatiIds = array_column($validatedData['allegati'], 'id');
			// foreach ($object->media as $mediaItem) {
			// 	if (!in_array($mediaItem->id, $allegatiIds)) {
			// 		Storage::delete('private/media/' . $pattern_name . '/' . $mediaItem->name);
			// 		$mediaItem->delete();
			// 	}
			// }

			$files = request()->file('allegati');
			foreach ($files as $index => $value) {
				$file = $value['file'];
				$extension = $file->getClientOriginalExtension();
				$filename = $pattern_name . '-num' . $validatedData['numero'] . '-' . $validatedData['data'] . '-' . $index . '.' . $extension;
				$file->storeAs('private/media/' . $pattern_name, $filename);

				$object->media()->create([
					'name' => $filename,
					'extension' => $file->getClientOriginalExtension(),
					'mime_type' => $file->getClientMimeType(),
					'url' => '/media/' . $pattern_name . '/' . $filename,
					'relationable_id' => $object->id,
					'relationable_type' => get_class($object)
				]);
			}
		}

		if (isset($validatedData['elementi'])) {
			$object->products()->delete();
			$object->altro()->delete();
			$object->descrizioni()->delete();

			if (!empty($validatedData['elementi'])) {
				foreach ($validatedData['elementi'] as $key => $element) {
					switch($element['tipo']) {
						case 'merci':
						case 'servizi':
							DocumentProduct::create([
								'document_id' => $object->id,
								'product_id' => request()->elementi[$key]['id'],
								'type' =>  $element['tipo'],
								'quantita' => $element['quantita'],
								'prezzo' => $element['prezzo'],
								'tipo_sconto' => '%',
								'sconto' => 0,
								'aliquota_iva_id' => $element['iva']['aliquota_iva_id'],
								'order' => $key
							]);
							break;
						case 'altro':
							DocumentAltro::create([
								'document_id' => $object->id,
								'nome' => $element['nome'],
								'quantita' => $element['quantita'],
								'unita_misura' => request()->elementi[$key]['unita_misura'],
								'prezzo' => $element['prezzo'],
								'tipo_sconto' => '%',
								'sconto' => 0,
								'aliquota_iva_id' => $element['iva']['aliquota_iva_id'],
								'order' => $key
							]);
							break;
						case 'descrizione':
							DocumentDescrizione::create([
								'document_id' => $object->id,
								'descrizione' => $element['descrizione'],
								'order' => $key
							]);
							break;
					}
				}
			}
		}
	}

	protected function setJsonData(string $type, Model|Collection $object)
	{
		$data = [];

		if ($object instanceof Collection) {
			$data['index'] = [
				'items' => $object->map(function ($item) {
					return [
						'id' => $item->id,
						'numero' => $item->numero,
						'data' => $item->data,
						'mittente' => $this->getEntityName($item),
						'imponibile' => $item->products->sum(function ($product) {
							return $product->quantita * $product->prezzo;
						}) + $item->altro->sum(function ($altro) {
							return $altro->quantita * $altro->prezzo;
						}),
						'stato' => $item->stato,
						'note' => $item->note
					];
				})
			];
		} else {
			$data['main'] = [
				'id' => $object->id,
				'numero' => $object->numero,
				'data' => $object->data,
				'stato' => $object->stato,
				'note' => $object->note,
				'entity_id' => $object->entity_id,
				'entity' => $object->entity,
				'indirizzo' => $object->indirizzo,
				'elementi' => $this->getElementi($object),
				'allegati' => $object->media->map(function ($media) {
					return [
						'id' => $media->id,
						'name' => $media->name,
						'url' => $media->url
					];
				})
			];
		}

		return $data;
	}

	private function getElementi(Document $document)
	{
		$elementi = collect();

		// Aggiungi prodotti
		foreach ($document->products as $product) {
			$elementi->push([
				'id' => $product->id,
				'tipo' => $product->type,
				'product_id' => $product->product_id,
				'nome' => $product->product->nome,
				'quantita' => $product->quantita,
				'prezzo' => $product->prezzo,
				'iva' => [
					'aliquota_iva_id' => $product->aliquota_iva_id,
					'aliquota' => $product->aliquotaIva->aliquota
				]
			]);
		}

		// Aggiungi altro
		foreach ($document->altro as $altro) {
			$elementi->push([
				'id' => $altro->id,
				'tipo' => 'altro',
				'nome' => $altro->nome,
				'quantita' => $altro->quantita,
				'unita_misura' => $altro->unita_misura,
				'prezzo' => $altro->prezzo,
				'iva' => [
					'aliquota_iva_id' => $altro->aliquota_iva_id,
					'aliquota' => $altro->aliquotaIva->aliquota
				]
			]);
		}

		// Aggiungi descrizioni
		foreach ($document->descrizioni as $descrizione) {
			$elementi->push([
				'id' => $descrizione->id,
				'tipo' => 'descrizione',
				'descrizione' => $descrizione->descrizione
			]);
		}

		return $elementi->sortBy('order')->values();
	}

	protected function setOtherData(string $type, Model $object)
	{
		$data['aliquote_iva'] = AliquotaIva::all();
		$data['tipi_intestatari'] = $this->tipi_intestatari;
		$data['intestatari'] = $this->getIntestatari();

		$data['prodotti']['merci'] = Product::merci()->with(['categories', 'aliquotaIva'])->get();
		if($this->trasporto_active === false) $data['prodotti']['servizi'] = Product::servizi()->with(['categories', 'aliquotaIva'])->get();

		// Rimuovi tutti i dati non necessari
		$data['metodi_pagamento'] = collect([]);
		$data['conti_bancari'] = collect([]);
		$data['spedizioni'] = collect([]);
		$data['ricorrenze'] = collect([]);
		
		$data['entrata'] = $this->entrata;
		$data['spedizione_active'] = false;
		$data['trasporto_active'] = false;
		$data['dettagli_active'] = false;
		$data['metodo_pagamento_active'] = false;
		$data['rate_active'] = false;
		$data['stati'] = $this->stati;
		$data['types_relation'] = $this->types_relation;

		// Aggiungi dati per la creazione di un nuovo documento
		if ($type === 'create') {
			$year = now()->year;
			$data['resource'] = [
				'numero' => FunctionsHelper::getLastNumber($this->prefix_code, $this->model, $year)['numero'],
				'interlocutore' => $this->entrata ? 'Fornitore' : 'Cliente',
				'intestatari' => $this->getIntestatari()
			];
		}

		return $data;
	}

	protected function setValidation(Model $object)
	{
		return [
			'rules' => [
				'stato' => 'nullable',
				'data' => 'required',
				'allegati' => 'nullable|array',
				'note' => 'nullable|string',
				'entity_id' => 'required|integer',
				'indirizzo' => 'array|required',
				'elementi' => 'array|required',
				'elementi.*.descrizione' => 'required_unless:elementi.*.tipo,altro,merci,servizi|string',
				'elementi.*.quantita' => 'required_unless:elementi.*.tipo,descrizione|numeric|min:1',
				'elementi.*.iva' => 'required_unless:elementi.*.tipo,descrizione',
				'elementi.*.tipo' => 'required|in:descrizione,altro,merci,servizi',
				'elementi.*.prezzo' => 'required_unless:elementi.*.tipo,descrizione|numeric',
				'elementi.*.id' => 'required_unless:elementi.*.tipo,altro,descrizione|integer', // ID del prodotto per merci/servizi
				'elementi.*.nome' => 'required_unless:elementi.*.tipo,merci,servizi,descrizione|string', // Nome solo per "altro"
			],
			'store' => [
				'numero' => [
					'required',
					'string',
					'max:255',
					Rule::unique('documents', 'numero')
					->whereNull('deleted_at')
				]
			],
			'update' => [
				'numero' => [
					'required',
					'string',
					'max:255',
					Rule::unique('documents', 'numero')
					->ignore($object->id)
					->whereNull('deleted_at')
				]
			],
			'messages' => [
				'numero.required' => 'Il campo numero è obbligatorio.',
				'numero.unique' => 'Il campo numero inserito è già in uso.',
				'data.required' => 'Il campo data è obbligatorio.',
				'entity_id.required' => 'Il campo tipo è obbligatorio.',
				'indirizzo.required' => 'Devi aggiungere almeno un indirizzo.',
				'elementi.required' => 'Devi aggiungere almeno un elemento.',
				'elementi.*.quantita.required_unless' => 'La quantità degli elementi è obbligatoria.',
				'elementi.*.descrizione.required_unless' => 'La descrizione degli elementi è obbligatoria.',
				'elementi.*.tipo.required' => 'Il tipo degli elementi è obbligatorio.',
				'elementi.*.prezzo.required_unless' => 'Il prezzo degli elementi è obbligatorio.',
				'elementi.*.id.required_unless' => 'Il prodotto è obbligatorio per merci e servizi.',
				'elementi.*.nome.required_unless' => 'Il nome è obbligatorio per elementi personalizzati.'
			]
		];
	}

	protected function getJsonData(string $type, Model|Collection $object = null, bool $create = false)
	{
		$object = $object ?? new $this->model;
        $jsonData = $this->setJsonData($type, $object) ?? [];

        $main = $jsonData['main'] ?? [];

		$typeMap = [
			'index' => $jsonData['index'] ?? [],
			'create' => $jsonData['create'] ?? [],
			'show' => $jsonData['show'] ?? [],
			'edit' => $jsonData['edit'] ?? [],
			'store' => $jsonData['store'] ?? [],
			'update' => $jsonData['update'] ?? [],
			'clone' => $jsonData['clone'] ?? [],
			'destroy' => $jsonData['destroy'] ?? []
		];

		$data = $typeMap[$type] ?? [];

		if ($object->exists && in_array($type, ['store', 'update', 'clone'])) {
			$data['actions'] = $this->getAction($object, [
				'show' => 'show',
				'edit' => 'edit',
				'update' => 'edit',
				'destroy' => 'delete',
				'clone' => 'clone',
				'pdf' => 'pdf'
			]);
		}

        return array_merge((array) $main, (array) $data);
	}

	protected function setClone(Model $object)
	{
		$newDocument = $object->replicate();

		$year = Carbon::parse($object->data)->year;

		$newDocument->numero = FunctionsHelper::getLastNumber($this->prefix_code, $this->model, $year)['numero'];
        $newDocument->data = Carbon::now()->toDateString();
        $newDocument->parent_id = $object->id;
        $newDocument->stato = $this->stato_iniziale;
        $newDocument->save();

		if ($object->indirizzo) {
            $newDocument->indirizzo()->create($object->indirizzo->toArray());
        }

		foreach ($object->products as $product) {
            $newDocument->products()->create($product->toArray());
        }

		foreach ($object->altro as $altro) {
            $newDocument->altro()->create($altro->toArray());
        }

        foreach ($object->descrizioni as $descrizione) {
            $newDocument->descrizioni()->create($descrizione->toArray());
        }

		return $newDocument;
	}

	public function magic($id)
	{
		$document = $this->resolveModel($id);

		$relation = $this->getRelation($document);
		$parents = $relation['parents'];
		$children = $relation['children'];

		$children_collection = $document->children()->with(['products', 'altro'])->get();

		$combineQuantitiesAndTotals = function ($items, $key, $quantityKey, $priceKey) {
			return collect($items)
				->groupBy($key)
				->map(function ($group) use ($quantityKey, $priceKey) {
					$quantitySum = $group->sum($quantityKey);
					$totalSum = $group->sum(function ($item) use ($quantityKey, $priceKey) {
						$price = $item[$priceKey] ?? 0;
						return max(0, $price) * ($item[$quantityKey] ?? 0);
					});
					return [
						'quantity' => $quantitySum,
						'total' => $totalSum
					];
				});
		};

		$compareElements = function ($mainElements, $combinedQuantitiesAndTotals, $key, $quantityKey, $priceKey) {
			return collect($mainElements)->map(function ($element) use ($combinedQuantitiesAndTotals, $key, $quantityKey, $priceKey) {
				$combined = $combinedQuantitiesAndTotals->get($element[$key], ['quantity' => 0, 'total' => 0]);
				$combinedQuantity = $combined['quantity'];
				$combinedTotal = $combined['total'];

				$mainQuantity = $element[$quantityKey] ?? 0;
				$mainPrice = $element[$priceKey] ?? 0;
				$mainTotal = max(0, $mainPrice) * $mainQuantity;

				$remainingTotal = max(0, $mainTotal - $combinedTotal);
				$remainingQuantity = ($remainingTotal > 0) ? (int) ceil($remainingTotal / $mainPrice) : max(0, $mainQuantity - $combinedQuantity);

				if ($remainingQuantity > 0 || $remainingTotal > 0) {
					$elementArray = is_array($element) ? $element : $element->toArray();
					$elementArray[$quantityKey] = $remainingQuantity;
					$elementArray['remaining_total'] = $remainingTotal;
					$elementArray['importo'] = $remainingTotal;
					if($remainingQuantity == 1) $elementArray['prezzo'] = $mainPrice;

					return $elementArray;
				}

				return null;
			})->filter();
		};

		$combinedProducts = $combineQuantitiesAndTotals($children_collection->flatMap(function ($child) {
			return $child->products->map(function ($product) {
				return [
					'product_id' => $product->product_id,
					'quantita' => $product->quantita,
					'prezzo' => $product->prezzo
				];
			});
		}), 'product_id', 'quantita', 'prezzo');

		$combinedAltro = $combineQuantitiesAndTotals($children_collection->flatMap(function ($child) {
			return $child->altro->map(function ($altro) {
				return [
					'nome' => $altro->nome,
					'quantita' => $altro->quantita,
					'prezzo' => $altro->prezzo
				];
			});
		}), 'nome', 'quantita', 'prezzo');

		$remainingProducts = $compareElements($document->products, $combinedProducts, 'product_id', 'quantita', 'prezzo');
		$remainingAltro = $compareElements($document->altro, $combinedAltro, 'nome', 'quantita', 'prezzo');

		$remainingElements = $remainingProducts->concat($remainingAltro);

		if ($remainingElements->isNotEmpty()) {
			$newDocument = $this->setClone($document);

			foreach ($remainingElements as $element) {
				if (isset($element['product_id'])) {
					// È un prodotto
					$newDocument->products()->create([
						'product_id' => $element['product_id'],
						'type' => 'merci',
						'quantita' => $element['quantita'],
						'prezzo' => $element['prezzo'],
						'tipo_sconto' => '%',
						'sconto' => 0,
						'aliquota_iva_id' => $element['aliquota_iva_id'] ?? 1,
						'order' => 0
					]);
				} else {
					// È altro
					$newDocument->altro()->create([
						'nome' => $element['nome'],
						'quantita' => $element['quantita'],
						'unita_misura' => $element['unita_misura'] ?? 'NR',
						'prezzo' => $element['prezzo'],
						'tipo_sconto' => '%',
						'sconto' => 0,
						'aliquota_iva_id' => $element['aliquota_iva_id'] ?? 1,
						'order' => 0
					]);
				}
			}

			return response()->json([
				'success' => true,
				'message' => 'Documento clonato con successo',
				'data' => [
					'id' => $newDocument->id,
					'numero' => $newDocument->numero
				]
			]);
		}

		return response()->json([
			'success' => false,
			'message' => 'Nessun elemento rimanente da clonare'
		]);
	}

	public function magicSync($id)
	{
		$document = $this->resolveModel($id);

		$relation = $this->getRelation($document);
		$parents = $relation['parents'];
		$children = $relation['children'];

		$children_collection = $document->children()->with(['products', 'altro'])->get();

		$combineQuantitiesAndTotals = function ($items, $key, $quantityKey, $priceKey) {
			return collect($items)
				->groupBy($key)
				->map(function ($group) use ($quantityKey, $priceKey) {
					$quantitySum = $group->sum($quantityKey);
					$totalSum = $group->sum(function ($item) use ($quantityKey, $priceKey) {
						$price = $item[$priceKey] ?? 0;
						return max(0, $price) * ($item[$quantityKey] ?? 0);
					});
					return [
						'quantity' => $quantitySum,
						'total' => $totalSum
					];
				});
		};

		$combinedProducts = $combineQuantitiesAndTotals($children_collection->flatMap(function ($child) {
			return $child->products->map(function ($product) {
				return [
					'product_id' => $product->product_id,
					'quantita' => $product->quantita,
					'prezzo' => $product->prezzo
				];
			});
		}), 'product_id', 'quantita', 'prezzo');

		$combinedAltro = $combineQuantitiesAndTotals($children_collection->flatMap(function ($child) {
			return $child->altro->map(function ($altro) {
				return [
					'nome' => $altro->nome,
					'quantita' => $altro->quantita,
					'prezzo' => $altro->prezzo
				];
			});
		}), 'nome', 'quantita', 'prezzo');

		$syncData = [];

		foreach ($document->products as $product) {
			$combined = $combinedProducts->get($product->product_id, ['quantity' => 0, 'total' => 0]);
			$syncData[] = [
				'id' => $product->id,
				'quantita_originale' => $product->quantita,
				'quantita_utilizzata' => $combined['quantity'],
				'quantita_rimanente' => max(0, $product->quantita - $combined['quantity']),
				'totale_originale' => $product->quantita * $product->prezzo,
				'totale_utilizzato' => $combined['total'],
				'totale_rimanente' => max(0, ($product->quantita * $product->prezzo) - $combined['total'])
			];
		}

		foreach ($document->altro as $altro) {
			$combined = $combinedAltro->get($altro->nome, ['quantity' => 0, 'total' => 0]);
			$syncData[] = [
				'id' => $altro->id,
				'nome' => $altro->nome,
				'quantita_originale' => $altro->quantita,
				'quantita_utilizzata' => $combined['quantity'],
				'quantita_rimanente' => max(0, $altro->quantita - $combined['quantity']),
				'totale_originale' => $altro->quantita * $altro->prezzo,
				'totale_utilizzato' => $combined['total'],
				'totale_rimanente' => max(0, ($altro->quantita * $altro->prezzo) - $combined['total'])
			];
		}

		return response()->json([
			'success' => true,
			'data' => $syncData
		]);
	}

	private function getEntityName(Document $document)
	{
		return $document->entity ? $document->entity->nome : 'N/A';
	}

	private function getIndirizzo(DocumentIndirizzo $document_indirizzo)
	{
		return [
			'id' => $document_indirizzo->id,
			'indirizzo' => $document_indirizzo->indirizzo,
			'cap' => $document_indirizzo->cap,
			'citta' => $document_indirizzo->citta,
			'provincia' => $document_indirizzo->provincia,
			'paese' => $document_indirizzo->paese
		];
	}

    private function getDettagli(DocumentDettagli $document_dettagli)
	{
		return [
			'id' => $document_dettagli->id,
			'luogo_destinazione' => $document_dettagli->luogo_destinazione,
			'data_destinazione' => $document_dettagli->data_destinazione,
			'ora_destinazione' => $document_dettagli->ora_destinazione,
			'luogo_consegna' => $document_dettagli->luogo_consegna,
			'data_consegna' => $document_dettagli->data_consegna,
			'ora_consegna' => $document_dettagli->ora_consegna
		];
	}

	private function getIntestatari()
	{
		$intestatari = [];

		foreach ($this->intestatari as $intestatario) {
			$entities = Entity::where('type', $intestatario)->with('indirizzi')->get();
			$intestatari[$intestatario] = $entities;
		}

		return $intestatari;
	}

	private function getRelation(Document $document)
	{
		$parents = collect();
		$children = collect();

		$current = $document;
		while ($current->parent) {
			$parents->push($current->parent);
			$current = $current->parent;
		}

		$current = $document;
		while ($current->children->isNotEmpty()) {
			$children = $children->merge($current->children);
			$current = $current->children->first();
		}

		return [
			'parents' => $parents,
			'children' => $children
		];
	}
}
