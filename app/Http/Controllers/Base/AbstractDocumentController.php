<?php

namespace App\Http\Controllers\Base;

use App\Http\Controllers\Base\AbstractCrudController;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
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
		$query = $this->model::getDocuments($this->pattern);
		
		// Se activeYear è true, filtra per anno
		if ($this->activeYear) {
			$year = request()->query('year', date('Y'));
			$query->whereYear('data', $year);
		}
		
		return $query->get();
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

		// Preprocess boolean fields in dettagli if they exist
		if ($this->dettagli_active && isset($validatedData['dettagli'])) {
			$booleanFields = ['ricamo_logo', 'pendenza', 'fissaggio_pavimento', 'montaggio'];
			foreach ($booleanFields as $field) {
				if (isset($validatedData['dettagli'][$field])) {
					$value = $validatedData['dettagli'][$field];
					
					// Convert various boolean representations to actual boolean
					if (is_string($value)) {
						$validatedData['dettagli'][$field] = in_array(strtolower($value), ['true', '1', 'yes', 'on']);
					} elseif (is_numeric($value)) {
						$validatedData['dettagli'][$field] = (bool) $value;
					} elseif (is_bool($value)) {
						$validatedData['dettagli'][$field] = $value;
					} else {
						// If it's not a valid boolean representation, set to false
						$validatedData['dettagli'][$field] = false;
					}
				} else {
					// Set default value if not provided
					$validatedData['dettagli'][$field] = false;
				}
			}
		}

		// if(isset($validatedData['metodo_pagamento_id'])) {
		// 	if($validatedData['metodo_pagamento_id'] == '0' || $validatedData['metodo_pagamento_id'] == null) unset($validatedData['metodo_pagamento_id']);
		// }

		// if(isset($validatedData['conto_bancario_id'])) {
		// 	if($validatedData['conto_bancario_id'] == '0' || $validatedData['conto_bancario_id'] == null) unset($validatedData['conto_bancario_id']);
		// }

	}

