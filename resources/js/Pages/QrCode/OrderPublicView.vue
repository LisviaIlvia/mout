<template>
	<div class="order-public-view">
		<!-- Informazioni principali -->
		<v-row class="pa-4">
			<v-col cols="12" md="6">
				<v-card variant="outlined" class="pa-4">
					<h3 class="text-h6 mb-3">
						<i class="fa-solid fa-building me-2"></i>
						Cliente/Fornitore
					</h3>
					
					<v-list density="compact">
						<v-list-item>
							<template v-slot:prepend>
								<i class="fa-solid fa-user text-grey me-2"></i>
							</template>
							<v-list-item-title>{{ order.entity?.nome || 'N/A' }}</v-list-item-title>
						</v-list-item>
						
						<v-list-item v-if="order.entity?.partita_iva">
							<template v-slot:prepend>
								<i class="fa-solid fa-id-card text-grey me-2"></i>
							</template>
							<v-list-item-title>P.IVA: {{ order.entity.partita_iva }}</v-list-item-title>
						</v-list-item>
						
						<v-list-item v-if="order.entity?.codice_fiscale">
							<template v-slot:prepend>
								<i class="fa-solid fa-id-badge text-grey me-2"></i>
							</template>
							<v-list-item-title>CF: {{ order.entity.codice_fiscale }}</v-list-item-title>
						</v-list-item>

						<v-list-item v-if="order.entity?.email">
							<template v-slot:prepend>
								<i class="fa-solid fa-envelope text-grey me-2"></i>
							</template>
							<v-list-item-title>Email: {{ order.entity.email }}</v-list-item-title>
						</v-list-item>
					</v-list>
				</v-card>
			</v-col>

			<v-col cols="12" md="6">
				<v-card variant="outlined" class="pa-4">
					<h3 class="text-h6 mb-3">
						<i class="fa-solid fa-calendar me-2"></i>
						Informazioni Ordine
					</h3>
					
					<v-list density="compact">
						<v-list-item>
							<template v-slot:prepend>
								<i class="fa-solid fa-hashtag text-grey me-2"></i>
							</template>
							<v-list-item-title>Numero: {{ order.numero }}</v-list-item-title>
						</v-list-item>
						
						<v-list-item v-if="order.data">
							<template v-slot:prepend>
								<i class="fa-solid fa-calendar-day text-grey me-2"></i>
							</template>
							<v-list-item-title>Data: {{ formatDate(order.data) }}</v-list-item-title>
						</v-list-item>
						
						<v-list-item v-if="order.stato">
							<template v-slot:prepend>
								<i class="fa-solid fa-info-circle text-grey me-2"></i>
							</template>
							<v-list-item-title>
								Stato: 
								<v-chip 
									:color="getStatusColor(order.stato)" 
									size="small"
									class="ml-2"
								>
									{{ order.stato }}
								</v-chip>
							</v-list-item-title>
						</v-list-item>
					</v-list>
				</v-card>
			</v-col>
		</v-row>

		<!-- Indirizzo -->
		<v-row class="pa-4" v-if="order.indirizzo">
			<v-col cols="12">
				<v-card variant="outlined" class="pa-4">
					<h3 class="text-h6 mb-3">
						<i class="fa-solid fa-map-marker-alt me-2"></i>
						Indirizzo di Consegna
					</h3>
					
					<v-list density="compact">
						<v-list-item v-if="order.indirizzo.nome">
							<template v-slot:prepend>
								<i class="fa-solid fa-user text-grey me-2"></i>
							</template>
							<v-list-item-title>{{ order.indirizzo.nome }}</v-list-item-title>
						</v-list-item>
						
						<v-list-item v-if="order.indirizzo.indirizzo">
							<template v-slot:prepend>
								<i class="fa-solid fa-road text-grey me-2"></i>
							</template>
							<v-list-item-title>{{ order.indirizzo.indirizzo }}</v-list-item-title>
						</v-list-item>
						
						<v-list-item v-if="order.indirizzo.comune || order.indirizzo.cap">
							<template v-slot:prepend>
								<i class="fa-solid fa-city text-grey me-2"></i>
							</template>
							<v-list-item-title>
								{{ order.indirizzo.cap }} {{ order.indirizzo.comune }}
								<span v-if="order.indirizzo.provincia">({{ order.indirizzo.provincia }})</span>
							</v-list-item-title>
						</v-list-item>
						
						<v-list-item v-if="order.indirizzo.telefono">
							<template v-slot:prepend>
								<i class="fa-solid fa-phone text-grey me-2"></i>
							</template>
							<v-list-item-title>Tel: {{ order.indirizzo.telefono }}</v-list-item-title>
						</v-list-item>
					</v-list>
				</v-card>
			</v-col>
		</v-row>

		<!-- Dettagli Tecnici -->
		<v-row class="pa-4" v-if="order.dettagli && hasDettagliData">
			<v-col cols="12">
				<v-card variant="outlined" class="pa-4">
					<h3 class="text-h6 mb-3">
						<i class="fa-solid fa-cogs me-2"></i>
						Dettagli Tecnici
					</h3>
					
					<v-row>
						<v-col cols="12" md="6">
							<v-list density="compact">
								<v-list-item v-if="order.dettagli.data_evasione">
									<template v-slot:prepend>
										<i class="fa-solid fa-calendar-check text-grey me-2"></i>
									</template>
									<v-list-item-title>Data Evasione: {{ formatDate(order.dettagli.data_evasione) }}</v-list-item-title>
								</v-list-item>
								
								<v-list-item v-if="order.dettagli.mod_poltrona">
									<template v-slot:prepend>
										<i class="fa-solid fa-chair text-grey me-2"></i>
									</template>
									<v-list-item-title>Modello Poltrona: {{ order.dettagli.mod_poltrona }}</v-list-item-title>
								</v-list-item>
								
								<v-list-item v-if="order.dettagli.quantita">
									<template v-slot:prepend>
										<i class="fa-solid fa-hashtag text-grey me-2"></i>
									</template>
									<v-list-item-title>Quantità: {{ order.dettagli.quantita }}</v-list-item-title>
								</v-list-item>
								
								<v-list-item v-if="order.dettagli.fianchi_finali">
									<template v-slot:prepend>
										<i class="fa-solid fa-arrows-alt-h text-grey me-2"></i>
									</template>
									<v-list-item-title>Fianchi Finali: {{ order.dettagli.fianchi_finali }}</v-list-item-title>
								</v-list-item>
								
								<v-list-item v-if="order.dettagli.interasse_cm">
									<template v-slot:prepend>
										<i class="fa-solid fa-ruler-horizontal text-grey me-2"></i>
									</template>
									<v-list-item-title>Interasse: {{ order.dettagli.interasse_cm }} cm</v-list-item-title>
								</v-list-item>
							</v-list>
						</v-col>
						
						<v-col cols="12" md="6">
							<v-list density="compact">
								<v-list-item v-if="order.dettagli.largh_bracciolo_cm">
									<template v-slot:prepend>
										<i class="fa-solid fa-ruler text-grey me-2"></i>
									</template>
									<v-list-item-title>Larghezza Bracciolo: {{ order.dettagli.largh_bracciolo_cm }} cm</v-list-item-title>
								</v-list-item>
								
								<v-list-item v-if="order.dettagli.rivestimento">
									<template v-slot:prepend>
										<i class="fa-solid fa-palette text-grey me-2"></i>
									</template>
									<v-list-item-title>Rivestimento: {{ order.dettagli.rivestimento }}</v-list-item-title>
								</v-list-item>
							</v-list>
						</v-col>
					</v-row>

					<!-- Opzioni Aggiuntive -->
					<div v-if="hasDettagliOptions" class="mt-4">
						<h4 class="text-subtitle-1 mb-2">Opzioni Aggiuntive:</h4>
						<v-row>
							<v-col cols="6" md="3" v-if="order.dettagli.ricamo_logo">
								<v-chip color="success" size="small">
									<i class="fa-solid fa-check me-1"></i>
									Ricamo Logo
								</v-chip>
							</v-col>
							<v-col cols="6" md="3" v-if="order.dettagli.pendenza">
								<v-chip color="success" size="small">
									<i class="fa-solid fa-check me-1"></i>
									Pendenza
								</v-chip>
							</v-col>
							<v-col cols="6" md="3" v-if="order.dettagli.fissaggio_pavimento">
								<v-chip color="success" size="small">
									<i class="fa-solid fa-check me-1"></i>
									Fissaggio Pavimento
								</v-chip>
							</v-col>
							<v-col cols="6" md="3" v-if="order.dettagli.montaggio">
								<v-chip color="success" size="small">
									<i class="fa-solid fa-check me-1"></i>
									Montaggio
								</v-chip>
							</v-col>
						</v-row>
					</div>
				</v-card>
			</v-col>
		</v-row>

		<!-- Elementi Ordinati -->
		<v-row class="pa-4" v-if="order.elementi && order.elementi.length > 0">
			<v-col cols="12">
				<v-card variant="outlined">
					<v-card-title class="text-h6">
						<i class="fa-solid fa-boxes me-2"></i>
						Elementi Ordinati
					</v-card-title>
					
					<div v-for="(elementi, categoria) in order.elementiPerCategoria" :key="categoria" class="mb-4">
						<!-- Header categoria -->
						<div class="pa-3 bg-grey-lighten-4">
							<h4 class="text-subtitle-1 font-weight-bold">{{ categoria.toUpperCase() }}</h4>
						</div>
						
						<!-- Tabella elementi per categoria -->
						<v-data-table
							:headers="getHeadersForCategory(categoria)"
							:items="elementi"
							:items-per-page="10"
							density="compact"
							class="elevation-0"
							hide-default-footer
						>
							<!-- Codice -->
							<template v-slot:item.codice="{ item }">
								{{ item.codice || '-' }}
							</template>
							
							<!-- Nome/Descrizione -->
							<template v-slot:item.nome="{ item }">
								{{ item.nome || item.descrizione || '-' }}
							</template>
							
							<!-- Fornitore -->
							<template v-slot:item.fornitore="{ item }">
								{{ item.fornitore_id ? getFornitoreName(item.fornitore_id) : '-' }}
							</template>
							
							<!-- Riferimento -->
							<template v-slot:item.riferimento="{ item }">
								{{ item.riferimento || '-' }}
							</template>
							
							<!-- Quantità -->
							<template v-slot:item.quantita="{ item }">
								{{ item.quantita || '-' }}
							</template>
							
							<!-- Unità di misura -->
							<template v-slot:item.unita_misura="{ item }">
								{{ item.unita_misura || '-' }}
							</template>
							
							<!-- Prezzo -->
							<template v-slot:item.prezzo="{ item }">
								{{ item.prezzo ? `€ ${formatPrice(item.prezzo)}` : '-' }}
							</template>
							
							<!-- IVA -->
							<template v-slot:item.iva="{ item }">
								{{ item.iva?.aliquota ? `${item.iva.aliquota}%` : '-' }}
							</template>
							
							<!-- Importo -->
							<template v-slot:item.importo="{ item }">
								{{ item.importo ? `€ ${formatPrice(item.importo)}` : '-' }}
							</template>
						</v-data-table>
					</div>
				</v-card>
			</v-col>
		</v-row>

		<!-- Riepilogo totale -->
		<v-row class="pa-4" v-if="order.elementi && order.elementi.length > 0">
			<v-col cols="12" md="6" offset-md="6">
				<v-card variant="outlined" class="pa-4">
					<h3 class="text-h6 mb-3">
						<i class="fa-solid fa-calculator me-2"></i>
						Riepilogo
					</h3>
					
					<v-list density="compact">
						<v-list-item>
							<v-list-item-title>Imponibile:</v-list-item-title>
							<template v-slot:append>
								<strong>€ {{ formatPrice(calcolaImponibile()) }}</strong>
							</template>
						</v-list-item>
						
						<v-list-item>
							<v-list-item-title>IVA:</v-list-item-title>
							<template v-slot:append>
								<strong>€ {{ formatPrice(calcolaIva()) }}</strong>
							</template>
						</v-list-item>
						
						<v-divider class="my-2"></v-divider>
						
						<v-list-item>
							<v-list-item-title class="text-h6">Totale:</v-list-item-title>
							<template v-slot:append>
								<strong class="text-h6">€ {{ formatPrice(calcolaTotale()) }}</strong>
							</template>
						</v-list-item>
					</v-list>
				</v-card>
			</v-col>
		</v-row>

		<!-- Allegati -->
		<v-row class="pa-4" v-if="order.media && order.media.length > 0">
			<v-col cols="12">
				<v-card variant="outlined" class="pa-4">
					<h3 class="text-h6 mb-3">
						<i class="fa-solid fa-paperclip me-2"></i>
						Allegati
					</h3>
					
					<v-row>
						<v-col cols="12" md="4" v-for="media in order.media" :key="media.id">
							<v-card variant="outlined" class="pa-3">
								<div class="d-flex align-center mb-2">
									<i :class="getFileIcon(media.mime_type)" class="me-2"></i>
									<span class="text-subtitle-2">{{ media.name }}</span>
								</div>
								
								
								<!-- Mostra immagine se è un file immagine -->
								<div v-if="isImage(media.mime_type)" class="mt-2">
									<img 
										:src="getMediaUrl(media)" 
										:alt="media.name"
										class="img-fluid"
										style="max-width: 100%; max-height: 200px; object-fit: contain;"
									>
								</div>
								
								<!-- Per file non immagine -->
								<div v-else class="mt-2">
									<v-chip 
										:color="getFileColor(media.mime_type)" 
										size="small"
										variant="outlined"
									>
										{{ getFileTypeLabel(media.mime_type) }}
									</v-chip>
								</div>
							</v-card>
						</v-col>
					</v-row>
				</v-card>
			</v-col>
		</v-row>

		<!-- Note -->
		<v-row class="pa-4" v-if="order.note">
			<v-col cols="12">
				<v-card variant="outlined" class="pa-4">
					<h3 class="text-h6 mb-3">
						<i class="fa-solid fa-sticky-note me-2"></i>
						Note
					</h3>
					<p class="text-body-1">{{ order.note }}</p>
				</v-card>
			</v-col>
		</v-row>
	</div>
