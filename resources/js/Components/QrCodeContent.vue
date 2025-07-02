<template>
	<div>
		<!-- Loading state -->
		<div v-if="qrService.isGenerating()" class="text-center pa-8">
			<v-progress-circular
				indeterminate
				color="primary"
				size="64"
			></v-progress-circular>
			<p class="mt-4 text-grey">Generazione QR Code in corso...</p>
		</div>

		<!-- QR Code content -->
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

			<!-- Download buttons -->
			<div class="mb-4">
				<h4 class="text-h6 mb-3">Scarica QR Code</h4>
				
				<v-row>
					<v-col cols="4">
						<v-btn
							color="info"
							variant="outlined"
							@click="downloadQr('svg')"
							:loading="qrService.isDownloading(qrData.data.id, 'svg')"
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
							:loading="qrService.isDownloading(qrData.data.id, 'png')"
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
							:loading="qrService.isDownloading(qrData.data.id, 'eps')"
							block
							size="small"
						>
							<i class="fa-solid fa-file-code me-1"></i>
							EPS
						</v-btn>
					</v-col>
				</v-row>
			</div>
		</div>

		<!-- Error state -->
		<div v-else-if="qrService.getError()" class="text-center pa-4">
			<v-alert
				type="error"
				class="mb-4"
			>
				{{ qrService.getError() }}
			</v-alert>
			<v-btn
				color="primary"
				@click="retry"
			>
				<i class="fa-solid fa-refresh me-2"></i>
				Riprova
			</v-btn>
		</div>

		<!-- No data state -->
		<div v-else-if="qrData" class="text-grey">QR code non disponibile</div>
	</div>
</template>

<script>
import QrCodeService from '@/lib/qrCodeService';

export default {
	name: 'QrCodeContent',
	props: {
		type: {
			type: String,
			required: true // 'product' o 'order'
		},
		id: {
			type: Number,
			required: true
		}
	},
	data() {
		return {
			qrService: new QrCodeService(),
			qrData: null
		};
	},
	computed: {
		// Computed per reattività
		qrDataComputed() {
			return this.qrService.getQrData();
		},
		// Accesso al downloadService globale
		downloadService() {
			return this.$downloadService;
		}
	},

	watch: {
		qrDataComputed: {
			handler(newData) {
				this.qrData = newData;
			},
			immediate: true
		}
	},
	mounted() {
		this.$nextTick(() => {
			// Imposta il downloadService se disponibile
			if (this.downloadService) {
				this.qrService.setDownloadService(this.downloadService);
			}
			this.generateQrCode();
		});
	},
	methods: {
		async generateQrCode() {
			await this.qrService.generateQrCode(
				this.type,
				this.id,
				(data) => {
					this.qrData = data;
				},
				(error) => {
					console.error('Errore QR Code:', error);
				}
			);
		},

		async downloadQr(format) {
			if (!this.qrData) return;
			
			// Usa i dati del QR code per il download
			const type = this.qrData.data.type || 'order'; // fallback a 'order'
			const id = this.qrData.data.id;
			
			await this.qrService.downloadQrCode(type, id, format);
		},

		retry() {
			this.generateQrCode();
		}
	},
	beforeUnmount() {
		this.qrService.reset();
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