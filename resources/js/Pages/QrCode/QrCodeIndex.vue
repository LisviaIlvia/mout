<template>
	<v-container fluid class="pa-6">
		<v-row>
			<v-col cols="12">
				<v-card elevation="4" class="pa-6">
					<v-toolbar class="px-0 mb-6">
						<v-row class="align-center bg-grey-lighten-3">
							<v-col cols="auto">
								<i class="fa-solid fa-qrcode fa-2x me-3 text-primary"></i>
							</v-col>
							<v-col>
								<h2 class="text-h4 font-weight-bold">Generatore QR Code</h2>
								<p class="text-body-2 text-grey-darken-1 mt-1">
									Genera QR code per prodotti e ordini
								</p>
							</v-col>
						</v-row>
					</v-toolbar>

					<!-- Selettore Tipo -->
					<v-row class="mb-6">
						<v-col cols="12" md="6">
							<v-card variant="outlined" class="pa-4">
								<v-card-title class="text-h6 mb-4">
									<i class="fa-solid fa-box me-2"></i>
									QR Code Prodotto
								</v-card-title>
								
								<v-form @submit.prevent="generateProductQr">
									<v-text-field
										v-model="productForm.id"
										label="ID Prodotto"
										type="number"
										placeholder="Inserisci l'ID del prodotto"
										:rules="[v => !!v || 'ID prodotto richiesto']"
										clearable
									></v-text-field>
									
									<v-btn
										color="primary"
										type="submit"
										:loading="loading.product"
										:disabled="!productForm.id"
										block
									>
										<i class="fa-solid fa-qrcode me-2"></i>
										Genera QR Code Prodotto
									</v-btn>
								</v-form>
							</v-card>
						</v-col>

						<v-col cols="12" md="6">
							<v-card variant="outlined" class="pa-4">
								<v-card-title class="text-h6 mb-4">
									<i class="fa-solid fa-file-invoice me-2"></i>
									QR Code Ordine
								</v-card-title>
								
								<v-form @submit.prevent="generateOrderQr">
									<v-text-field
										v-model="orderForm.id"
										label="ID Ordine"
										type="number"
										placeholder="Inserisci l'ID dell'ordine"
										:rules="[v => !!v || 'ID ordine richiesto']"
										clearable
									></v-text-field>
									
									<v-btn
										color="secondary"
										type="submit"
										:loading="loading.order"
										:disabled="!orderForm.id"
										block
									>
										<i class="fa-solid fa-qrcode me-2"></i>
										Genera QR Code Ordine
									</v-btn>
								</v-form>
							</v-card>
						</v-col>
					</v-row>

					<!-- Risultato QR Code -->
					<v-row v-if="qrResult">
						<v-col cols="12">
							<v-card variant="outlined" class="pa-6">
								<v-card-title class="text-h6 mb-4">
									<i class="fa-solid fa-qrcode me-2"></i>
									QR Code Generato
								</v-card-title>

								<v-row>
									<v-col cols="12" md="6">
										<!-- QR Code SVG -->
										<div class="text-center">
											<div 
												v-html="qrResult.qr_code" 
												class="qr-code-display mb-4"
												style="max-width: 300px; margin: 0 auto;"
											></div>
											
											<v-chip
												:color="qrResult.data.type === 'product' ? 'primary' : 'secondary'"
												class="mb-4"
											>
												{{ qrResult.data.type === 'product' ? 'Prodotto' : 'Ordine' }}
											</v-chip>
										</div>
									</v-col>

									<v-col cols="12" md="6">
										<!-- Dettagli -->
										<v-card variant="outlined" class="pa-4">
											<h4 class="text-h6 mb-3">Dettagli</h4>
											
											<v-list density="compact">
												<v-list-item>
													<template v-slot:prepend>
														<i class="fa-solid fa-hashtag text-grey"></i>
													</template>
													<v-list-item-title>ID: {{ qrResult.data.id }}</v-list-item-title>
												</v-list-item>
												
												<v-list-item>
													<template v-slot:prepend>
														<i class="fa-solid fa-barcode text-grey"></i>
													</template>
													<v-list-item-title>Codice: {{ qrResult.data.code }}</v-list-item-title>
												</v-list-item>
												
												<v-list-item>
													<template v-slot:prepend>
														<i class="fa-solid fa-tag text-grey"></i>
													</template>
													<v-list-item-title>Nome: {{ qrResult.data.name }}</v-list-item-title>
												</v-list-item>
												
												<v-list-item>
													<template v-slot:prepend>
														<i class="fa-solid fa-link text-grey"></i>
													</template>
													<v-list-item-title>
														<a :href="qrResult.data.url" target="_blank" class="text-decoration-none">
															{{ qrResult.data.url }}
														</a>
													</v-list-item-title>
												</v-list-item>
											</v-list>
										</v-card>

										<!-- Azioni -->
										<v-card variant="outlined" class="pa-4 mt-4">
											<h4 class="text-h6 mb-3">Scarica QR Code</h4>
											
											<v-row>
												<v-col cols="4">
													<v-btn
														color="info"
														variant="outlined"
														@click="downloadQr('svg')"
														:loading="loading.download"
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
														:loading="loading.download"
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
														:loading="loading.download"
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
									</v-col>
								</v-row>
							</v-card>
						</v-col>
					</v-row>

					<!-- Messaggio di errore -->
					<v-alert
						v-if="error"
						type="error"
						class="mt-4"
						dismissible
						@click:close="error = null"
					>
						{{ error }}
					</v-alert>
				</v-card>
			</v-col>
		</v-row>
	</v-container>