// aggiungo fornitore_id a DocumentProduct

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
// Modificare il salvataggio per includere il fornitore_id
				switch($element['tipo']) {
					case 'merci':
					case 'servizi':
						// Log temporaneo per debug
						\Log::info("Salvando elemento {$key}:", ['element' => $element]);
						
						// CORREZIONE: Usa product_id invece di id per i prodotti
						$productId = $element['product_id'] ?? null;
						
						// Se non c'è product_id, cerca di recuperarlo dal nome del prodotto
						if (!$productId && isset($element['nome'])) {
							$product = Product::where('nome', $element['nome'])->first();
							$productId = $product ? $product->id : null;
						}
						
						// Verifica che il prodotto esista
						if (!$productId) {
							\Log::error("Prodotto non trovato per elemento {$key}: " . json_encode($element));
							continue 2; // Salta questo elemento e continua il ciclo foreach esterno
						}
						
						$productData = [
							'document_id' => $object->id,
							'product_id' => $productId,
							'type' =>  $element['tipo'],
							'quantita' => $element['quantita'],
							'prezzo' => $element['prezzo'],
							'aliquota_iva_id' => $element['iva']['aliquota_iva_id'],
							'order' => $key
						];
						
						// Aggiungi fornitore_id e riferimento solo per documenti di vendita
						if (in_array('clienti', $this->intestatari)) {
							$productData['fornitore_id'] = $element['fornitore_id'] ?? null;
							$productData['riferimento'] = $element['riferimento'] ?? null;
						}
						
						DocumentProduct::create($productData);
						break;
					case 'altro':
						DocumentAltro::create([
							'document_id' => $object->id,
							'nome' => $element['nome'],
							'quantita' => $element['quantita'],
							'unita_misura' => request()->elementi[$key]['unita_misura'],
							'prezzo' => $element['prezzo'],
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

        if($this->dettagli_active === true) {
            if (isset($validatedData['dettagli']) && !empty($validatedData['dettagli'])) {
                $dettaglio = $validatedData['dettagli'];
                DocumentDettagli::create([
                    'document_id' => $object->id,
                    'data_evasione' => $dettaglio['data_evasione'] ?? null,
                    'mod_poltrona' => $dettaglio['mod_poltrona'] ?? null,
                    'quantita' => $dettaglio['quantita'] ?? null,
                    'fianchi_finali' => $dettaglio['fianchi_finali'] ?? null,
                    'interasse_cm' => $dettaglio['interasse_cm'] ?? null,
                    'largh_bracciolo_cm' => $dettaglio['largh_bracciolo_cm'] ?? null,
                    'rivestimento' => $dettaglio['rivestimento'] ?? null,
                    'ricamo_logo' => filter_var($dettaglio['ricamo_logo'], FILTER_VALIDATE_BOOLEAN) ?? false,
                    'pendenza' => filter_var($dettaglio['pendenza'], FILTER_VALIDATE_BOOLEAN) ?? false,
                    'fissaggio_pavimento' => filter_var($dettaglio['fissaggio_pavimento'], FILTER_VALIDATE_BOOLEAN) ?? false,
                    'montaggio' => filter_var($dettaglio['montaggio'], FILTER_VALIDATE_BOOLEAN) ?? false
                ]);
            }
        }

	}

// aggiungo fornitore_id a DocumentProduct

	protected function beforeUpdate(&$validatedData)
	{
		// Debug: log dei dati ricevuti
		\Log::info("beforeUpdate chiamato");
		\Log::info("validatedData keys: " . implode(', ', array_keys($validatedData)));
		
		$validatedData['type'] =  $this->pattern;

		// Preprocess boolean fields in dettagli if they exist
		if ($this->dettagli_active && isset($validatedData['dettagli'])) {
			\Log::info("Processando dettagli: " . json_encode($validatedData['dettagli']));
			
			$booleanFields = ['ricamo_logo', 'pendenza', 'fissaggio_pavimento', 'montaggio'];
			foreach ($booleanFields as $field) {
				if (isset($validatedData['dettagli'][$field])) {
					$value = $validatedData['dettagli'][$field];
					
					// Convert various boolean representations to actual boolean
					if (is_string($value)) {
						$validatedData['dettagli'][$field] = in_array(strtolower($value), ['true', '1', 'yes', 'on']);
					} elseif (is_numeric($value)) {
						$validatedData['dettagli'][$field] = (bool) $value;
					} elseif (is_bool($value)) {
						$validatedData['dettagli'][$field] = $value;
					} else {
						// If it's not a valid boolean representation, set to false
						$validatedData['dettagli'][$field] = false;
					}
				} else {
					// Set default value if not provided
					$validatedData['dettagli'][$field] = false;
				}
			}
		}

		// if(isset($validatedData['metodo_pagamento_id'])) {
		// 	if($validatedData['metodo_pagamento_id'] == '0' || $validatedData['metodo_pagamento_id'] == null) unset($validatedData['metodo_pagamento_id']);
		// }

		// if(isset($validatedData['conto_bancario_id'])) {
		// 	if($validatedData['conto_bancario_id'] == '0' || $validatedData['conto_bancario_id'] == null) unset($validatedData['conto_bancario_id']);
		// }

		\Log::info("beforeUpdate completato");
	}

// aggiungo fornitore_id a DocumentProduct

	protected function afterUpdate(&$object, $validatedData)
	{
		// Debug: log dei dati ricevuti
		\Log::info("afterUpdate chiamato per documento ID: " . $object->id);
		\Log::info("validatedData keys: " . implode(', ', array_keys($validatedData)));
		
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
			// Debug: log degli elementi
			\Log::info("Elementi da processare: " . json_encode($validatedData['elementi']));
			\Log::info("Tipo di elementi: " . gettype($validatedData['elementi']));
			\Log::info("Elementi è array: " . (is_array($validatedData['elementi']) ? 'SI' : 'NO'));
			\Log::info("Elementi è empty: " . (empty($validatedData['elementi']) ? 'SI' : 'NO'));
			
			// Controlla che elementi sia un array e non sia null prima di eliminare
			if ($validatedData['elementi'] !== null && is_array($validatedData['elementi']) && !empty($validatedData['elementi'])) {
				$object->products()->delete();
				$object->altro()->delete();
				$object->descrizioni()->delete();

				foreach ($validatedData['elementi'] as $key => $element) {
					// Debug: log di ogni elemento
					\Log::info("Processando elemento {$key}:", ['element' => $element]);
					
					switch($element['tipo']) {
						case 'merci':
						case 'servizi':
							// CORREZIONE: Usa product_id invece di id per i prodotti
							$productId = $element['product_id'] ?? null;
							
							// Se non c'è product_id, cerca di recuperarlo dal nome del prodotto
							if (!$productId && isset($element['nome'])) {
								$product = Product::where('nome', $element['nome'])->first();
								$productId = $product ? $product->id : null;
							}
							
							\Log::info("Product ID per elemento {$key}: " . $productId);
							
							// Verifica che il prodotto esista
							if (!$productId) {
								\Log::error("Prodotto non trovato per elemento {$key}: " . json_encode($element));
								continue 2; // Salta questo elemento e continua il ciclo foreach esterno
							}
							
							$productData = [
								'document_id' => $object->id,
								'product_id' => $productId,
								'type' =>  $element['tipo'],
								'quantita' => $element['quantita'],
								'prezzo' => $element['prezzo'],
								'aliquota_iva_id' => $element['iva']['aliquota_iva_id'],
								'order' => $key
							];
							
							// Aggiungi fornitore_id e riferimento solo per documenti di vendita
							if (in_array('clienti', $this->intestatari)) {
								$productData['fornitore_id'] = $element['fornitore_id'] ?? null;
								$productData['riferimento'] = $element['riferimento'] ?? null;
							}
							
							\Log::info("Creando DocumentProduct con dati:", $productData);
							DocumentProduct::create($productData);
							break;
						case 'altro':
							// Debug: verifica se request()->elementi[$key]['unita_misura'] esiste
							$unitaMisura = null;
							if (request()->has('elementi') && isset(request()->elementi[$key]['unita_misura'])) {
								$unitaMisura = request()->elementi[$key]['unita_misura'];
							} else {
								// Fallback: cerca l'unità di misura nell'elemento stesso
								$unitaMisura = $element['unita_misura'] ?? null;
							}
							\Log::info("Unità misura per elemento {$key}: " . $unitaMisura);
							
							DocumentAltro::create([
								'document_id' => $object->id,
								'nome' => $element['nome'],
								'quantita' => $element['quantita'],
								'unita_misura' => $unitaMisura,
								'prezzo' => $element['prezzo'],
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
			} else {
				if ($validatedData['elementi'] === null) {
					\Log::warning("Elementi è NULL per documento ID: " . $object->id . ". Questo indica un problema nel frontend.");
				} else {
					\Log::warning("Elementi non validi o vuoti per documento ID: " . $object->id . ". Elementi: " . json_encode($validatedData['elementi']));
				}
			}
		}

		if($this->dettagli_active === true) {
			if (isset($validatedData['dettagli']) && !empty($validatedData['dettagli'])) {
				$object->dettagli()->delete();
				$dettaglio = $validatedData['dettagli'];
				DocumentDettagli::create([
					'document_id' => $object->id,
					'data_evasione' => $dettaglio['data_evasione'] ?? null,
					'mod_poltrona' => $dettaglio['mod_poltrona'] ?? null,
					'quantita' => $dettaglio['quantita'] ?? null,
					'fianchi_finali' => $dettaglio['fianchi_finali'] ?? null,
					'interasse_cm' => $dettaglio['interasse_cm'] ?? null,
					'largh_bracciolo_cm' => $dettaglio['largh_bracciolo_cm'] ?? null,
					'rivestimento' => $dettaglio['rivestimento'] ?? null,
					'ricamo_logo' => filter_var($dettaglio['ricamo_logo'], FILTER_VALIDATE_BOOLEAN) ?? false,
					'pendenza' => filter_var($dettaglio['pendenza'], FILTER_VALIDATE_BOOLEAN) ?? false,
					'fissaggio_pavimento' => filter_var($dettaglio['fissaggio_pavimento'], FILTER_VALIDATE_BOOLEAN) ?? false,
					'montaggio' => filter_var($dettaglio['montaggio'], FILTER_VALIDATE_BOOLEAN) ?? false
				]);
			}
		}
		
		\Log::info("afterUpdate completato per documento ID: " . $object->id);
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
			// Carica esplicitamente le relazioni necessarie
			$object->load(['entity', 'indirizzo', 'dettagli', 'products.product.aliquotaIva', 'products.product.categories.parent', 'altro.aliquotaIva', 'descrizioni', 'media']);
			
			$data['main'] = [
				'id' => $object->id,
				'numero' => $object->numero,
				'data' => $object->data,
				'stato' => $object->stato,
				'note' => $object->note,
				'entity_id' => $object->entity_id,
				'entity_type' => $object->entity ? $object->entity->type : null,
				'entity' => $object->entity,
				'indirizzo' => $object->indirizzo,
				'dettagli' => $object->dettagli ? $this->getDettagli($object->dettagli) : null,
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

	//  Modificare il metodo getElementi per includere il fornitore
	// Modifico il metodo da private a protected per poterlo utilizzare nel controller figlio
	protected function getElementi(Document $document)
	{
		$elementi = collect();

		// Aggiungi prodotti
		foreach ($document->products as $product) {
			$categoria = $product->product->categories->first()->nome ?? null;
			// Determina il tipo in base al prodotto se il campo type è null
			$tipo = $product->type;
			if ($tipo === null && $product->product) {
				// Determina il tipo in base alla categoria del prodotto
				if ($product->product->categories && $product->product->categories->count() > 0) {
					$categoria = $product->product->categories->first();
					if ($categoria->parent_id === null) {
						// Categoria principale
						$tipo = strtolower($categoria->nome);
					} else {
						// Sottocategoria, cerca la categoria padre
						$categoriaPadre = $categoria->parent;
						if ($categoriaPadre) {
							$tipo = strtolower($categoriaPadre->nome);
						}
					}
				}
			}
			
			// Mappa i tipi delle categorie ai tipi accettati dalla validazione
			$tipoMappato = $this->mapTipoToValidType($tipo);
			
			$elementi->push([
				'id' => $product->id,
				'tipo' => $tipoMappato,
				'codice' => $product->product->codice,
				'categoria' => $categoria,
				'product_id' => $product->product_id,
				'nome' => $product->product->nome,
				'quantita' => $product->quantita,
				'prezzo' => $product->prezzo,
				'unita_misura' => $product->product->unita_misura ?? 'NR',
				'importo' => $product->quantita * $product->prezzo,
				'fornitore_id' => $product->fornitore_id,
				'riferimento' => $product->riferimento,
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
				'importo' => $altro->quantita * $altro->prezzo,
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

	/**
	 * Mappa i tipi delle categorie ai tipi accettati dalla validazione
	 */
	private function mapTipoToValidType($tipo)
	{
		// Mappa dei tipi delle categorie ai tipi validi
		$mapping = [
			'ferramenta' => 'merci',
			'ferramenta' => 'merci',
			'accessori' => 'merci',
			'componenti' => 'merci',
			'ricambi' => 'merci',
			'materiali' => 'merci',
			'prodotti' => 'merci',
			'articoli' => 'merci',
			'servizi' => 'servizi',
			'assistenza' => 'servizi',
			'manutenzione' => 'servizi',
			'installazione' => 'servizi',
			'consulenza' => 'servizi',
		];
		
		// Se il tipo è già valido, restituiscilo
		if (in_array($tipo, ['merci', 'servizi', 'altro', 'descrizione'])) {
			return $tipo;
		}
		
		// Altrimenti, cerca nella mappa o usa 'merci' come fallback
		return $mapping[$tipo] ?? 'merci';
	}

	// Aggiungere i fornitori ai dati passati al frontend
	protected function setOtherData(string $type, Model $object)
	{
		$data['aliquote_iva'] = AliquotaIva::all();
		$data['tipi_intestatari'] = $this->tipi_intestatari;
		$data['intestatari'] = $this->getIntestatari();
		
		// Passa i fornitori sempre per show/edit (per popolare i campi esistenti)
		// Per create, passa i fornitori solo se il documento è di tipo vendita
		if ($type === 'create') {
			// Per la creazione, passa i fornitori solo per ordini vendita
			if (in_array('clienti', $this->intestatari)) {
				$data['fornitori'] = Entity::fornitori()->get();
			} else {
				$data['fornitori'] = collect([]);
			}
		} else {
			// Per show/edit, passa sempre i fornitori per popolare i campi esistenti
			$data['fornitori'] = Entity::fornitori()->get();
		}

		$data['prodotti']['merci'] = Product::merci()->with(['categories', 'aliquotaIva'])->get();
		if($this->trasporto_active === false) $data['prodotti']['servizi'] = Product::servizi()->with(['categories', 'aliquotaIva'])->get();

		// Rimuovi tutti i dati non necessari
		$data['metodi_pagamento'] = collect([]);
		$data['conti_bancari'] = collect([]);
		$data['spedizioni'] = collect([]);
		$data['ricorrenze'] = collect([]);
		
		$data['entrata'] = $this->entrata;
		$data['spedizione_active'] = $this->spedizione_active;
		$data['trasporto_active'] = $this->trasporto_active;
		$data['dettagli_active'] = $this->dettagli_active;
		$data['metodo_pagamento_active'] = $this->metodo_pagamento_active;
		$data['rate_active'] = $this->rate_active;
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
			
			// Aggiungi dettagli di default se attivi
			if ($this->dettagli_active) {
				$data['dettagli'] = [
					'data_evasione' => null,
					'mod_poltrona' => null,
					'quantita' => null,
					'fianchi_finali' => null,
					'interasse_cm' => null,
					'largh_bracciolo_cm' => null,
					'rivestimento' => null,
					'ricamo_logo' => false,
					'pendenza' => false,
					'fissaggio_pavimento' => false,
					'montaggio' => false
				];
			}
		}

		return $data;
	}

	protected function setValidation(Model $object)
	{
		$rules = [
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
			'elementi.*.product_id' => 'required_unless:elementi.*.tipo,altro,descrizione|integer|exists:products,id', // ID del prodotto per merci/servizi
			'elementi.*.nome' => 'required_unless:elementi.*.tipo,merci,servizi,descrizione|string', // Nome solo per "altro"
		];

		// Aggiungi regole per fornitore_id e riferimento solo per documenti di vendita
		if (in_array('clienti', $this->intestatari)) {
			$rules['elementi.*.fornitore_id'] = 'nullable|integer|exists:entities,id'; // Fornitore opzionale per merci/servizi
			$rules['elementi.*.riferimento'] = 'nullable|string|max:255'; // Riferimento opzionale per merci/servizi
		}

		// Aggiungi regole per i dettagli se attivi
		if ($this->dettagli_active) {
			$rules['dettagli'] = 'nullable|array';
			$rules['dettagli.data_evasione'] = 'nullable|date';
			$rules['dettagli.mod_poltrona'] = 'nullable|string|max:255';
			$rules['dettagli.quantita'] = 'nullable|integer|min:0';
			$rules['dettagli.fianchi_finali'] = 'nullable|integer|min:0';
			$rules['dettagli.interasse_cm'] = 'nullable|numeric|min:0';
			$rules['dettagli.largh_bracciolo_cm'] = 'nullable|numeric|min:0';
			$rules['dettagli.rivestimento'] = 'nullable|string|max:255';
			$rules['dettagli.ricamo_logo'] = 'nullable|in:true,false,1,0,"true","false","1","0"';
			$rules['dettagli.pendenza'] = 'nullable|in:true,false,1,0,"true","false","1","0"';
			$rules['dettagli.fissaggio_pavimento'] = 'nullable|in:true,false,1,0,"true","false","1","0"';
			$rules['dettagli.montaggio'] = 'nullable|in:true,false,1,0,"true","false","1","0"';
		}

		return [
			'rules' => $rules,
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
				'elementi.*.product_id.required_unless' => 'Il prodotto è obbligatorio per merci e servizi.',
				'elementi.*.nome.required_unless' => 'Il nome è obbligatorio per elementi personalizzati.',
				'dettagli.data_evasione.date' => 'La data di evasione deve essere una data valida.',
				'dettagli.quantita.integer' => 'La quantità deve essere un numero intero.',
				'dettagli.quantita.min' => 'La quantità non può essere negativa.',
				'dettagli.fianchi_finali.integer' => 'I fianchi finali devono essere un numero intero.',
				'dettagli.fianchi_finali.min' => 'I fianchi finali non possono essere negativi.',
				'dettagli.interasse_cm.numeric' => 'L\'interasse deve essere un numero.',
				'dettagli.interasse_cm.min' => 'L\'interasse non può essere negativo.',
				'dettagli.largh_bracciolo_cm.numeric' => 'La larghezza del bracciolo deve essere un numero.',
				'dettagli.largh_bracciolo_cm.min' => 'La larghezza del bracciolo non può essere negativa.'
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
			$actionPermissions = [
				'show' => 'show',
				'edit' => 'edit',
				'update' => 'edit',
				'destroy' => 'delete'
			];
			
			// Aggiungi azioni solo se abilitate
			if ($this->clone) {
				$actionPermissions['clone'] = 'clone';
			}
			if ($this->pdf) {
				$actionPermissions['pdf'] = 'pdf';
			}
			if ($this->magic) {
				$actionPermissions['magic'] = 'magic';
			}
			
			$data['actions'] = $this->getAction($object, $actionPermissions);
		}

		// Per i tipi show e edit, struttura i dati come si aspetta il frontend
		if (in_array($type, ['show', 'edit']) && $object->exists) {
			return [
				'document' => $main,
				'stato' => $object->stato,
				'relation' => $this->getRelation($object),
				'interlocutore' => $this->entrata ? 'Fornitore' : 'Cliente',
				'numero' => $object->numero
			];
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

		if ($object->dettagli) {
            $newDocument->dettagli()->create($object->dettagli->toArray());
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
			'data_evasione' => $document_dettagli->data_evasione,
			'mod_poltrona' => $document_dettagli->mod_poltrona,
			'quantita' => $document_dettagli->quantita,
			'fianchi_finali' => $document_dettagli->fianchi_finali,
			'interasse_cm' => $document_dettagli->interasse_cm,
			'largh_bracciolo_cm' => $document_dettagli->largh_bracciolo_cm,
			'rivestimento' => $document_dettagli->rivestimento,
			'ricamo_logo' => $document_dettagli->ricamo_logo,
			'pendenza' => $document_dettagli->pendenza,
			'fissaggio_pavimento' => $document_dettagli->fissaggio_pavimento,
			'montaggio' => $document_dettagli->montaggio
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

	/**
	 * Elimina una risorsa dal database.
	 *
	 * @param int $id  ID della risorsa da eliminare
	 * @return \Illuminate\Http\JsonResponse
	**/
	public function destroy($id)
	{
		return DB::transaction(function () use ($id) {
			$object = $this->resolveModel($id);
			$data = $this->getJsonData('destroy', $object);

			// Cancella manualmente le relazioni prima di cancellare il documento
			$object->products()->delete();
			$object->altro()->delete();
			$object->descrizioni()->delete();
			$object->indirizzo()->delete();
			if ($this->dettagli_active) {
				$object->dettagli()->delete();
			}
			$object->media()->delete();
			
			// Cancella il documento principale
			$object->delete();

			return response()->json(['record' => $data]);
		});
	}
}
