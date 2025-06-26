<template>
	<v-dialog
		v-model="dialog"
		:max-width="dialogSetup.width"
		:fullscreen="dialogSetup.fullscreen"
		:scrim="dialogSetup.scrim"
		persistent
		transition="dialog-transition"
	>
		<v-card>
			<v-card-title class="pa-6 pb-0">
				<div class="d-flex align-center">
					<v-icon
						:color="color"
						class="me-3"
						size="24"
					>
						<i class="fa-solid fa-qrcode"></i>
					</v-icon>
					{{ dialogTitle }}
				</div>
			</v-card-title>

			<v-card-text class="pa-6">
				<div v-if="loading" class="text-center pa-8">
					<v-progress-circular
						indeterminate
						color="primary"
						size="64"
					></v-progress-circular>
					<p class="mt-4 text-grey">Generazione QR Code in corso...</p>
				</div>

				<div v-else-if="qrData && qrData.data" class="qr-code-container">
					<div class="text-center mb-4">
						<h4 class="text-h6 mb-2">{{ qrData.data.code || 'QR Code' }}</h4>
						<p class="text-caption text-grey">{{ qrData.data.name || '' }}</p>
					</div>

					<div class="text-center mb-6">
						<div class="qr-code-svg" v-html="qrData.qr_code"></div>
					</div>

					<v-divider class="mb-4"></v-divider>

					<!-- URL cliccabile -->
					<div class="mb-4">
						<h4 class="text-h6 mb-3">Link Pubblico</h4>
						<v-card variant="outlined" class="pa-3">
							<a 
								:href="qrData.data.url" 
								target="_blank" 
								class="text-decoration-none text-primary"
								style="word-break: break-all;"
							>
								<i class="fa-solid fa-external-link-alt me-2"></i>
								{{ qrData.data.url }}
							</a>
							
						</v-card>
					</div>

					<v-divider class="mb-4"></v-divider>

					<div class="mb-4">
						<h4 class="text-h6 mb-3">Scarica QR Code</h4>
						
						<v-row>
							<v-col cols="4">
								<v-btn
									color="info"
									variant="outlined"
									@click="downloadQr('svg')"
									:loading="isQrCodeLoading('svg')"
									block
									size="small"
								>
									<i class="fa-solid fa-vector-square me-1"></i>
									SVG
								</v-btn>
								
							</v-col>
							
							<v-col cols="4">
								<v-btn
									color="success"
									variant="outlined"
									@click="downloadQr('png')"
									:loading="isQrCodeLoading('png')"
									block
									size="small"
								>
									<i class="fa-solid fa-image me-1"></i>
									PNG
								</v-btn>
							
							</v-col>
							
							<v-col cols="4">
								<v-btn
									color="warning"
									variant="outlined"
									@click="downloadQr('eps')"
									:loading="isQrCodeLoading('eps')"
									block
									size="small"
								>
									<i class="fa-solid fa-file-code me-1"></i>
									EPS
								</v-btn>
							</v-col>
						</v-row>
						
						<!-- <p class="text-caption text-grey mt-3">
							<i class="fa-solid fa-info-circle me-1"></i>
							Il QR code è scannerizzabile e porta alla vista pubblica dell'ordine
						</p> -->
					</div>
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
	computed: {
		// Accesso alle proprietà globali di Vue
		crudTable() {
			return this.$crudTable;
		},
		downloadService() {
			return this.$downloadService;
		}
	},
	methods: {
		openDialog(type, id, item) {
			
			this.type = type; 			//'order' o 'product'	
			this.item = item; 			//oggetto completo dell'ordine della tabella
			this.qrData = null; 		//reset dati precedenti
			this.error = null;			//reset errori precedenti
			this.dialog = false;		//reset dialog precedente

			
			this.$nextTick(() => {
				this.dialog = true;		  //apre dialog
				this.generateQrCode(id);  // chiama l'API per generare QR code
			});
		},

		// Chiamata API per Generare QR Code
		async generateQrCode(id) {
			this.loading = true;
			this.error = null;

			try {
				await this.axiosService.get({
					url: `/qr/${this.type}/${id}?_=${Date.now()}`,
					success: (data) => {
						this.qrData = data.data;
						
					},
					error: (error) => {
						this.error = error.message || 'Errore nella generazione del QR code';
					}
				});
			} catch (error) {
				this.error = 'Errore nella generazione del QR code';
			} finally {
				this.loading = false;
			}
		},

		async downloadQr(format) {
			if (!this.qrData) return;
			
			const params = new URLSearchParams({
				type: this.qrData.data.type,
				id: this.qrData.data.id,
				format: format
			});
			
			const url = `/qr/download?${params.toString()}`;
			
			// Usa il DownloadService se disponibile
			if (this.downloadService) {
				this.downloadService.downloadQrCode(url, this.qrData.data.id, format);
			} else if (this.crudTable) {
				// Fallback su crudTable se disponibile
				this.crudTable.openQrCode(url, this.qrData.data.id, format);
			} else {
				// Fallback finale
				this.downloading = true;
				try {
					window.open(url, '_blank');
				} catch (error) {
					console.error('Errore QR Code:', error);
				} finally {
					this.downloading = false;
				}
			}
		},

		isQrCodeLoading(format) {
			// Controlla lo stato di caricamento usando crudTable o downloadService
			if (this.crudTable && this.crudTable.isQrCodeLoading) {
				return this.crudTable.isQrCodeLoading(this.qrData?.data?.id, format);
			}
			if (this.downloadService && this.downloadService.isLoading) {
				return this.downloadService.isLoading('qrCode', this.qrData?.data?.id, format);
			}
			return this.downloading;
		},

		retry() {
			if (this.item && this.type) {
				// Usa l'ID dell'item corrente invece di this.item.id che potrebbe essere stale
				const currentId = this.item.id;
				this.generateQrCode(currentId);
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