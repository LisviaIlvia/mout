<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeController extends Controller
{
    /**
     * Genera QR code per un prodotto
     * NOTA: Per ora disabilitato, focus solo su ordini
     */
    public function product($id)
    {
        // Per ora disabilitato - focus solo su ordini
        abort(404, 'Funzionalità prodotto temporaneamente disabilitata');
        
        $product = Product::findOrFail($id);
        
        $qrData = [
            'type' => 'product',
            'id' => $product->id,
            'code' => $product->numero,
            'name' => $product->nome,
            'url' => route('qr.product', $product->id)
        ];

        $qrCode = (string) QrCode::format('svg')
            ->size(300)
            ->margin(10)
            ->generate(json_encode($qrData));

        return response()->json([
            'qr_code' => (string) $qrCode,
            'data' => $qrData,
            'product' => $product
        ]);
    }

    /**
     * Genera QR code per un ordine (vendita o acquisto)
     * Il QR code conterrà direttamente l'URL della vista pubblica
     */
    public function order($id)
    {
        // CORREZIONE: Cerca l'ordine con l'ID specifico e il tipo corretto
        $order = Document::where('id', $id)
            ->where(function($query) {
                $query->where('type', 'ordini-vendita');
                    //   ->orWhere('type', 'ordini-acquisto');
            })->firstOrFail();

        // Crea l'URL diretto per la vista pubblica
        $publicUrl = route('qr.order.view', $order->id);

        // Genera il QR code con direttamente l'URL
        // Questo permette agli scanner QR di riconoscerlo come link
        $qrCode = (string) QrCode::format('svg')
            ->size(300)
            ->margin(10)
            ->generate($publicUrl); // URL diretto, non JSON

        // Prepara i dati per la risposta (per visualizzazione nel dialog)
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
     * Questa è la pagina che viene aperta quando si scansiona il QR code
     */
    public function orderView($id)
    {
        // Cerca l'ordine con l'ID specifico e il tipo corretto
        $order = Document::where('id', $id)
            ->where(function($query) {
                $query->where('type', 'ordini-vendita');
                    //   ->orWhere('type', 'ordini-acquisto');
            })->with([
                'entity', 
                'products.product', 
                'products.aliquotaIva',  // Carica l'aliquota IVA direttamente dal DocumentProduct
                'products.product.categories', // Carica le categorie dei prodotti
                'altro.aliquotaIva', // Carica gli altri elementi
                'descrizioni', // Carica le descrizioni
                'dettagli', // Carica i dettagli tecnici
                'indirizzo', // Carica l'indirizzo
                'media' // Carica gli allegati
            ]) // Carica le relazioni necessarie
            ->firstOrFail();

        // Determina il tipo di ordine per il titolo
        $orderType = 'Ordine di Vendita' ;
        $title = $orderType . ' - ' . $order->numero;

        // Prepara gli elementi come nel PDF
        $elementi = collect();
        
        // Prodotti
        foreach ($order->products as $product) {
            $elementi->push([
                'id' => $product->id,
                'tipo' => $product->type,
                'product_id' => $product->product_id,
                'codice' => $product->product->codice ?? null,
                'nome' => $product->product->nome ?? null,
                'quantita' => $product->quantita,
                'unita_misura' => $product->product->unita_misura ?? 'NR',
                'prezzo' => $product->prezzo,
                'importo' => $product->quantita * $product->prezzo,
                'fornitore_id' => $product->fornitore_id,
                'riferimento' => $product->riferimento,
                'iva' => [
                    'aliquota_iva_id' => $product->aliquota_iva_id,
                    'aliquota' => $product->aliquotaIva->aliquota ?? 0
                ],
                'categoria' => [
                    'nome' => $product->product->categories->first()->nome ?? 'Senza categoria'
                ],
                'order' => $product->order
            ]);
        }

        // Altri elementi
        foreach ($order->altro as $altro) {
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
                    'aliquota' => $altro->aliquotaIva->aliquota ?? 0
                ],
                'order' => $altro->order
            ]);
        }

        // Descrizioni
        foreach ($order->descrizioni as $descrizione) {
            $elementi->push([
                'id' => $descrizione->id,
                'tipo' => 'descrizione',
                'descrizione' => $descrizione->descrizione,
                'order' => $descrizione->order
            ]);
        }

        // Ordina per order
        $elementi = $elementi->sortBy('order')->values();

        // Raggruppa per categoria
        $elementiPerCategoria = $elementi->groupBy(function($item) {
            if ($item['tipo'] === 'descrizione') return 'Descrizioni';
            if ($item['tipo'] === 'altro') return 'Altri Elementi';
            return $item['categoria']['nome'] ?? 'Senza categoria';
        });

        // Struttura i dati come nelle viste show/edit
        $orderData = [
            'id' => $order->id,
            'numero' => $order->numero,
            'data' => $order->data,
            'stato' => $order->stato,
            'note' => $order->note,
            'type' => $order->type,
            'entity' => $order->entity,
            'indirizzo' => $order->indirizzo,
            'dettagli' => $order->dettagli,
            'media' => $order->media,
            'fornitori' => \App\Models\Entity::fornitori()->get(['id', 'nome']), // Carica i fornitori
            'products' => $order->products->map(function($product) {
                return [
                    'id' => $product->id,
                    'product_id' => $product->product_id,
                    'product' => $product->product,
                    'quantita' => $product->quantita,
                    'prezzo' => $product->prezzo,
                    'aliquotaIva' => $product->aliquotaIva,
                    'fornitore_id' => $product->fornitore_id,
                    'riferimento' => $product->riferimento,
                    'order' => $product->order
                ];
            }),
            'elementi' => $elementi,
            'elementiPerCategoria' => $elementiPerCategoria
        ];

        // Restituisce la vista Inertia con i dati dell'ordine
        return Inertia::render('QrCode/OrderPublicView', [
            'order' => $orderData,
            'title' => $title
        ]);
    }

    /**
     * Genera QR code dinamico
     */
    public function generate(Request $request)
    {
        $request->validate([
            'type' => 'required|in:product,order',
            'id' => 'required|integer'
        ]);

        if ($request->type === 'product') {
            return $this->product($request->id);
        } else {
            return $this->order($request->id);
        }
    }

    /**
     * Download QR code in vari formati
     */
    public function download(Request $request)
    {
        $request->validate([
            'type' => 'required|in:product,order',
            'id' => 'required|integer',
            'format' => 'in:svg,png,eps' // Solo formati supportati dalla libreria
        ]);

        $format = strtolower($request->format ?? 'svg');
        
        if ($request->type === 'product') {
            // Per ora disabilitato
            abort(404, 'Funzionalità prodotto temporaneamente disabilitata');
        } else {
            // CORREZIONE: Cerca l'ordine con l'ID specifico e il tipo corretto
            $order = Document::where('id', $request->id)
                ->where(function($query) {
                    $query->where('type', 'ordini-vendita')
                          ->orWhere('type', 'ordini-acquisto');
                })->firstOrFail();
            
            $filename = "qr-order-{$order->numero}.{$format}";
            
            // Usa l'URL diretto per il download
            $publicUrl = route('qr.order.view', $order->id);
            
            $qrData = [
                'type' => 'order',
                'id' => $order->id,
                'code' => $order->numero,
                'name' => $order->entity->nome,
                'url' => $publicUrl
            ];
        }

        try {
            // Genera il QR code nel formato richiesto
            $qrCode = QrCode::format($format)
                ->size(400) // Dimensione maggiore per il download
                ->margin(10)
                ->generate($publicUrl); // URL diretto, non JSON

            // Imposta i content type appropriati
            $contentType = match($format) {
                'svg' => 'image/svg+xml',
                'png' => 'image/png',
                'eps' => 'application/postscript',
                default => 'image/svg+xml'
            };

            // Restituisce il file per il download
            return response((string) $qrCode)
                ->header('Content-Type', $contentType)
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");

        } catch (\Exception $e) {
            
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