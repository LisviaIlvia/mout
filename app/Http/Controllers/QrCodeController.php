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
     * Mostra la pagina di generazione QR code
     */
    public function index()
    {
        return Inertia::render('QrCode/QrCodeIndex');
    }

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
        
        // CORREZIONE: Cerca l'ordine con l'ID specifico e il tipo corretto
        $order = Document::where('id', $id)
            ->where(function($query) {
                $query->where('type', 'ordini-vendita')
                      ->orWhere('type', 'ordini-acquisto');
            })->firstOrFail();

        // Debug: log dei dati dell'ordine trovato
        \Log::info("QR Code - Ordine trovato: ID=" . $order->id . ", Numero=" . $order->numero . ", Cliente=" . $order->entity->nome);

        // Crea l'URL diretto per la vista pubblica
        $publicUrl = route('qr.order.view', $order->id);

        // Debug: log dell'URL generato
        \Log::info("QR Code - URL generato: " . $publicUrl);

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

        // Debug: log dei dati che verranno restituiti
        \Log::info("QR Code - Dati restituiti: " . json_encode($qrData));

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
        // CORREZIONE: Cerca l'ordine con l'ID specifico e il tipo corretto
        $order = Document::where('id', $id)
            ->where(function($query) {
                $query->where('type', 'ordini-vendita')
                      ->orWhere('type', 'ordini-acquisto');
            })->with([
                'entity', 
                'products.product', 
                'products.aliquotaIva',  // Carica l'aliquota IVA direttamente dal DocumentProduct
                'dettagli'
            ]) // Carica le relazioni necessarie
            ->firstOrFail();

        // Determina il tipo di ordine per il titolo
        $orderType = $order->type === 'ordini-vendita' ? 'Ordine di Vendita' : 'Ordine di Acquisto';
        $title = $orderType . ' - ' . $order->numero;

        // Struttura i dati come nelle viste show/edit
        $orderData = [
            'id' => $order->id,
            'numero' => $order->numero,
            'data' => $order->data,
            'stato' => $order->stato,
            'note' => $order->note,
            'type' => $order->type,
            'entity' => $order->entity,
            'dettagli' => $order->dettagli,
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
            })
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
            // Se il formato non è supportato, fallback su SVG
            \Log::warning("QR Code - Formato {$format} non supportato, fallback su SVG: " . $e->getMessage());
            
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