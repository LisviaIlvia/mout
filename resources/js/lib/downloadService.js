class DownloadService {
	constructor() {
		this.loading = {
			pdf: {},
			qrCode: {},
			excel: {},
			// Facile aggiungere altri tipi
		};
	}

	// Download PDF
	downloadPdf(urlOpen, recordId) {
		this.startLoading('pdf', recordId);
		
		// Notifica di inizio download
		this.showNotification('Generazione PDF in corso...', 'info');
		
		fetch(urlOpen)
			.then(response => {
				if (!response.ok) {
					throw new Error('Errore nel caricamento del PDF');
				}
				return response.blob();
			})
			.then(blob => {
				this.downloadBlob(blob, `documento-${recordId}.pdf`);
				this.stopLoading('pdf', recordId);
				this.showNotification('PDF generato con successo!', 'success');
			})
			.catch(error => {
				console.error('Errore PDF:', error);
				this.stopLoading('pdf', recordId);
				this.fallbackDownload(urlOpen);
				this.showNotification('Errore nella generazione del PDF - Tentativo con apertura diretta', 'warning');
			});
	}

	// Download QR Code
	downloadQrCode(urlOpen, recordId, format) {
		this.startLoading('qrCode', recordId, format);
		
		this.showNotification(`Generazione QR Code ${format.toUpperCase()} in corso...`, 'info');
		
		fetch(urlOpen)
			.then(response => {
				if (!response.ok) {
					throw new Error('Errore nel caricamento del QR code');
				}
				return response.blob();
			})
			.then(blob => {
				const extension = this.getQrCodeExtension(format);
				this.downloadBlob(blob, `qr-code-${recordId}.${extension}`);
				this.stopLoading('qrCode', recordId, format);
				this.showNotification(`QR Code ${format.toUpperCase()} scaricato con successo!`, 'success');
			})
			.catch(error => {
				console.error('Errore QR Code:', error);
				this.stopLoading('qrCode', recordId, format);
				this.fallbackDownload(urlOpen);
				this.showNotification(`Errore nel download del QR Code ${format.toUpperCase()} - Tentativo con apertura diretta`, 'warning');
			});
	}

	// Metodi di utilità
	startLoading(type, recordId, format = null) {
		if (!this.loading[type][recordId]) {
			this.loading[type][recordId] = {};
		}
		
		if (format) {
			this.loading[type][recordId][format] = true;
		} else {
			this.loading[type][recordId] = true;
		}
	}

	stopLoading(type, recordId, format = null) {
		if (this.loading[type][recordId]) {
			if (format) {
				this.loading[type][recordId][format] = false;
			} else {
				this.loading[type][recordId] = false;
			}
		}
	}

	isLoading(type, recordId, format = null) {
		if (!this.loading[type][recordId]) return false;
		
		if (format) {
			return this.loading[type][recordId][format] || false;
		}
		
		return this.loading[type][recordId] || false;
	}

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

	fallbackDownload(url) {
		console.log('Tentativo fallback con apertura in nuova finestra...');
		window.open(url, '_blank');
	}

	getQrCodeExtension(format) {
		switch(format) {
			case 'svg': return 'svg';
			case 'eps': return 'eps';
			default: return 'png';
		}
	}

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