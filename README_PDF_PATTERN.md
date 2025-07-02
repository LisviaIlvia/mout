# Pattern PDF nel CRM - Documentazione Completa

## Panoramica

Il CRM implementa un pattern coerente e gerarchico per la gestione dei PDF che segue la stessa logica utilizzata per tutti gli altri componenti del sistema. Il sistema è progettato per essere **estendibile**, **manutenibile** e **coerente** con l'architettura esistente.

## Architettura del Pattern

### 1. AbstractCrudController (Base)

Il controller base fornisce l'infrastruttura generica per i PDF:

```php
// Proprietà di configurazione
protected bool $pdf = false;

// Metodo per definire i componenti PDF (per future implementazioni)
protected function setComponentsPdf()
{
    $content = str_replace(' ', '', ucwords(str_replace('-', ' ', strtolower($this->pattern))));
    
    return [
        'pdf' => 'Crud/CrudPdf',
        'content' => $content . 'PdfContent'
    ];
}

// Metodo per ottenere i path completi dei componenti PDF
private function getComponentsPdf() 
{
    $components = $this->setComponentsPdf();
    return [
        'pdf' => isset($components['pdf']) ? $this->root . $components['pdf'] : null,
        'content' => isset($components['content']) ? $this->root . $components['content'] : null,
    ];
}

// Metodo generico per la generazione PDF
public function pdf($id)
{
    $object = $this->resolveModel($id);
    return $this->generatePdf($object);
}

// Hook method da implementare nei controller figli
protected function generatePdf(Model $object)
{
    return response()->json([
        'error' => 'PDF generation not implemented for this resource',
        'message' => 'This resource does not support PDF generation'
    ], 501);
}
```

### 2. AbstractDocumentController (Intermedio)

Il controller intermedio implementa la logica specifica per i documenti:

```php
// Configurazione PDF specifica per documenti
protected array $pdfSetup = [
    'template' => 'pdf.document', // Template Blade di default
    'format' => 'a4',            // Formato di default
    'landscape' => false,        // Orientamento di default
    'margins' => [15, 15, 15, 15], // Margini di default
    'filename_prefix' => 'document' // Prefisso nome file di default
];

// Componenti PDF personalizzati per documenti
protected function setComponentsPdf()
{
    $content = str_replace(' ', '', ucwords(str_replace('-', ' ', strtolower($this->pattern))));
    
    return [
        'pdf' => 'documents/DocumentsPdf',
        'content' => 'documents/' . $content . 'PdfContent'
    ];
}

// Implementazione della generazione PDF
protected function generatePdf(Model $object)
{
    try {
        // 1. Recupera il modello del documento
        $document = $object;

        // 2. Carica tutte le relazioni necessarie per il PDF
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

        // 3. Prepara le immagini degli allegati
        $document->media->each(function ($media) {
            if (str_starts_with($media->mime_type, 'image/')) {
                $imagePath = storage_path('app/private/media/' . $this->pattern . '/' . $media->name);
                if (file_exists($imagePath)) {
                    $media->base64_data = base64_encode(file_get_contents($imagePath));
                }
            }
        });

        // 4. Recupera tutti gli elementi del documento
        $elementi = $this->getElementi($document);
        $elementiPerCategoria = $elementi->groupBy(fn($item) => $item['categoria']['nome'] ?? 'Senza categoria');

        // 5. Prepara i dati per il template
        $data = [
            'document' => $document,
            'elementi' => $elementi,
            'elementiPerCategoria' => $elementiPerCategoria,
            'azienda' => \App\Models\Azienda::first(),
            'aziendaIndirizzi' => \App\Models\AziendaIndirizzo::where('azienda_id', 1)->get(),
        ];

        // 6. Hook per personalizzazioni
        $data = $this->beforePdfGeneration($data, $document);

        // 7. Genera il PDF
        $pdf = Pdf::view($this->pdfSetup['template'], $data)
            ->headerView('pdf.header-pdf', $data)
            ->format($this->pdfSetup['format'])
            ->margins(...$this->pdfSetup['margins'])
            ->name($this->pdfSetup['filename_prefix'] . '-' . $document->numero . '.pdf');

        if ($this->pdfSetup['landscape']) {
            $pdf->landscape();
        }

        return $pdf->download();
        
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Errore nella generazione PDF ' . $this->pattern . ' ID ' . $document->id . ': ' . $e->getMessage());
        
        return response()->json([
            'error' => 'Errore nella generazione del PDF',
            'message' => $e->getMessage()
        ], 500);
    }
}

// Hook method per personalizzare i dati del PDF
protected function beforePdfGeneration(array $data, Document $document): array
{
    return $data;
}
```

