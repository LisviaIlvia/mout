<template>
	<v-dialog
		v-model="dialog"
		:max-width="dialogSetup.width || 500"
		:fullscreen="dialogSetup.fullscreen || false"
		:scrim="dialogSetup.scrim !== undefined ? dialogSetup.scrim : true"
		persistent
	>
		<v-card>
			<v-toolbar
				:color="color"
				dark
			>
				<v-toolbar-title>
					<i class="fa-solid fa-qrcode me-2"></i>
					{{ dialogTitle }}
				</v-toolbar-title>
				<v-spacer></v-spacer>
				<v-btn
					icon
					@click="closeDialog"
				>
					<i class="fa-solid fa-times"></i>
				</v-btn>
			</v-toolbar>

			<v-card-text class="pa-6">
				<div v-if="loading" class="text-center pa-8">
					<v-progress-circular
						indeterminate
						size="64"
						:color="color"
					></v-progress-circular>
					<p class="mt-4 text-body-1">Generazione QR Code in corso...</p>
				</div>

				<div v-else-if="qrData && qrData.data && qrData.data.qr_code" class="text-center">
					<!-- QR Code Display -->
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

					<!-- Azioni Download - Multipli formati -->
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

				<div v-else-if="qrData" class="text-grey">QR code non disponibile</div>

				<div v-else-if="error" class="text-center pa-4">
					<v-alert
						type="error"
						class="mb-4"
					>
						{{ error }}
					</v-alert>
					<v-btn
						color="primary"
						@click="retry"
					>
						<i class="fa-solid fa-refresh me-2"></i>
						Riprova
					</v-btn>
				</div>
			</v-card-text>

			<v-card-actions class="pa-6">
				<v-spacer></v-spacer>
				<v-btn
					color="grey"
					variant="text"
					@click="closeDialog"
				>
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
			// Debug: log dei parametri ricevuti
			console.log('QR Code - openDialog chiamato con:', { type, id, item });
			
			this.type = type; 			//'order' o 'product'	
			this.item = item; 			//oggetto completo dell'ordine della tabella
			this.qrData = null; 		//reset dati precedenti
			this.error = null;			//reset errori precedenti
			this.dialog = false;		//reset dialog precedente
			
			// Debug: log dello stato dopo il reset
			console.log('QR Code - Stato dopo reset:', { 
				type: this.type, 
				itemId: this.item?.id, 
				item: this.item 
			});
			
			this.$nextTick(() => {
				this.dialog = true;		  //apre dialog
				this.generateQrCode(id);  // chiama l'API per generare QR code
			});
		},

		// Chiamata API per Generare QR Code
		async generateQrCode(id) {
			this.loading = true;
			this.error = null;

			// Debug: log dell'ID e tipo
			console.log('QR Code - Generazione per:', { type: this.type, id: id });

			try {
				await this.axiosService.get({
					url: `/qr/${this.type}/${id}?_=${Date.now()}`,
					success: (data) => {
						// Debug: log dei dati ricevuti
						console.log('QR Code - Dati ricevuti:', data);
						console.log('QR Code - Dati order nella risposta:', data.data?.order);
						console.log('QR Code - ID order nella risposta:', data.data?.order?.id);
						this.qrData = data;
						
						// Debug: log dello stato finale
						console.log('QR Code - Stato finale dopo assegnazione:', {
							qrDataOrderId: this.qrData?.data?.order?.id,
							qrDataDataId: this.qrData?.data?.data?.id,
							itemId: this.item?.id
						});
					},
					error: (error) => {
						// Debug: log dell'errore
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

		retry() {
			// Debug: log dello stato corrente
			console.log('QR Code - Retry chiamato con stato:', { 
				type: this.type, 
				itemId: this.item?.id, 
				item: this.item 
			});
			
			if (this.item && this.type) {
				// Usa l'ID dell'item corrente invece di this.item.id che potrebbe essere stale
				const currentId = this.item.id;
				console.log('QR Code - Retry con ID:', currentId);
				this.generateQrCode(currentId);
			} else {
				console.error('QR Code - Retry fallito: item o type mancanti');
			}
		},

		closeDialog() {
			this.dialog = false;
			this.$emit('dialog-qr-closed');
		}
	},
	beforeUnmount() {
		// Reset dati per sicurezza
		this.qrData = null;
		this.error = null;
		this.item = null;
		this.type = null;
	}
};
</script>

<style scoped>
.qr-code-container {
	background: white;
	border: 1px solid #e0e0e0;
	border-radius: 8px;
	padding: 20px;
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.qr-code-svg {
	max-width: 100%;
	height: auto;
}

.qr-code-container:hover {
	box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
	transition: box-shadow 0.3s ease;
}
</style> 