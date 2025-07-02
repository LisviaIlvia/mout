<?php

namespace App\Imports;

use App\Models\Document;
use App\Models\DocumentProduct;
use App\Models\DocumentIndirizzo;
use App\Models\DocumentDescrizione;
use App\Models\DocumentDettagli;
use App\Models\Entity;
use App\Models\Product;
use App\Models\AliquotaIva;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Events\AfterImport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Helpers\FunctionsHelper;

class OrdineVenditaImport implements ToModel, WithHeadingRow, WithValidation, WithBatchInserts, WithChunkReading, WithEvents
{
    protected $importedCount = 0;
    protected $errors = [];
    protected $year;

    public function __construct($year = null)
    {
        $this->year = $year ?? date('Y');
    }

    /**
     * @param array $row
     */
    public function model(array $row)
    {
        try {
            // Validazione base dei dati
            if (empty($row['numero']) || empty($row['data']) || empty($row['cliente'])) {
                $this->errors[] = "Riga " . ($this->importedCount + 2) . ": Dati mancanti (numero, data, cliente)";
                return null;
            }

            // Trova o crea il cliente
            $cliente = Entity::where('nome', 'like', '%' . trim($row['cliente']) . '%')
                           ->where('type', 'clienti')
                           ->first();

            if (!$cliente) {
                $this->errors[] = "Riga " . ($this->importedCount + 2) . ": Cliente '{$row['cliente']}' non trovato";
                return null;
            }

            // Genera numero ordine se non specificato
            $numero = !empty($row['numero']) ? trim($row['numero']) : FunctionsHelper::getLastNumber('ODV', Document::class, $this->year)['numero'];

            // Parsing data
            $data = $this->parseDate($row['data']);
            if (!$data) {
                $this->errors[] = "Riga " . ($this->importedCount + 2) . ": Data non valida '{$row['data']}'";
                return null;
            }

            // Crea l'ordine vendita
            $ordine = Document::create([
                'type' => 'ordini-vendita',
                'numero' => $numero,
                'data' => $data,
                'entity_id' => $cliente->id,
                'note' => $row['note'] ?? null,
                'stato' => $row['stato'] ?? 'Bozza'
            ]);

            // Crea indirizzo se specificato
            if (!empty($row['indirizzo'])) {
                DocumentIndirizzo::create([
                    'document_id' => $ordine->id,
                    'nome' => $row['indirizzo'],
                    'indirizzo' => $row['indirizzo'],
                    'comune' => $row['comune'] ?? '',
                    'provincia' => $row['provincia'] ?? '',
                    'cap' => $row['cap'] ?? ''
                ]);
            }

            // Aggiungi prodotti se specificati
            if (!empty($row['prodotti'])) {
                $this->addProducts($ordine, $row['prodotti']);
            }

            // Aggiungi descrizioni se specificate
            if (!empty($row['descrizione'])) {
                DocumentDescrizione::create([
                    'document_id' => $ordine->id,
                    'descrizione' => $row['descrizione'],
                    'order' => 1
                ]);
            }

            // Aggiungi dettagli se specificati
            if (!empty($row['dettagli'])) {
                $this->addDettagli($ordine, $row);
            }

            $this->importedCount++;
            return $ordine;

        } catch (\Exception $e) {
            Log::error('Errore import ordine vendita: ' . $e->getMessage(), $row);
            $this->errors[] = "Riga " . ($this->importedCount + 2) . ": Errore - " . $e->getMessage();
            return null;
        }
    }

    /**
     * Aggiunge prodotti all'ordine
     */
    protected function addProducts($ordine, $prodottiString)
    {
        $prodotti = explode(';', $prodottiString);
        $order = 1;

        foreach ($prodotti as $prodotto) {
            $parts = explode(':', $prodotto);
            if (count($parts) >= 3) {
                $codice = trim($parts[0]);
                $quantita = (float) trim($parts[1]);
                $prezzo = (float) trim($parts[2]);

                $product = Product::where('codice', $codice)->first();
                if ($product) {
                    DocumentProduct::create([
                        'document_id' => $ordine->id,
                        'product_id' => $product->id,
                        'quantita' => $quantita,
                        'prezzo' => $prezzo,
                        'aliquota_iva_id' => $product->aliquota_iva_id,
                        'order' => $order++
                    ]);
                }
            }
        }
    }