### 3. Controller Specifici (Foglie)

I controller specifici possono personalizzare ulteriormente:

```php
// Configurazione PDF personalizzata
protected array $pdfSetup = [
    'template' => 'pdf.ordine-vendita',
    'format' => 'a3',
    'landscape' => true,
    'margins' => [15, 15, 15, 15],
    'filename_prefix' => 'ordine-vendita'
];

// Componenti PDF personalizzati (per future implementazioni)
protected function setComponentsPdf()
{
    return [
        'pdf' => 'documents/OrdiniVenditaPdf',
        'content' => 'documents/OrdiniVenditaPdfContent'
    ];
}

// Personalizzazione dati PDF
protected function beforePdfGeneration(array $data, Document $document): array
{
    // Aggiungi dati specifici per ordini vendita
    $data['dati_specifici'] = $this->getDatiSpecificiOrdineVendita($document);
    return $data;
}
```

## Implementazione Frontend (Vue.js)

### 1. Struttura dei Componenti

Il sistema PDF utilizza un'architettura a tre livelli:

#### **Livello 1: CrudIndex.vue (Tabella principale)**
```vue
<template>
  <!-- Pulsante PDF nella tabella -->
  <v-btn 
    v-if="item.actions.pdf && item.actions.pdf != false"
    icon="fa-solid fa-file-pdf fa-sm"
    density="compact"
    rounded="sm"
    class="me-2"
    :color="crudTable.colorPdf"
    :disabled="!item.actions.pdf"
    :loading="crudTable.isPdfLoading(item.id)"
    @click="crudTable.openPdf(item.actions.pdf, item.id)"
  />
</template>

<script>
export default {
  // Il componente riceve le props dal backend
  props: {
    // ... altre props
  },
  data() {
    return {
      crudTable: new this.$crudTable(this.$usePage().props)
    }
  }
}
</script>
```

#### **Livello 2: crudTable.js (Gestione logica)**
```javascript
import DownloadService from './downloadService';

class crudTable {
  constructor(props) {
    // ... altre proprietà
    
    // Service per i download
    this.downloadService = new DownloadService();
  }

  // Metodo che gestisce il click sul pulsante PDF
  openPdf(urlOpen, recordId) {
    this.downloadService.downloadPdf(urlOpen, recordId);
  }

  // Metodo per verificare lo stato di loading
  isPdfLoading(recordId) {
    return this.downloadService.isLoading('pdf', recordId);
  }
}
```

