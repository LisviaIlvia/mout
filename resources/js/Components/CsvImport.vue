<template>
	<v-dialog v-model="dialog" max-width="600px">
		<v-card>
			<v-card-title class="d-flex align-center">
				<v-icon icon="fa-solid fa-file-csv" class="mr-3" color="primary"></v-icon>
				Importa {{ title }} da CSV
			</v-card-title>
			
			<v-card-text>
				<v-alert
					v-if="showAlert"
					:type="alertType"
					:title="alertTitle"
					:text="alertMessage"
					class="mb-4"
				></v-alert>
				
				<v-form ref="form" @submit.prevent="handleImport">
					<v-file-input
						v-model="csvFile"
						:label="fileLabel"
						accept=".csv,.txt"
						:rules="fileRules"
						:error-messages="fileErrors"
						@update:model-value="onFileChange"
						prepend-icon="fa-solid fa-upload"
						class="mb-4"
					></v-file-input>
					
					<v-select
						v-model="selectedYear"
						:items="yearOptions"
						label="Anno"
						:rules="[v => !!v || 'Anno è obbligatorio']"
						class="mb-4"
					></v-select>
					
					<v-expansion-panels v-if="showTemplate" class="mb-4">
						<v-expansion-panel>
							<v-expansion-panel-title>
								<v-icon icon="fa-solid fa-download" class="mr-2"></v-icon>
								Scarica Template CSV
							</v-expansion-panel-title>
							<v-expansion-panel-text>
								<p class="text-body-2 mb-2">
									Scarica il template CSV per vedere la struttura corretta dei dati:
								</p>
								<v-btn
									variant="outlined"
									color="primary"
									size="small"
									@click="downloadTemplate"
									prepend-icon="fa-solid fa-download"
								>
									Scarica Template
								</v-btn>
							</v-expansion-panel-text>
						</v-expansion-panel>
					</v-expansion-panels>
					
					<v-expansion-panels v-if="showInstructions" class="mb-4">
						<v-expansion-panel>
							<v-expansion-panel-title>
								<v-icon icon="fa-solid fa-info-circle" class="mr-2"></v-icon>
								Istruzioni Import
							</v-expansion-panel-title>
							<v-expansion-panel-text>
								<div class="text-body-2">
									<h4 class="mb-2">Formato CSV richiesto:</h4>
									<ul class="mb-3">
										<li><strong>numero</strong>: Numero ordine (opzionale, generato automaticamente se vuoto)</li>
										<li><strong>data</strong>: Data ordine (formato: dd/mm/yyyy o yyyy-mm-dd)</li>
										<li><strong>cliente</strong>: Nome del cliente (deve esistere nel sistema)</li>
										<li><strong>note</strong>: Note aggiuntive (opzionale)</li>
										<li><strong>stato</strong>: Stato ordine (opzionale, default: Bozza)</li>
										<li><strong>indirizzo</strong>: Indirizzo di spedizione (opzionale)</li>
										<li><strong>comune</strong>: Comune (opzionale)</li>
										<li><strong>provincia</strong>: Provincia (opzionale)</li>
										<li><strong>cap</strong>: CAP (opzionale)</li>
										<li><strong>prodotti</strong>: Prodotti nel formato "codice:quantità:prezzo;codice2:quantità2:prezzo2" (opzionale)</li>
										<li><strong>descrizione</strong>: Descrizione aggiuntiva (opzionale)</li>
									</ul>
									
									<h4 class="mb-2">Esempio riga CSV:</h4>
									<code class="d-block pa-2 bg-grey-lighten-4 rounded">
										ODV00001/2024,15/01/2024,Mario Rossi,Note ordine,Bozza,Via Roma 1,Milano,MI,20100,MER001:2:100.50;MER002:1:75.00,Descrizione ordine
									</code>
								</div>
							</v-expansion-panel-text>
						</v-expansion-panel>
					</v-expansion-panels>
				</v-form>
			</v-card-text>
			
			<v-card-actions class="pa-4">
				<v-spacer></v-spacer>
				<v-btn
					color="grey"
					variant="text"
					@click="closeDialog"
					:disabled="loading"
				>
					Annulla
				</v-btn>
				<v-btn
					color="primary"
					@click="handleImport"
					:loading="loading"
					:disabled="!csvFile || !selectedYear"
				>
					<v-icon icon="fa-solid fa-upload" class="mr-2"></v-icon>
					Importa
				</v-btn>
			</v-card-actions>
		</v-card>
	</v-dialog>
</template>