    /**
     * Aggiunge dettagli all'ordine
     */
    protected function addDettagli($ordine, $row)
    {
        DocumentDettagli::create([
            'document_id' => $ordine->id,
            'data_evasione' => !empty($row['data_evasione']) ? $this->parseDate($row['data_evasione']) : null,
            'mod_poltrona' => $row['mod_poltrona'] ?? null,
            'quantita' => !empty($row['quantita_dettagli']) ? (int) $row['quantita_dettagli'] : null,
            'fianchi_finali' => $row['fianchi_finali'] ?? null,
            'interasse_cm' => !empty($row['interasse_cm']) ? (float) $row['interasse_cm'] : null,
            'largh_bracciolo_cm' => !empty($row['largh_bracciolo_cm']) ? (float) $row['largh_bracciolo_cm'] : null,
            'rivestimento' => $row['rivestimento'] ?? null,
            'ricamo_logo' => isset($row['ricamo_logo']) ? filter_var($row['ricamo_logo'], FILTER_VALIDATE_BOOLEAN) : false,
            'pendenza' => isset($row['pendenza']) ? filter_var($row['pendenza'], FILTER_VALIDATE_BOOLEAN) : false,
            'fissaggio_pavimento' => isset($row['fissaggio_pavimento']) ? filter_var($row['fissaggio_pavimento'], FILTER_VALIDATE_BOOLEAN) : false,
            'montaggio' => isset($row['montaggio']) ? filter_var($row['montaggio'], FILTER_VALIDATE_BOOLEAN) : false
        ]);
    }

    /**
     * Parsing data da vari formati
     */
    protected function parseDate($dateString)
    {
        $dateString = trim($dateString);
        
        // Prova vari formati
        $formats = ['d/m/Y', 'Y-m-d', 'd-m-Y', 'Y/m/d'];
        
        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat($format, $dateString);
                return $date->format('Y-m-d');
            } catch (\Exception $e) {
                continue;
            }
        }
        
        return null;
    }

    /**
     * Regole di validazione
     */
    public function rules(): array
    {
        return [
            'numero' => 'nullable|string',
            'data' => 'required|string',
            'cliente' => 'required|string',
            'note' => 'nullable|string',
            'stato' => 'nullable|string',
            'indirizzo' => 'nullable|string',
            'comune' => 'nullable|string',
            'provincia' => 'nullable|string',
            'cap' => 'nullable|string',
            'prodotti' => 'nullable|string',
            'descrizione' => 'nullable|string',
            'data_evasione' => 'nullable|string',
            'mod_poltrona' => 'nullable|string',
            'quantita_dettagli' => 'nullable|numeric',
            'fianchi_finali' => 'nullable|string',
            'interasse_cm' => 'nullable|numeric',
            'largh_bracciolo_cm' => 'nullable|numeric',
            'rivestimento' => 'nullable|string',
            'ricamo_logo' => 'nullable|boolean',
            'pendenza' => 'nullable|boolean',
            'fissaggio_pavimento' => 'nullable|boolean',
            'montaggio' => 'nullable|boolean'
        ];
    }

    /**
     * Messaggi di validazione personalizzati
     */
    public function customValidationMessages()
    {
        return [
            'data.required' => 'La data è obbligatoria',
            'cliente.required' => 'Il cliente è obbligatorio',
        ];
    }

    /**
     * Dimensione del batch per l'importazione
     */
    public function batchSize(): int
    {
        return 100;
    }

    /**
     * Dimensione del chunk per la lettura
     */
    public function chunkSize(): int
    {
        return 100;
    }

    /**
     * Eventi durante l'importazione
     */
    public function registerEvents(): array
    {
        return [
            BeforeImport::class => function(BeforeImport $event) {
                DB::beginTransaction();
            },
            AfterImport::class => function(AfterImport $event) {
                if (empty($this->errors)) {
                    DB::commit();
                } else {
                    DB::rollBack();
                }
            },
        ];
    }

    /**
     * Restituisce il numero di record importati
     */
    public function getImportedCount()
    {
        return $this->importedCount;
    }

    /**
     * Restituisce gli errori
     */
    public function getErrors()
    {
        return $this->errors;
    }
} 