#### **Livello 3: downloadService.js (Download effettivo)**
```javascript
class DownloadService {
  constructor() {
    // Oggetto che mantiene lo stato di loading per ogni tipo di download
    this.loading = {
      pdf: {},      // Stato loading per i PDF
      qrCode: {},   // Stato loading per i QR Code
      excel: {},    // Stato loading per i file Excel
    };
  }

  /**
   * Download PDF - Scarica un documento PDF dal server
   * 
   * @param {string} urlOpen - URL per generare/scaricare il PDF
   * @param {number|string} recordId - ID del record per il quale generare il PDF
   */
  downloadPdf(urlOpen, recordId) {
    // Avvia lo stato di loading per questo PDF
    this.startLoading('pdf', recordId);
    
    // Mostra notifica di inizio generazione
    this.showNotification('Generazione PDF in corso...', 'info');
    
    // Esegue la richiesta fetch per scaricare il PDF
    fetch(urlOpen)
      .then(response => {
        // Verifica che la risposta sia valida
        if (!response.ok) {
          throw new Error('Errore nel caricamento del PDF');
        }
        // Converte la risposta in blob (file binario)
        return response.blob();
      })
      .then(blob => {
        // Scarica il blob come file PDF
        this.downloadBlob(blob, `documento-${recordId}.pdf`);
        // Ferma lo stato di loading
        this.stopLoading('pdf', recordId);
      })
      .catch(error => {
        // Gestione errori: log, stop loading e fallback
        console.error('Errore PDF:', error);
        this.stopLoading('pdf', recordId);
        // Tentativo di apertura diretta in nuova finestra
        this.fallbackDownload(urlOpen);
        this.showNotification('Errore nella generazione del PDF - Tentativo con apertura diretta', 'warning');
      });
  }

  /**
   * Scarica un blob come file nel browser
   */
  downloadBlob(blob, filename) {
    // Crea un URL temporaneo per il blob
    const url = window.URL.createObjectURL(blob);
    
    // Crea un elemento link temporaneo
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    
    // Aggiunge il link al DOM, lo clicca e lo rimuove
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    // Libera la memoria rilasciando l'URL del blob
    window.URL.revokeObjectURL(url);
  }

  /**
   * Fallback - Apre l'URL in una nuova finestra se il download diretto fallisce
   */
  fallbackDownload(url) {
    console.log('Tentativo fallback con apertura in nuova finestra...');
    window.open(url, '_blank');
  }

  // Metodi per gestire lo stato di loading
  startLoading(type, recordId) {
    if (!this.loading[type][recordId]) {
      this.loading[type][recordId] = {};
    }
    this.loading[type][recordId] = true;
  }

  stopLoading(type, recordId) {
    if (this.loading[type][recordId]) {
      this.loading[type][recordId] = false;
    }
  }

  isLoading(type, recordId) {
    if (!this.loading[type][recordId]) return false;
    return this.loading[type][recordId] || false;
  }

  /**
   * Mostra una notifica all'utente
   */
  showNotification(message, color = 'info', timeout = 3000) {
    if (window.flashMessage) {
      window.flashMessage({
        message,
        color,
        timeout
      });
    }
  }
}

export default DownloadService;
```

### 2. Inizializzazione Globale

Il `downloadService` è inizializzato globalmente in `app.js`:

```javascript
// In app.js
import DownloadService from './lib/downloadService';

// Rendi downloadService disponibile globalmente
window.downloadService = new DownloadService();
window.flashMessage = (notification) => {
  if (app.config.globalProperties.$flashMessage) {
    app.config.globalProperties.$flashMessage(notification);
  }
};
```

## Pattern delle Azioni e Rotte

### 1. Generazione Automatica delle Azioni

```php
// In AbstractCrudController::getPropsIndex()
protected function getPropsIndex(Collection $collection, array $actionPermissions = [...])
{
    // Aggiunge automaticamente l'azione PDF se abilitata
    if($this->pdf === true) $actionPermissions['pdf'] = 'pdf';
    
    return [
        // ... altre props
        'data' => $this->getDataIndex($collection, $actionPermissions),
    ];
}

// In AbstractCrudController::getAction()
protected function getAction(Model $object = null, array $actionPermissions = [...])
{
    if($object) {
        return collect($actionPermissions)->mapWithKeys(function ($permissionAction, $routeAction) use ($user, $pattern, $permission, $object) {
            // Genera automaticamente l'URL per l'azione PDF
            return [
                $routeAction => $user->can("{$permission}.{$permissionAction}") 
                    ? route("{$pattern}.{$routeAction}", $object) 
                    : false
            ];
        })->toArray();
    }
}
```

### 2. Generazione Automatica delle Rotte