</template>

<script>
import axiosService from '@/lib/axiosService';

export default {
	name: 'QrCodeIndex',
	data() {
		return {
			productForm: {
				id: null
			},
			orderForm: {
				id: null
			},
			qrResult: null,
			error: null,
			loading: {
				product: false,
				order: false,
				download: false
			},
			axiosService: new axiosService()
		};
	},
	methods: {
		async generateProductQr() {
			if (!this.productForm.id) return;
			
			this.loading.product = true;
			this.error = null;
			
			try {
				const response = await this.axiosService.get({
					url: `/qr/product/${this.productForm.id}`,
					success: (data) => {
						this.qrResult = data;
					},
					error: (error) => {
						this.error = error.message || 'Errore nella generazione del QR code';
					}
				});
			} catch (error) {
				this.error = 'Errore nella generazione del QR code';
			} finally {
				this.loading.product = false;
			}
		},

		async generateOrderQr() {
			if (!this.orderForm.id) return;
			
			this.loading.order = true;
			this.error = null;
			
			try {
				const response = await this.axiosService.get({
					url: `/qr/order/${this.orderForm.id}`,
					success: (data) => {
						this.qrResult = data;
					},
					error: (error) => {
						this.error = error.message || 'Errore nella generazione del QR code';
					}
				});
			} catch (error) {
				this.error = 'Errore nella generazione del QR code';
			} finally {
				this.loading.order = false;
			}
		},

		async downloadQr(format) {
			if (!this.qrResult) return;
			
			this.loading.download = true;
			
			try {
				const params = new URLSearchParams({
					type: this.qrResult.data.type,
					id: this.qrResult.data.id,
					format: format
				});
				
				window.open(`/qr/download?${params.toString()}`, '_blank');
			} catch (error) {
				this.error = 'Errore nel download del QR code';
			} finally {
				this.loading.download = false;
			}
		}
	}
};
</script>

<style scoped>
.qr-code-display {
	border: 1px solid #e0e0e0;
	border-radius: 8px;
	padding: 20px;
	background: white;
}

.qr-code-display svg {
	max-width: 100%;
	height: auto;
}
</style> 