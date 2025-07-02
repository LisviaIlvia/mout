import axiosService from './axiosService';

class QrCodeService {
	constructor() {
		this.axiosService = new axiosService();
		this.downloadService = null; // Sarà inizializzato quando disponibile
		this.loading = {
			generate: false,
			download: {}
		};
		this.qrData = null;
		this.error = null;
	}

	/**
	 * Genera QR code per un tipo specifico (product/order)
	 * @param {string} type - 'product' o 'order'
	 * @param {number} id - ID dell'elemento
	 * @param {function} callback - Callback di successo
	 * @param {function} errorCallback - Callback di errore
	 */
	async generateQrCode(type, id, callback, errorCallback) {
		this.loading.generate = true;
		this.error = null;

		try {
			if (!this.axiosService) {
				throw new Error('AxiosService non disponibile');
			}

			await this.axiosService.get({
				url: `/qr/${type}/${id}?_=${Date.now()}`,
				success: (data) => {
					this.qrData = data.data;
					if (callback) callback(this.qrData);
				},
				error: (error) => {
					this.error = error.message || 'Errore nella generazione del QR code';
					if (errorCallback) errorCallback(this.error);
				}
			});
		} catch (error) {
			console.error('Errore generazione QR Code:', error);
			this.error = 'Errore nella generazione del QR code';
			if (errorCallback) errorCallback(this.error);
		} finally {
			this.loading.generate = false;
		}
	}

	/**
	 * Download QR code in vari formati
	 * @param {string} type - 'product' o 'order'
	 * @param {number} id - ID dell'elemento
	 * @param {string} format - 'svg', 'png', 'eps'
	 */
	async downloadQrCode(type, id, format) {
		const key = `${id}-${format}`;
		this.loading.download[key] = true;

		try {
			const params = new URLSearchParams({
				type: type,
				id: id,
				format: format
			});
			
			const url = `/qr/download?${params.toString()}`;
			
			// Usa downloadService se disponibile, altrimenti fallback su window.open
			if (this.downloadService && this.downloadService.downloadQrCode) {
				this.downloadService.downloadQrCode(url, id, format);
			} else {
				// Fallback: apertura diretta in nuova finestra
				window.open(url, '_blank');
			}
		} catch (error) {
			console.error('Errore download QR Code:', error);
		} finally {
			this.loading.download[key] = false;
		}
	}

	/**
	 * Verifica se il download è in corso
	 * @param {number} id - ID dell'elemento
	 * @param {string} format - Formato
	 * @returns {boolean}
	 */
	isDownloading(id, format) {
		// Usa downloadService se disponibile, altrimenti usa il loading locale
		if (this.downloadService && this.downloadService.isLoading) {
			return this.downloadService.isLoading('qrCode', id, format);
		}
		
		// Fallback: usa il loading locale
		const key = `${id}-${format}`;
		return this.loading.download[key] || false;
	}

	/**
	 * Verifica se la generazione è in corso
	 * @returns {boolean}
	 */
	isGenerating() {
		return this.loading.generate;
	}

	/**
	 * Imposta il downloadService
	 * @param {DownloadService} downloadService - Istanza del downloadService
	 */
	setDownloadService(downloadService) {
		this.downloadService = downloadService;
	}

	/**
	 * Reset dello stato
	 */
	reset() {
		this.qrData = null;
		this.error = null;
		this.loading = {
			generate: false,
			download: {}
		};
	}

	/**
	 * Ottieni i dati del QR code
	 * @returns {object|null}
	 */
	getQrData() {
		return this.qrData;
	}

	/**
	 * Ottieni l'errore corrente
	 * @returns {string|null}
	 */
	getError() {
		return this.error;
	}
}

export default QrCodeService; 