```php
// In CrudRoutePermissionHelper::resource()
public static function resource(string $prefix, string $bind, string $controller, array $options = [])
{
    $defaults = [
        'export' => false,
        'pdf' => false,        // Opzione per abilitare PDF
        'clone' => false,
        'magic' => false,
        'permission' => '',
        'routes_excluded' => []
    ];

    $options = array_merge($defaults, $options);
    
    $operations = [
        'pdf' => function() use ($prefix, $bind, $controller, $permission) { 
            self::pdf($prefix, $bind, $controller, $permission); 
        },
        // ... altre operazioni
    ];

    foreach ($operations as $operation => $callback) {
        if ($operation === 'pdf') {
            if ($options[$operation] === true) {
                $callback(); // Genera la rotta PDF
            }
        }
    }
}

// In CrudRoutePermissionHelper::pdf()
public static function pdf(string $prefix, string $bind, string $controller, string $permission)
{
    Route::group(['middleware' => ["permission:{$permission}.pdf"]], function () use ($prefix, $bind, $controller) {
        Route::get("/{$prefix}/pdf/{{$bind}}", [$controller, 'pdf'])->name($prefix.'.pdf');
    });
}
```

## Flusso Completo di Generazione PDF

### 1. **Frontend (Click Utente)**
```javascript
// 1. Utente clicca sul pulsante PDF
@click="crudTable.openPdf(item.actions.pdf, item.id)"

// 2. crudTable.js gestisce il click
openPdf(urlOpen, recordId) {
    this.downloadService.downloadPdf(urlOpen, recordId);
}

// 3. downloadService.js esegue il download
downloadPdf(urlOpen, recordId) {
    this.startLoading('pdf', recordId);
    fetch(urlOpen)
        .then(response => response.blob())
        .then(blob => this.downloadBlob(blob, `documento-${recordId}.pdf`))
        .catch(error => this.fallbackDownload(urlOpen));
}
```

### 2. **Backend (Generazione PDF)**
```php
// 1. Rotte generate automaticamente
Route::get("/ordini-vendita/pdf/{id}", [OrdineVenditaController::class, 'pdf'])
    ->name('ordini-vendita.pdf')
    ->middleware('permission:ordine_vendita.pdf');

// 2. Controller riceve la richiesta
public function pdf($id)
{
    $object = $this->resolveModel($id);
    return $this->generatePdf($object);
}

// 3. AbstractDocumentController genera il PDF
protected function generatePdf(Model $object)
{
    // Carica relazioni
    $document->load(['entity', 'indirizzo', 'products.product.aliquotaIva', ...]);
    
    // Prepara dati
    $data = [
        'document' => $document,
        'elementi' => $this->getElementi($document),
        'azienda' => \App\Models\Azienda::first(),
        // ...
    ];
    
    // Hook per personalizzazioni
    $data = $this->beforePdfGeneration($data, $document);
    
    // Genera PDF con Spatie Laravel PDF
    $pdf = Pdf::view($this->pdfSetup['template'], $data)
        ->headerView('pdf.header-pdf', $data)
        ->format($this->pdfSetup['format'])
        ->margins(...$this->pdfSetup['margins'])
        ->name($this->pdfSetup['filename_prefix'] . '-' . $document->numero . '.pdf');
    
    return $pdf->download();
}
```

### 3. **Frontend (Ricezione e Download)**
```javascript
// 1. downloadService riceve il blob
.then(blob => {
    this.downloadBlob(blob, `documento-${recordId}.pdf`);
    this.stopLoading('pdf', recordId);
})

// 2. Scarica il file
downloadBlob(blob, filename) {
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
}
```

## Vantaggi del Pattern

### 1. **Coerenza**
- Tutti i PDF seguono la stessa struttura
- Componenti organizzati in modo gerarchico
- Configurazione standardizzata

### 2. **Flessibilità**
- Ogni livello può personalizzare i componenti
- Configurazione specifica per tipo di documento
- Hook methods per estensioni

### 3. **Manutenibilità**
- Logica centralizzata
- Facile aggiungere nuovi tipi di PDF
- Separazione delle responsabilità

### 4. **Automazione**
- Rotte generate automaticamente
- Azioni aggiunte automaticamente
- Permessi gestiti automaticamente

### 5. **Robustezza**
- Gestione errori completa
- Fallback automatico
- Loading states per UX