<script>
export default {
	name: 'CsvImport',
	props: {
		title: {
			type: String,
			default: 'Dati'
		},
		fileLabel: {
			type: String,
			default: 'Seleziona file CSV'
		},
		importUrl: {
			type: String,
			required: true
		},
		showTemplate: {
			type: Boolean,
			default: true
		},
		showInstructions: {
			type: Boolean,
			default: true
		}
	},
	data() {
		return {
			dialog: false,
			csvFile: null,
			selectedYear: new Date().getFullYear(),
			loading: false,
			fileErrors: [],
			showAlert: false,
			alertType: 'info',
			alertTitle: '',
			alertMessage: '',
			yearOptions: []
		};
	},
	computed: {
		fileRules() {
			return [
				v => !!v || 'File CSV è obbligatorio',
				v => !v || v.size <= 10 * 1024 * 1024 || 'File non può superare 10MB',
				v => !v || v.type === 'text/csv' || v.name.endsWith('.csv') || v.name.endsWith('.txt') || 'File deve essere in formato CSV'
			];
		}
	},
	mounted() {
		this.generateYearOptions();
	},
	methods: {
		openDialog() {
			this.dialog = true;
			this.resetForm();
		},
		
		closeDialog() {
			this.dialog = false;
			this.resetForm();
		},
		
		resetForm() {
			this.csvFile = null;
			this.selectedYear = new Date().getFullYear();
			this.loading = false;
			this.fileErrors = [];
			this.showAlert = false;
			if (this.$refs.form) {
				this.$refs.form.reset();
			}
		},
		
		onFileChange(file) {
			this.fileErrors = [];
			this.showAlert = false;
			
			if (file) {
				// Validazione file
				if (file.size > 10 * 1024 * 1024) {
					this.fileErrors.push('File non può superare 10MB');
					this.csvFile = null;
					return;
				}
				
				if (!file.name.endsWith('.csv') && !file.name.endsWith('.txt')) {
					this.fileErrors.push('File deve essere in formato CSV');
					this.csvFile = null;
					return;
				}
			}
		},
		
		async handleImport() {
			if (!this.csvFile || !this.selectedYear) {
				return;
			}
			
			// Validazione form
			const { valid } = await this.$refs.form.validate();
			if (!valid) {
				return;
			}
			
			this.loading = true;
			this.showAlert = false;
			
			try {
				const formData = new FormData();
				formData.append('csv_file', this.csvFile);
				formData.append('year', this.selectedYear);
				
				const response = await this.$axiosService.post({
					url: this.importUrl,
					data: formData,
					headers: {
						'Content-Type': 'multipart/form-data'
					}
				});
				
				if (response.success) {
					this.showSuccessAlert(response.message, response.imported_count);
					this.$emit('import-success', response);
					this.closeDialog();
				} else {
					this.showErrorAlert(response.message, response.errors);
				}
				
			} catch (error) {
				console.error('Errore importazione:', error);
				let errorMessage = 'Errore durante l\'importazione';
				
				if (error.response?.data?.message) {
					errorMessage = error.response.data.message;
				} else if (error.message) {
					errorMessage = error.message;
				}
				
				this.showErrorAlert(errorMessage);
			} finally {
				this.loading = false;
			}
		},
		
		showSuccessAlert(message, importedCount) {
			this.alertType = 'success';
			this.alertTitle = 'Importazione Completata';
			this.alertMessage = message;
			this.showAlert = true;
			
			// Mostra notifica globale
			if (window.flashMessage) {
				window.flashMessage({
					type: 'success',
					text: message
				});
			}
		},
		
		showErrorAlert(message, errors = null) {
			this.alertType = 'error';
			this.alertTitle = 'Errore Importazione';
			
			let fullMessage = message;
			if (errors && Array.isArray(errors)) {
				fullMessage += '\n\nErrori dettagliati:\n' + errors.join('\n');
			}
			
			this.alertMessage = fullMessage;
			this.showAlert = true;
		},
		
		generateYearOptions() {
			const currentYear = new Date().getFullYear();
			this.yearOptions = [];
			
			for (let year = currentYear - 2; year <= currentYear + 2; year++) {
				this.yearOptions.push({
					title: year.toString(),
					value: year
				});
			}
		},
		
		downloadTemplate() {
			// Crea template CSV per ordini vendita
			const headers = [
				'numero',
				'data',
				'cliente',
				'note',
				'stato',
				'indirizzo',
				'comune',
				'provincia',
				'cap',
				'prodotti',
				'descrizione'
			];
			
			const exampleRow = [
				'ODV00001/2024',
				'15/01/2024',
				'Mario Rossi',
				'Note ordine',
				'Bozza',
				'Via Roma 1',
				'Milano',
				'MI',
				'20100',
				'MER001:2:100.50;MER002:1:75.00',
				'Descrizione ordine'
			];
			
			const csvContent = [headers.join(','), exampleRow.join(',')].join('\n');
			const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
			const link = document.createElement('a');
			
			if (link.download !== undefined) {
				const url = URL.createObjectURL(blob);
				link.setAttribute('href', url);
				link.setAttribute('download', 'template_ordini_vendita.csv');
				link.style.visibility = 'hidden';
				document.body.appendChild(link);
				link.click();
				document.body.removeChild(link);
			}
		}
	}
};
</script>

<style scoped>
.v-expansion-panel-text {
	max-height: 400px;
	overflow-y: auto;
}

code {
	font-family: 'Courier New', monospace;
	font-size: 0.875rem;
	line-height: 1.4;
}
</style> 