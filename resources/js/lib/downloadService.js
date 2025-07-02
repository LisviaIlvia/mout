/**
 * DownloadService - Servizio per la gestione dei download
 * 
 * Questo servizio gestisce il download di file PDF, QR Code, Excel e altri documenti
 * con gestione dello stato di caricamento, notifiche e fallback automatici.
 * 
 * Caratteristiche principali:
 * - Gestione dello stato di loading per ogni tipo di download
 * - Fallback automatico in caso di errore
 * - Supporto per diversi formati di file
 * - Download sicuro tramite blob
 */
class DownloadService {
	/**
	 * Costruttore - Inizializza lo stato di loading per tutti i tipi di download
	 */
	constructor() {
		// Oggetto che mantiene lo stato di loading per ogni tipo di download
		// Struttura: { tipo: { recordId: { formato: boolean } } }
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
				// Mostra notifica di successo
				// this.showNotification('PDF generato con successo!', 'success');
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
	 * Download QR Code - Scarica un QR Code in diversi formati
	 * 
	 * @param {string} urlOpen - URL per generare il QR Code
	 * @param {number|string} recordId - ID del record per il quale generare il QR Code
	 * @param {string} format - Formato del QR Code (png, svg, eps)
	 */
	downloadQrCode(urlOpen, recordId, format) {
		// Avvia lo stato di loading per questo QR Code
		this.startLoading('qrCode', recordId, format);
		
		// Mostra notifica di inizio generazione
		this.showNotification(`Generazione QR Code ${format.toUpperCase()} in corso...`, 'info');
		
		// Esegue la richiesta fetch per scaricare il QR Code
		fetch(urlOpen)
			.then(response => {
				// Verifica che la risposta sia valida
				if (!response.ok) {
					throw new Error('Errore nel caricamento del QR code');
				}
				// Converte la risposta in blob
				return response.blob();
			})
			.then(blob => {
				// Determina l'estensione del file in base al formato
				const extension = this.getQrCodeExtension(format);
				// Scarica il blob come file QR Code
				this.downloadBlob(blob, `qr-code-${recordId}.${extension}`);
				// Ferma lo stato di loading
				this.stopLoading('qrCode', recordId, format);
				// Mostra notifica di successo
				this.showNotification(`QR Code ${format.toUpperCase()} scaricato con successo!`, 'success');
			})
			.catch(error => {
				// Gestione errori: log, stop loading e fallback
				console.error('Errore QR Code:', error);
				this.stopLoading('qrCode', recordId, format);
				// Tentativo di apertura diretta in nuova finestra
				this.fallbackDownload(urlOpen);
				this.showNotification(`Errore nel download del QR Code ${format.toUpperCase()} - Tentativo con apertura diretta`, 'warning');
			});
	}

	/**
	 * Avvia lo stato di loading per un tipo specifico di download
	 * 
	 * @param {string} type - Tipo di download (pdf, qrCode, excel)
	 * @param {number|string} recordId - ID del record
	 * @param {string|null} format - Formato specifico (opzionale, per QR Code)
	 */
	startLoading(type, recordId, format = null) {
		// Inizializza l'oggetto per questo recordId se non esiste
		if (!this.loading[type][recordId]) {
			this.loading[type][recordId] = {};
		}
		
		// Se è specificato un formato (es. per QR Code), imposta loading per quel formato
		if (format) {
			this.loading[type][recordId][format] = true;
		} else {
			// Altrimenti imposta loading generale per questo record
			this.loading[type][recordId] = true;
		}
	}

	/**
	 * Ferma lo stato di loading per un tipo specifico di download
	 * 
	 * @param {string} type - Tipo di download (pdf, qrCode, excel)
	 * @param {number|string} recordId - ID del record
	 * @param {string|null} format - Formato specifico (opzionale)
	 */
	stopLoading(type, recordId, format = null) {
		// Verifica che esista l'oggetto loading per questo record
		if (this.loading[type][recordId]) {
			if (format) {
				// Ferma loading per il formato specifico
				this.loading[type][recordId][format] = false;
			} else {
				// Ferma loading generale per questo record
				this.loading[type][recordId] = false;
			}
		}
	}

	/**
	 * Verifica se un download è in corso di caricamento
	 * 
	 * @param {string} type - Tipo di download (pdf, qrCode, excel)
	 * @param {number|string} recordId - ID del record
	 * @param {string|null} format - Formato specifico (opzionale)
	 * @returns {boolean} - True se il download è in corso
	 */
	isLoading(type, recordId, format = null) {
		// Se non esiste l'oggetto loading per questo record, non è in caricamento
		if (!this.loading[type][recordId]) return false;
		
		if (format) {
			// Verifica loading per il formato specifico
			return this.loading[type][recordId][format] || false;
		}
		
		// Verifica loading generale per questo record
		return this.loading[type][recordId] || false;
	}

	/**
	 * Scarica un blob come file nel browser
	 * 
	 * @param {Blob} blob - Il file binario da scaricare
	 * @param {string} filename - Nome del file da scaricare
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
	 * 
	 * @param {string} url - URL da aprire in nuova finestra
	 */
	fallbackDownload(url) {
		console.log('Tentativo fallback con apertura in nuova finestra...');
		// Apre l'URL in una nuova finestra/tab
		window.open(url, '_blank');
	}

	/**
	 * Determina l'estensione del file per i QR Code in base al formato
	 * 
	 * @param {string} format - Formato del QR Code (png, svg, eps)
	 * @returns {string} - Estensione del file
	 */
	getQrCodeExtension(format) {
		switch(format) {
			case 'svg': return 'svg';  // Scalable Vector Graphics
			case 'eps': return 'eps';  // Encapsulated PostScript
			default: return 'png';     // Portable Network Graphics (default)
		}
	}

	/**
	 * Mostra una notifica all'utente tramite il sistema di notifiche globale
	 * 
	 * @param {string} message - Messaggio da mostrare
	 * @param {string} color - Colore della notifica (info, success, warning, error)
	 * @param {number} timeout - Timeout in millisecondi (default: 3000ms)
	 */
	showNotification(message, color = 'info', timeout = 3000) {
		// Verifica che esista la funzione flashMessage globale
		if (window.flashMessage) {
			window.flashMessage({
				message,
				color,
				timeout
			});
		}
	}
}

// Esporta la classe per l'uso in altri moduli
export default DownloadService; 