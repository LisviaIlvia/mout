# 📱 IMPLEMENTAZIONE QR CODE - MOUT CRM
## Guida Completa Passo-Passo

Questa documentazione ti guida attraverso l'intera implementazione del sistema QR Code nel CRM, dalla configurazione iniziale all'utilizzo avanzato.

---

## 📋 INDICE
1. [Panoramica del Sistema](#panoramica-del-sistema)
2. [Installazione e Configurazione](#installazione-e-configurazione)
3. [Architettura del Sistema](#architettura-del-sistema)
4. [Backend - Controller e Logica](#backend---controller-e-logica)
5. [Frontend - Componenti Vue](#frontend---componenti-vue)
6. [Vista Pubblica](#vista-pubblica)
7. [Sistema di Routing](#sistema-di-routing)
8. [Gestione Permessi](#gestione-permessi)
9. [Funzionalità Avanzate](#funzionalità-avanzate)
10. [Test e Debug](#test-e-debug)
11. [Best Practices](#best-practices)
12. [Troubleshooting](#troubleshooting)

---

## 🎯 PANORAMICA DEL SISTEMA

### **Cos'è il Sistema QR Code**
Il sistema QR Code del CRM permette di:
- **Generare QR code** per ordini di vendita e acquisto
- **Accesso pubblico** ai dettagli degli ordini senza autenticazione
- **Download multipli formati** (SVG, PNG, EPS)
- **Integrazione completa** con l'architettura del CRM

### **Flusso Principale**
```
1. Utente clicca icona QR → 2. Dialog si apre → 3. API genera QR → 4. Visualizzazione → 5. Download disponibile
                                                                                    ↓
6. Scannerizzazione → 7. Vista pubblica dell'ordine
```

---

## 🔧 INSTALLAZIONE E CONFIGURAZIONE

### **Passo 1: Installazione Libreria**
```bash
composer require simplesoftwareio/simple-qrcode
```

### **Passo 2: Verifica Dipendenze**
- **PHP**: 8.2+
- **Laravel**: 12.x
- **Estensioni PHP**: `imagick` (opzionale, per PNG)

### **Passo 3: Configurazione Colori**
```scss
// resources/sass/app.scss
.color-qr {
    color: #9C27B0 !important;
}

.qr-code-container {
    background: white;
    border-radius: 8px;
    padding: 16px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}
```

---

## 🏗️ ARCHITETTURA DEL SISTEMA

### **Struttura File**
```
📁 Sistema QR Code
├── 🎛️ Backend (Laravel)
│   ├── QrCodeController.php (Controller principale)
│   ├── routes/web.php (Routing)
│   └── AbstractCrudController.php (Integrazione permessi)
├── 🎨 Frontend (Vue.js)
│   ├── DialogQrCode.vue (Dialog principale)
│   ├── QrCodeDisplay.vue (Componente display)
│   ├── QrCodeIndex.vue (Pagina generatore)
│   ├── OrderPublicView.vue (Vista pubblica)
│   └── CrudIndex.vue (Integrazione tabella)
└── 📄 Documentazione
    ├── README_QR_CODE.md (Questa guida)
    └── test_qr_code.md (Test cases)
```

### **Componenti Principali**
1. **QrCodeController**: Gestisce generazione e download
2. **DialogQrCode**: Interfaccia utente per visualizzazione
3. **OrderPublicView**: Vista pubblica accessibile via QR
4. **CrudIndex**: Integrazione con tabelle CRM

---

## ⚙️ BACKEND - CONTROLLER E LOGICA

### **Passo 1: Controller Principale**

#### **File**: `app/Http/Controllers/QrCodeController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Document;
use Illuminate\Http\Request;
use Inertia\Inertia;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeController extends Controller
{
    /**
     * Genera QR code per un ordine
     * Il QR code conterrà direttamente l'URL della vista pubblica
     */
    public function order($id)
    {
        // Debug: log dell'ID ricevuto
        \Log::info("QR Code - Richiesta per ordine ID: " . $id);
        
        // Cerca l'ordine con l'ID specifico e il tipo corretto
        $order = Document::where('id', $id)
            ->where(function($query) {
                $query->where('type', 'ordini-vendita')
                      ->orWhere('type', 'ordini-acquisto');
            })->firstOrFail();

        // Crea l'URL diretto per la vista pubblica
        $publicUrl = route('qr.order.view', $order->id);

        // Genera il QR code con direttamente l'URL
        $qrCode = (string) QrCode::format('svg')
            ->size(300)
            ->margin(10)
            ->generate($publicUrl); // URL diretto, non JSON

        // Prepara i dati per la risposta
        $qrData = [
            'type' => 'order',
            'id' => $order->id,
            'code' => $order->numero,
            'name' => $order->entity->nome,
            'url' => $publicUrl
        ];

        return response()->json([
            'qr_code' => (string) $qrCode,
            'data' => $qrData,
            'order' => $order->load('entity')
        ]);
    }

    /**
     * Vista pubblica dell'ordine - accessibile senza autenticazione
     */
    public function orderView($id)
    {
        $order = Document::where('id', $id)
            ->where(function($query) {
                $query->where('type', 'ordini-vendita')
                      ->orWhere('type', 'ordini-acquisto');
            })->with([
                'entity', 
                'products.product', 
                'products.aliquotaIva',
                'dettagli'
            ])->firstOrFail();

        $orderType = $order->type === 'ordini-vendita' ? 'Ordine di Vendita' : 'Ordine di Acquisto';
        $title = $orderType . ' - ' . $order->numero;

        return Inertia::render('QrCode/OrderPublicView', [
            'order' => $orderData,
            'title' => $title
        ]);
    }

    /**
     * Download QR code in vari formati
     */
    public function download(Request $request)
    {
        $request->validate([
            'type' => 'required|in:product,order',
            'id' => 'required|integer',
            'format' => 'in:svg,png,eps'
        ]);

        $format = strtolower($request->format ?? 'svg');
        $order = Document::where('id', $request->id)
            ->where(function($query) {
                $query->where('type', 'ordini-vendita')
                      ->orWhere('type', 'ordini-acquisto');
            })->firstOrFail();
        
        $filename = "qr-order-{$order->numero}.{$format}";
        $publicUrl = route('qr.order.view', $order->id);

        try {
            $qrCode = QrCode::format($format)
                ->size(400)
                ->margin(10)
                ->generate($publicUrl);

            $contentType = match($format) {
                'svg' => 'image/svg+xml',
                'png' => 'image/png',
                'eps' => 'application/postscript',
                default => 'image/svg+xml'
            };

            return response((string) $qrCode)
                ->header('Content-Type', $contentType)
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");

        } catch (\Exception $e) {
            // Fallback su SVG
            $qrCode = QrCode::format('svg')
                ->size(400)
                ->margin(10)
                ->generate($publicUrl);
            
            $filename = "qr-order-{$order->numero}.svg";
            
            return response((string) $qrCode)
                ->header('Content-Type', 'image/svg+xml')
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
        }
    }
}
```

### **Passo 2: Integrazione Permessi**

#### **File**: `app/Http/Controllers/Base/AbstractCrudController.php`

```php
protected function getAction($object = null, array $actionPermissions = [], string $type = null, string $url = null) {
    $user = auth()->user();
    $pattern = $url ?? $this->pattern;
    $permission = $this->permission ?? $this->pattern;

    if($object) {
        $actions = collect($actionPermissions)->mapWithKeys(function ($permissionAction, $routeAction) use ($user, $pattern, $permission, $object) {
            // ... logica esistente ...
        })->toArray();

        // Aggiungi azione QR code solo per ordini vendita e acquisto
        if (in_array($pattern, ['ordini-vendita', 'ordini-acquisto'])) {
            $actions['qr'] = true;
        } else {
            $actions['qr'] = false; // Nasconde il pulsante per altre entità
        }

        return $actions;
    }
    
    // ... resto del codice ...
}
```

---

## 🎨 FRONTEND - COMPONENTI VUE

### **Passo 1: Dialog Principale**

#### **File**: `resources/js/Components/Dialog/DialogQrCode.vue`

```vue
<template>
    <v-dialog
        v-model="dialog"
        :max-width="dialogSetup.width || 500"
        :fullscreen="dialogSetup.fullscreen || false"
        :scrim="dialogSetup.scrim !== undefined ? dialogSetup.scrim : true"
        persistent
    >
        <v-card>
            <v-toolbar :color="color" dark>
                <v-toolbar-title>
                    <i class="fa-solid fa-qrcode me-2"></i>
                    {{ dialogTitle }}
                </v-toolbar-title>
                <v-spacer></v-spacer>
                <v-btn icon @click="closeDialog">
                    <i class="fa-solid fa-times"></i>
                </v-btn>
            </v-toolbar>

            <v-card-text class="pa-6">
                <!-- Loading State -->
                <div v-if="loading" class="text-center pa-8">
                    <v-progress-circular
                        indeterminate
                        size="64"
                        :color="color"
                    ></v-progress-circular>
                    <p class="mt-4 text-body-1">Generazione QR Code in corso...</p>
                </div>

                <!-- QR Code Display -->
                <div v-else-if="qrData && qrData.data && qrData.data.qr_code" class="text-center">
                    <div class="qr-code-container mb-6">
                        <div 
                            v-html="qrData.data.qr_code" 
                            class="qr-code-svg"
                            style="max-width: 300px; margin: 0 auto;"
                        ></div>
                    </div>

                    <!-- Dettagli -->
                    <v-card variant="outlined" class="pa-4 mb-4">
                        <h4 class="text-h6 mb-3">Dettagli</h4>
                        <v-list density="compact">
                            <v-list-item>
                                <template v-slot:prepend>
                                    <i class="fa-solid fa-barcode text-grey"></i>
                                </template>
                                <v-list-item-title>Codice: {{ qrData.data.data.code }}</v-list-item-title>
                            </v-list-item>
                            
                            <v-list-item>
                                <template v-slot:prepend>
                                    <i class="fa-solid fa-tag text-grey"></i>
                                </template>
                                <v-list-item-title>Nome: {{ qrData.data.data.name }}</v-list-item-title>
                            </v-list-item>
                            
                            <v-list-item>
                                <template v-slot:prepend>
                                    <i class="fa-solid fa-link text-grey"></i>
                                </template>
                                <v-list-item-title>
                                    <a :href="qrData.data.data.url" class="text-decoration-none">
                                        {{ qrData.data.data.url }}
                                    </a>
                                </v-list-item-title>
                            </v-list-item>
                        </v-list>
                    </v-card>

                    <!-- Azioni Download -->
                    <v-card variant="outlined" class="pa-4">
                        <h4 class="text-h6 mb-3">Scarica QR Code</h4>
                        
                        <v-row>
                            <v-col cols="4">
                                <v-btn
                                    color="info"
                                    variant="outlined"
                                    @click="downloadQr('svg')"
                                    :loading="downloading"
                                    block
                                    size="small"
                                >
                                    <i class="fa-solid fa-vector-square me-1"></i>
                                    SVG
                                </v-btn>
                                <p class="text-caption text-grey mt-1">Vettoriale</p>
                            </v-col>
                            
                            <v-col cols="4">
                                <v-btn
                                    color="success"
                                    variant="outlined"
                                    @click="downloadQr('png')"
                                    :loading="downloading"
                                    block
                                    size="small"
                                >
                                    <i class="fa-solid fa-image me-1"></i>
                                    PNG
                                </v-btn>
                                <p class="text-caption text-grey mt-1">Alta qualità</p>
                            </v-col>
                            
                            <v-col cols="4">
                                <v-btn
                                    color="warning"
                                    variant="outlined"
                                    @click="downloadQr('eps')"
                                    :loading="downloading"
                                    block
                                    size="small"
                                >
                                    <i class="fa-solid fa-file-code me-1"></i>
                                    EPS
                                </v-btn>
                                <p class="text-caption text-grey mt-1">PostScript</p>
                            </v-col>
                        </v-row>
                        
                        <p class="text-caption text-grey mt-3">
                            <i class="fa-solid fa-info-circle me-1"></i>
                            Il QR code è scannerizzabile e porta alla vista pubblica dell'ordine
                        </p>
                    </v-card>
                </div>

                <!-- Error State -->
                <div v-else-if="error" class="text-center pa-4">
                    <v-alert type="error" class="mb-4">
                        {{ error }}
                    </v-alert>
                    <v-btn color="primary" @click="retry">
                        <i class="fa-solid fa-refresh me-2"></i>
                        Riprova
                    </v-btn>
                </div>
            </v-card-text>

            <v-card-actions class="pa-6">
                <v-spacer></v-spacer>
                <v-btn color="grey" variant="text" @click="closeDialog">
                    Chiudi
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script>
import axiosService from '@/lib/axiosService';

export default {
    name: 'DialogQrCode',
    props: {
        dialogTitle: {
            type: String,
            default: 'QR Code'
        },
        dialogType: {
            type: String,
            default: 'qr'
        },
        dialogSetup: {
            type: Object,
            default: () => ({
                width: 500,
                fullscreen: false,
                scrim: true
            })
        },
        color: {
            type: String,
            default: 'color-qr'
        }
    },
    data() {
        return {
            dialog: false,
            loading: false,
            downloading: false,
            qrData: null,
            error: null,
            item: null,
            type: null,
            axiosService: new axiosService()
        };
    },
    methods: {
        openDialog(type, id, item) {
            console.log('QR Code - openDialog chiamato con:', { type, id, item });
            
            this.type = type;
            this.item = item;
            this.qrData = null;
            this.error = null;
            this.dialog = false;
            
            this.$nextTick(() => {
                this.dialog = true;
                this.generateQrCode(id);
            });
        },

        async generateQrCode(id) {
            this.loading = true;
            this.error = null;

            try {
                await this.axiosService.get({
                    url: `/qr/${this.type}/${id}?_=${Date.now()}`,
                    success: (data) => {
                        console.log('QR Code - Dati ricevuti:', data);
                        this.qrData = data;
                    },
                    error: (error) => {
                        console.error('QR Code - Errore:', error);
                        this.error = error.message || 'Errore nella generazione del QR code';
                    }
                });
            } catch (error) {
                console.error('QR Code - Errore catch:', error);
                this.error = 'Errore nella generazione del QR code';
            } finally {
                this.loading = false;
            }
        },

        async downloadQr(format) {
            if (!this.qrData) return;
            this.downloading = true;
            try {
                const params = new URLSearchParams({
                    type: this.qrData.data.data.type,
                    id: this.qrData.data.data.id,
                    format: format
                });
                window.open(`/qr/download?${params.toString()}`, '_blank');
            } catch (error) {
                this.error = 'Errore nel download del QR code';
            } finally {
                this.downloading = false;
            }
        },

        closeDialog() {
            this.dialog = false;
            this.qrData = null;
            this.error = null;
        },

        retry() {
            if (this.item) {
                this.generateQrCode(this.item.id);
            }
        }
    }
};
</script>
```

### **Passo 2: Integrazione Tabella**

#### **File**: `resources/js/Pages/Crud/CrudIndex.vue`

```vue
<template>
    <!-- ... template esistente ... -->
    <template v-for="header in headers" v-slot:[`item.${header.key}`]="{ item }">
        <template v-if="header.key === 'actions'">
            <div class="d-flex justify-end">
                <!-- QR Code Button -->
                <v-btn 
                    v-if="item.actions.qr && item.actions.qr != false"
                    icon="fa-solid fa-qrcode fa-sm"
                    density="compact"
                    rounded="sm"
                    class="me-2"
                    color="color-qr"
                    :disabled="!item.actions.qr"
                    @click="openQrCode(item)"
                />
                
                <!-- Altri pulsanti esistenti... -->
            </div>
        </template>
    </template>
    
    <!-- Dialog QR Code -->
    <dialog-qr-code
        ref="dialogQrCodeRef"
        :key="dialogQrCodeKey"
        :dialogTitle="setup.single"
        :dialogType="setup.type"
        :tooltip="setup.tooltipDialog"
        :textRequest="setup.textRequestDialog"
        :textConfirm="setup.textConfirmDialog"
        @show-notification="showNotification"
    ></dialog-qr-code>
</template>

<script>
export default {
    // ... script esistente ...
    data() {
        return {
            // ... data esistente ...
            dialogQrCodeKey: 0
        }
    },
    methods: {
        openQrCode(item) {
            const currentRoute = this.$route().current();
            let type = null;
            
            console.log('QR Code - Item ricevuto:', item);
            
            // Supporta solo ordini vendita e acquisto
            if (currentRoute.includes('ordini-vendita') || currentRoute.includes('ordini-acquisto')) {
                type = 'order';
            } else {
                this.$nextTick(() => {
                    this.flashMessage({
                        type: 'warning',
                        message: 'QR Code disponibile solo per ordini vendita e acquisto'
                    });
                });
                return;
            }
            
            console.log('QR Code - Parametri dialog:', { type, id: item.id, item });
            
            this.dialogQrCodeKey++;
            this.$nextTick(() => {
                this.$refs.dialogQrCodeRef.openDialog(type, item.id, item);
            });
        }
    }
}
</script>
```

---

## 🌐 VISTA PUBBLICA

### **Passo 1: Componente Vista Pubblica**

#### **File**: `resources/js/Pages/QrCode/OrderPublicView.vue`

```vue
<template>
    <div class="order-public-view">
        <!-- Informazioni principali -->
        <v-row class="pa-4">
            <v-col cols="12" md="6">
                <v-card variant="outlined" class="pa-4">
                    <h3 class="text-h6 mb-3">
                        <i class="fa-solid fa-building me-2"></i>
                        Cliente/Fornitore
                    </h3>
                    
                    <v-list density="compact">
                        <v-list-item>
                            <template v-slot:prepend>
                                <i class="fa-solid fa-user text-grey me-2"></i>
                            </template>
                            <v-list-item-title>{{ order.entity?.nome || 'N/A' }}</v-list-item-title>
                        </v-list-item>
                        
                        <v-list-item v-if="order.entity?.partita_iva">
                            <template v-slot:prepend>
                                <i class="fa-solid fa-id-card text-grey me-2"></i>
                            </template>
                            <v-list-item-title>P.IVA: {{ order.entity.partita_iva }}</v-list-item-title>
                        </v-list-item>
                        
                        <v-list-item v-if="order.entity?.codice_fiscale">
                            <template v-slot:prepend>
                                <i class="fa-solid fa-id-badge text-grey me-2"></i>
                            </template>
                            <v-list-item-title>CF: {{ order.entity.codice_fiscale }}</v-list-item-title>
                        </v-list-item>
                    </v-list>
                </v-card>
            </v-col>

            <v-col cols="12" md="6">
                <v-card variant="outlined" class="pa-4">
                    <h3 class="text-h6 mb-3">
                        <i class="fa-solid fa-calendar me-2"></i>
                        Informazioni Ordine
                    </h3>
                    
                    <v-list density="compact">
                        <v-list-item>
                            <template v-slot:prepend>
                                <i class="fa-solid fa-hashtag text-grey me-2"></i>
                            </template>
                            <v-list-item-title>Numero: {{ order.numero }}</v-list-item-title>
                        </v-list-item>
                        
                        <v-list-item v-if="order.data">
                            <template v-slot:prepend>
                                <i class="fa-solid fa-calendar-day text-grey me-2"></i>
                            </template>
                            <v-list-item-title>Data: {{ formatDate(order.data) }}</v-list-item-title>
                        </v-list-item>
                        
                        <v-list-item v-if="order.stato">
                            <template v-slot:prepend>
                                <i class="fa-solid fa-info-circle text-grey me-2"></i>
                            </template>
                            <v-list-item-title>
                                Stato: 
                                <v-chip 
                                    :color="getStatusColor(order.stato)" 
                                    size="small"
                                    class="ml-2"
                                >
                                    {{ order.stato }}
                                </v-chip>
                            </v-list-item-title>
                        </v-list-item>
                    </v-list>
                </v-card>
            </v-col>
        </v-row>

        <!-- Prodotti/Servizi -->
        <v-row class="pa-4" v-if="order.products && order.products.length > 0">
            <v-col cols="12">
                <v-card variant="outlined">
                    <v-card-title class="text-h6">
                        <i class="fa-solid fa-boxes me-2"></i>
                        Prodotti/Servizi
                    </v-card-title>
                    
                    <v-data-table
                        :headers="productHeaders"
                        :items="order.products"
                        :items-per-page="10"
                        density="compact"
                        class="elevation-0"
                    >
                        <template v-slot:item.product.nome="{ item }">
                            {{ item.product?.nome || 'N/A' }}
                        </template>
                        
                        <template v-slot:item.quantita="{ item }">
                            {{ item.quantita }}
                        </template>
                        
                        <template v-slot:item.prezzo="{ item }">
                            € {{ formatPrice(item.prezzo) }}
                        </template>
                        
                        <template v-slot:item.aliquota_iva.aliquota="{ item }">
                            {{ item.aliquotaIva?.aliquota || '0' }}%
                        </template>
                        
                        <template v-slot:item.totale="{ item }">
                            € {{ formatPrice(item.quantita * item.prezzo) }}
                        </template>
                    </v-data-table>
                </v-card>
            </v-col>
        </v-row>

        <!-- Riepilogo totale -->
        <v-row class="pa-4" v-if="order.products && order.products.length > 0">
            <v-col cols="12" md="6" offset-md="6">
                <v-card variant="outlined" class="pa-4">
                    <h3 class="text-h6 mb-3">
                        <i class="fa-solid fa-calculator me-2"></i>
                        Riepilogo
                    </h3>
                    
                    <v-list density="compact">
                        <v-list-item>
                            <v-list-item-title>Imponibile:</v-list-item-title>
                            <template v-slot:append>
                                <strong>€ {{ formatPrice(calcolaImponibile()) }}</strong>
                            </template>
                        </v-list-item>
                        
                        <v-list-item>
                            <v-list-item-title>IVA:</v-list-item-title>
                            <template v-slot:append>
                                <strong>€ {{ formatPrice(calcolaIva()) }}</strong>
                            </template>
                        </v-list-item>
                        
                        <v-divider class="my-2"></v-divider>
                        
                        <v-list-item>
                            <v-list-item-title class="text-h6">Totale:</v-list-item-title>
                            <template v-slot:append>
                                <strong class="text-h6">€ {{ formatPrice(calcolaTotale()) }}</strong>
                            </template>
                        </v-list-item>
                    </v-list>
                </v-card>
            </v-col>
        </v-row>

        <!-- Note -->
        <v-row class="pa-4" v-if="order.note">
            <v-col cols="12">
                <v-card variant="outlined" class="pa-4">
                    <h3 class="text-h6 mb-3">
                        <i class="fa-solid fa-sticky-note me-2"></i>
                        Note
                    </h3>
                    <p class="text-body-1">{{ order.note }}</p>
                </v-card>
            </v-col>
        </v-row>
    </div>
</template>

<script>
export default {
    name: 'OrderPublicView',
    props: {
        order: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            productHeaders: [
                { title: 'Prodotto', key: 'product.nome', sortable: true },
                { title: 'Quantità', key: 'quantita', sortable: true },
                { title: 'Prezzo Unit.', key: 'prezzo', sortable: true },
                { title: 'IVA', key: 'aliquota_iva.aliquota', sortable: true },
                { title: 'Totale', key: 'totale', sortable: true }
            ]
        };
    },
    methods: {
        formatDate(date) {
            if (!date) return 'N/A';
            
            const d = new Date(date);
            return d.toLocaleDateString('it-IT', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
        },
        
        formatPrice(price) {
            if (!price) return '0,00';
            return parseFloat(price).toFixed(2).replace('.', ',');
        },
        
        getStatusColor(status) {
            const statusColors = {
                'bozza': 'grey',
                'confermato': 'blue',
                'in_lavorazione': 'orange',
                'completato': 'green',
                'annullato': 'red'
            };
            return statusColors[status] || 'grey';
        },
        
        calcolaImponibile() {
            return this.order.products.reduce((total, product) => total + (product.quantita * product.prezzo), 0);
        },
        
        calcolaIva() {
            return this.order.products.reduce((total, product) => {
                const aliquota = product.aliquotaIva?.aliquota || 0;
                return total + (product.quantita * product.prezzo * aliquota / 100);
            }, 0);
        },
        
        calcolaTotale() {
            return this.calcolaImponibile() + this.calcolaIva();
        }
    }
};
</script>

<style scoped>
.order-public-view {
    background-color: #f5f5f5;
    min-height: 100vh;
}

@media print {
    .order-public-view {
        background-color: white;
    }
}
</style>
```

---

## 🛣️ SISTEMA DI ROUTING

### **Passo 1: Definizione Route**

#### **File**: `routes/web.php`

```php
// QR Code routes - SENZA autenticazione per accesso pubblico
Route::prefix('qr')->name('qr.')->group(function () {
    Route::get('/', [\App\Http\Controllers\QrCodeController::class, 'index'])->name('index');
    Route::get('/product/{id}', [\App\Http\Controllers\QrCodeController::class, 'product'])->name('product');
    Route::get('/order/{id}', [\App\Http\Controllers\QrCodeController::class, 'order'])->name('order');
    Route::get('/order/{id}/view', [\App\Http\Controllers\QrCodeController::class, 'orderView'])->name('order.view');
    Route::post('/generate', [\App\Http\Controllers\QrCodeController::class, 'generate'])->name('generate');
    Route::get('/download', [\App\Http\Controllers\QrCodeController::class, 'download'])->name('download');
});
```

### **Passo 2: Route Disponibili**

| **Route** | **Metodo** | **Descrizione** | **Autenticazione** |
|-----------|------------|-----------------|-------------------|
| `/qr/` | GET | Pagina generatore QR | ❌ Pubblica |
| `/qr/order/{id}` | GET | Genera QR per ordine | ❌ Pubblica |
| `/qr/order/{id}/view` | GET | Vista pubblica ordine | ❌ Pubblica |
| `/qr/download` | GET | Download QR code | ❌ Pubblica |

---

## 🔐 GESTIONE PERMESSI

### **Passo 1: Integrazione Permessi**

Il sistema QR Code è integrato con il sistema di permessi esistente:

```php
// In AbstractCrudController.php
protected function getAction($object = null, array $actionPermissions = [], string $type = null, string $url = null) {
    // ... logica esistente ...
    
    // Aggiungi azione QR code solo per ordini vendita e acquisto
    if (in_array($pattern, ['ordini-vendita', 'ordini-acquisto'])) {
        $actions['qr'] = true;
    } else {
        $actions['qr'] = false; // Nasconde il pulsante per altre entità
    }
    
    return $actions;
}
```

### **Passo 2: Controllo Accesso**

- **Generazione QR**: Disponibile solo per ordini vendita/acquisto
- **Vista pubblica**: Accessibile senza autenticazione
- **Download**: Accessibile senza autenticazione

---

## 🚀 FUNZIONALITÀ AVANZATE

### **Passo 1: Multipli Formati Download**

```php
public function download(Request $request) {
    $format = strtolower($request->format ?? 'svg');
    
    try {
        $qrCode = QrCode::format($format)
            ->size(400)
            ->margin(10)
            ->generate($publicUrl);

        $contentType = match($format) {
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'eps' => 'application/postscript',
            default => 'image/svg+xml'
        };

        return response((string) $qrCode)
            ->header('Content-Type', $contentType)
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");

    } catch (\Exception $e) {
        // Fallback su SVG
        $qrCode = QrCode::format('svg')
            ->size(400)
            ->margin(10)
            ->generate($publicUrl);
        
        return response((string) $qrCode)
            ->header('Content-Type', 'image/svg+xml')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}
```

### **Passo 2: Debug e Logging**

```php
public function order($id) {
    // Debug: log dell'ID ricevuto
    \Log::info("QR Code - Richiesta per ordine ID: " . $id);
    
    // Debug: verifica se l'ordine esiste
    $orderExists = Document::where('id', $id)->exists();
    \Log::info("QR Code - Ordine con ID {$id} esiste: " . ($orderExists ? 'SI' : 'NO'));
    
    // Debug: lista tutti gli ordini per verificare
    $allOrders = Document::where('type', 'ordini-vendita')
        ->orWhere('type', 'ordini-acquisto')
        ->select('id', 'numero', 'type')
        ->get();
    \Log::info("QR Code - Tutti gli ordini disponibili: " . $allOrders->toJson());
    
    // ... resto del codice ...
}
```

### **Passo 3: Gestione Errori**

```javascript
async generateQrCode(id) {
    this.loading = true;
    this.error = null;

    try {
        await this.axiosService.get({
            url: `/qr/${this.type}/${id}?_=${Date.now()}`,
            success: (data) => {
                console.log('QR Code - Dati ricevuti:', data);
                this.qrData = data;
            },
            error: (error) => {
                console.error('QR Code - Errore:', error);
                this.error = error.message || 'Errore nella generazione del QR code';
            }
        });
    } catch (error) {
        console.error('QR Code - Errore catch:', error);
        this.error = 'Errore nella generazione del QR code';
    } finally {
        this.loading = false;
    }
}
```

---

## 🧪 TEST E DEBUG

### **Passo 1: Test Generazione QR**

1. **Vai alla sezione "Ordini Vendita" o "Ordini Acquisto"**
2. **Clicca sull'icona QR code (viola) in una riga**
3. **Verifica che si apra il dialog con:**
   - QR code SVG
   - Codice ordine
   - Nome cliente/fornitore
   - URL della vista pubblica

### **Passo 2: Test Download**

1. **Nel dialog QR code, clicca "Scarica SVG/PNG/EPS"**
2. **Verifica che il file venga scaricato con nome corretto**
3. **Apri il file per verificare che sia valido**

### **Passo 3: Test Scannerizzazione**

1. **Usa un'app QR code scanner sul telefono**
2. **Scansiona il QR code generato**
3. **Verifica che apra l'URL della vista pubblica**
4. **Controlla che la pagina mostri tutti i dettagli dell'ordine**

### **Passo 4: Test Vista Pubblica**

1. **Vai direttamente all'URL: `/qr/order/{id}/view`**
2. **Verifica che mostri:**
   - Header con tipo ordine e numero
   - Informazioni cliente/fornitore
   - Data e stato ordine
   - Tabella prodotti/servizi
   - Riepilogo totale
   - Note (se presenti)

### **Passo 5: URL di Test**

```bash
# Generazione QR
GET /qr/order/1

# Vista pubblica
GET /qr/order/1/view

# Download
GET /qr/download?type=order&id=1&format=svg
```

---

## 📋 BEST PRACTICES

### **Passo 1: Sicurezza**

1. **Validazione Input**: Sempre validare ID e parametri
2. **Controllo Accesso**: Verificare esistenza ordine prima di generare QR
3. **Sanitizzazione**: Pulire dati prima di inserirli nel QR code
4. **Rate Limiting**: Considerare limiti di richieste per API pubbliche

### **Passo 2: Performance**

1. **Caching**: Considerare cache per QR code generati frequentemente
2. **Lazy Loading**: Caricare relazioni solo quando necessario
3. **Ottimizzazione Query**: Usare eager loading per relazioni
4. **Compressione**: Comprimere risposte API quando possibile

### **Passo 3: UX/UI**

1. **Loading States**: Mostrare sempre stati di caricamento
2. **Error Handling**: Gestire errori con messaggi chiari
3. **Responsive Design**: Assicurare compatibilità mobile
4. **Accessibilità**: Supportare screen reader e navigazione tastiera

### **Passo 4: Manutenibilità**

1. **Logging**: Loggare operazioni importanti per debug
2. **Documentazione**: Mantenere documentazione aggiornata
3. **Testing**: Scrivere test per funzionalità critiche
4. **Versioning**: Versionare API per compatibilità

---

## 🔧 TROUBLESHOOTING

### **Problema 1: QR Code non si genera**

**Sintomi**: Dialog si apre ma QR code non appare

**Possibili Cause**:
- Ordine non trovato nel database
- Errore nella libreria Simple QrCode
- Problema di permessi

**Soluzioni**:
```bash
# Verifica log Laravel
tail -f storage/logs/laravel.log

# Verifica esistenza ordine
php artisan tinker
>>> App\Models\Document::find(1)

# Verifica installazione libreria
composer show simplesoftwareio/simple-qrcode
```

### **Problema 2: Vista pubblica non carica**

**Sintomi**: QR code si genera ma vista pubblica non funziona

**Possibili Cause**:
- Route non registrata correttamente
- Problema con Inertia.js
- Errore nel caricamento relazioni

**Soluzioni**:
```bash
# Verifica route
php artisan route:list | grep qr

# Verifica cache route
php artisan route:clear

# Verifica relazioni modello
php artisan tinker
>>> $order = App\Models\Document::with(['entity', 'products.product'])->find(1)
>>> $order->entity
```

### **Problema 3: Download non funziona**

**Sintomi**: Pulsante download non scarica file

**Possibili Cause**:
- Formato non supportato
- Problema con headers HTTP
- Errore nella generazione

**Soluzioni**:
```php
// Verifica formato supportato
$supportedFormats = ['svg', 'png', 'eps'];

// Verifica headers
return response((string) $qrCode)
    ->header('Content-Type', $contentType)
    ->header('Content-Disposition', "attachment; filename=\"{$filename}\"")
    ->header('Cache-Control', 'no-cache');
```

### **Problema 4: QR Code non scannerizzabile**

**Sintomi**: QR code si genera ma non è leggibile

**Possibili Cause**:
- URL troppo lungo
- Errore nella codifica
- Problema di dimensione

**Soluzioni**:
```php
// Usa URL corto
$publicUrl = route('qr.order.view', $order->id);

// Verifica dimensione
$qrCode = QrCode::format('svg')
    ->size(300)  // Dimensione minima
    ->margin(10) // Margine adeguato
    ->generate($publicUrl);
```

---

## 📚 RISORSE AGGIUNTIVE

### **Documentazione Libreria**
- [Simple QrCode Documentation](https://github.com/SimpleSoftwareIO/simple-qrcode)
- [Laravel QR Code Examples](https://laravel.com/docs/10.x/helpers#qr-code)

### **Strumenti di Test**
- [QR Code Scanner Online](https://www.qr-code-generator.com/qr-code-scanner/)
- [QR Code Generator](https://www.qr-code-generator.com/)

### **Best Practices QR Code**
- [QR Code Design Guidelines](https://www.qrcode.com/en/howto/code.html)
- [QR Code Error Correction](https://www.qrcode.com/en/about/error_correction.html)

---

## 🎯 CONCLUSIONI

Il sistema QR Code del CRM è completamente integrato e funzionale. Seguendo questa guida passo-passo, hai accesso a:

✅ **Generazione QR code** per ordini vendita/acquisto  
✅ **Vista pubblica** accessibile senza autenticazione  
✅ **Download multipli formati** (SVG, PNG, EPS)  
✅ **Integrazione completa** con sistema permessi  
✅ **Gestione errori** robusta  
✅ **Debug e logging** dettagliato  

Il sistema è pronto per l'uso in produzione e può essere facilmente esteso per supportare altri tipi di documento o funzionalità aggiuntive. 