## Come Implementare un Nuovo PDF

### 1. **Backend (Laravel)**

#### Abilitare PDF nel Controller
```php
protected bool $pdf = true;
```

#### Configurare il PDF Setup
```php
protected array $pdfSetup = [
    'template' => 'pdf.mio-template',
    'format' => 'a4',
    'landscape' => false,
    'margins' => [15, 15, 15, 15],
    'filename_prefix' => 'mio-prefisso'
];
```

#### Personalizzare i Componenti (Opzionale)
```php
protected function setComponentsPdf()
{
    return [
        'pdf' => 'mio/MioPdf',
        'content' => 'mio/MioPdfContent'
    ];
}
```

#### Personalizzare i Dati PDF (Opzionale)
```php
protected function beforePdfGeneration(array $data, Document $document): array
{
    $data['dati_specifici'] = $this->getDatiSpecifici($document);
    return $data;
}
```

### 2. **Frontend (Vue.js)**

Il frontend è **completamente automatico**:
- ✅ Pulsante PDF appare automaticamente
- ✅ Click gestito da `crudTable.openPdf()`
- ✅ Download gestito da `downloadService.downloadPdf()`
- ✅ Loading states gestiti automaticamente
- ✅ Gestione errori e fallback automatici

### 3. **Rotte (Laravel)**

Le rotte sono **generate automaticamente**:
```php
// Nel file routes/web.php
CrudRoutePermission::resource('mio-controller', 'mio', MioController::class, [
    'pdf' => true  // Abilita automaticamente la rotta PDF
]);
```

## Struttura dei File

### Backend (Laravel)
```
app/Http/Controllers/
├── Base/
│   ├── AbstractCrudController.php      # Infrastruttura base PDF
│   └── AbstractDocumentController.php  # Logica specifica documenti
├── OrdineVenditaController.php         # Esempio implementazione
└── ...

app/Helpers/
└── CrudRoutePermissionHelper.php       # Generazione automatica rotte

resources/views/pdf/
├── header-pdf.blade.php                # Header generico
├── document.blade.php                  # Template generico
├── ordine-vendita.blade.php            # Template specifico
└── ...
```

### Frontend (Vue.js)
```
resources/js/
├── lib/
│   ├── downloadService.js              # Gestione download
│   └── crudTable.js                    # Logica tabella
├── Pages/
│   └── Crud/
│       └── CrudIndex.vue               # Tabella principale
└── Components/
    └── Dialog/
        └── DialogShow.vue              # Dialog con pulsante PDF
```

## Gestione Errori e Fallback

### 1. **Backend Errori**
```php
try {
    // Generazione PDF
    return $pdf->download();
} catch (\Exception $e) {
    \Illuminate\Support\Facades\Log::error('Errore PDF: ' . $e->getMessage());
    
    return response()->json([
        'error' => 'Errore nella generazione del PDF',
        'message' => $e->getMessage()
    ], 500);
}
```

### 2. **Frontend Errori**
```javascript
.catch(error => {
    console.error('Errore PDF:', error);
    this.stopLoading('pdf', recordId);
    
    // Fallback: apertura in nuova finestra
    this.fallbackDownload(urlOpen);
    this.showNotification('Errore nella generazione del PDF - Tentativo con apertura diretta', 'warning');
});
```

### 3. **Fallback Automatico**
```javascript
fallbackDownload(url) {
    console.log('Tentativo fallback con apertura in nuova finestra...');
    window.open(url, '_blank');
}
```

## Conclusione

Il pattern PDF del CRM è progettato per essere:

- **Coerente** con il resto del sistema
- **Flessibile** per diverse esigenze  
- **Manutenibile** nel lungo termine
- **Automatizzato** per ridurre il codice boilerplate
- **Robusto** con gestione errori completa
- **User-friendly** con loading states e fallback

Seguendo questo pattern, aggiungere nuovi tipi di PDF diventa semplice e mantiene la coerenza dell'intero sistema, garantendo un'esperienza utente ottimale e una manutenzione semplificata. 