</template>

<script>
export default {
	name: 'OrderPublicView',
	props: {
		order: {
			type: Object,
			required: true
		}
	},
	data() {
		return {
			// Headers per la tabella prodotti
			productHeaders: [
				{ title: 'Prodotto', key: 'product.nome', sortable: true },
				{ title: 'Quantità', key: 'quantita', sortable: true },
				{ title: 'Prezzo Unit.', key: 'prezzo', sortable: true },
				{ title: 'IVA', key: 'aliquota_iva.aliquota', sortable: true },
				{ title: 'Totale', key: 'totale', sortable: true }
			]
		};
	},
	computed: {
		// Determina il tipo di ordine per il label
		orderTypeLabel() {
			if (this.order.type === 'ordini-vendita') {
				return 'Ordine di Vendita';
			} else if (this.order.type === 'ordini-acquisto') {
				return 'Ordine di Acquisto';
			}
			return 'Ordine';
		},
		
		// Verifica se ci sono dati nei dettagli
		hasDettagliData() {
			if (!this.order.dettagli) return false;
			return this.order.dettagli.data_evasione ||
				   this.order.dettagli.mod_poltrona || 
				   this.order.dettagli.quantita || 
				   this.order.dettagli.fianchi_finali || 
				   this.order.dettagli.interasse_cm || 
				   this.order.dettagli.largh_bracciolo_cm || 
				   this.order.dettagli.rivestimento;
		},
		
		// Verifica se ci sono opzioni nei dettagli
		hasDettagliOptions() {
			if (!this.order.dettagli) return false;
			return this.order.dettagli.ricamo_logo || 
				   this.order.dettagli.pendenza || 
				   this.order.dettagli.fissaggio_pavimento || 
				   this.order.dettagli.montaggio;
		}
	},
	methods: {
		// Formatta la data in formato italiano
		formatDate(date) {
			if (!date) return 'N/A';
			
			const d = new Date(date);
			return d.toLocaleDateString('it-IT', {
				day: '2-digit',
				month: '2-digit',
				year: 'numeric'
			});
		},
		
		// Formatta il prezzo con 2 decimali
		formatPrice(price) {
			if (!price) return '0,00';
			return parseFloat(price).toFixed(2).replace('.', ',');
		},
		
		// Determina il colore dello stato
		getStatusColor(status) {
			const statusColors = {
				'bozza': 'grey',
				'confermato': 'blue',
				'in_lavorazione': 'orange',
				'completato': 'green',
				'annullato': 'red'
			};
			return statusColors[status] || 'grey';
		},
		
		// Calcola l'imponibile totale
		calcolaImponibile() {
			if (!this.order.elementi) return 0;
			return this.order.elementi.reduce((total, elemento) => {
				if (elemento.tipo === 'descrizione') return total;
				return total + (elemento.importo || 0);
			}, 0);
		},
		
		// Calcola l'IVA totale
		calcolaIva() {
			if (!this.order.elementi) return 0;
			return this.order.elementi.reduce((total, elemento) => {
				if (elemento.tipo === 'descrizione') return total;
				const aliquota = elemento.iva?.aliquota || 0;
				const importo = elemento.importo || 0;
				return total + (importo * aliquota / 100);
			}, 0);
		},
		
	// Calcola il totale
	calcolaTotale() {
		return this.calcolaImponibile() + this.calcolaIva();
	},
		
	// Ottiene gli headers appropriati per categoria
	getHeadersForCategory(categoria) {
		if (categoria === 'Descrizioni') {
			return [
				{ title: 'Descrizione', key: 'descrizione', sortable: false }
			];
		}
		
		return [
			{ title: 'Codice', key: 'codice', sortable: true },
			{ title: 'Nome', key: 'nome', sortable: true },
			{ title: 'Fornitore', key: 'fornitore', sortable: true },
			{ title: 'Rif.', key: 'riferimento', sortable: true },
			{ title: 'Q.tà', key: 'quantita', sortable: true, align: 'center' },
			{ title: 'U.M.', key: 'unita_misura', sortable: true, align: 'center' },
			{ title: 'Prezzo Unit.', key: 'prezzo', sortable: true, align: 'end' },
			{ title: 'IVA', key: 'iva', sortable: true, align: 'center' },
			{ title: 'Importo', key: 'importo', sortable: true, align: 'end' }
		];
	},
		
	// Ottiene il nome del fornitore
	getFornitoreName(fornitoreId) {
		if (!this.order.fornitori) return `Fornitore ${fornitoreId}`;
		const fornitore = this.order.fornitori.find(f => f.id === fornitoreId);
		return fornitore ? fornitore.nome : `Fornitore ${fornitoreId}`;
	},
		
	// Verifica se è un'immagine
	isImage(mimeType) {
		return mimeType && mimeType.startsWith('image/');
	},
		
	// Ottiene l'URL del media
	getMediaUrl(media) {
		// Usa la rotta pubblica per i file media
		const type = this.order.type === 'ordini-vendita' ? 'ordini-vendita' : 'ordini-acquisto';
		return `/public-media/${type}/${media.name}`;
	},
		
	// Ottiene l'icona del file
	getFileIcon(mimeType) {
		if (this.isImage(mimeType)) return 'fa-solid fa-image';
		if (mimeType?.includes('pdf')) return 'fa-solid fa-file-pdf';
		if (mimeType?.includes('word')) return 'fa-solid fa-file-word';
		if (mimeType?.includes('excel')) return 'fa-solid fa-file-excel';
		return 'fa-solid fa-file';
	},
		
	// Ottiene il colore del file
	getFileColor(mimeType) {
		if (this.isImage(mimeType)) return 'success';
		if (mimeType?.includes('pdf')) return 'error';
		if (mimeType?.includes('word')) return 'primary';
		if (mimeType?.includes('excel')) return 'success';
		return 'grey';
	},
		
	// Ottiene il label del tipo di file
	getFileTypeLabel(mimeType) {
		if (this.isImage(mimeType)) return 'Immagine';
		if (mimeType?.includes('pdf')) return 'PDF';
		if (mimeType?.includes('word')) return 'Word';
		if (mimeType?.includes('excel')) return 'Excel';
		return 'File';
	}
	}
};
</script>

<style scoped>
.order-public-view {
	background-color: #f5f5f5;
	min-height: 100vh;
}

/* Stili per la stampa */
@media print {
	.order-public-view {
		background-color: white;
	}
	
	.v-toolbar {
		background-color: #1976d2 !important;
		color: white !important;
	}
}
</style> 