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

		<!-- Prodotti/Servizi -->
		<v-row class="pa-4" v-if="order.products && order.products.length > 0">
			<v-col cols="12">
				<v-card variant="outlined">
					<v-card-title class="text-h6">
						<i class="fa-solid fa-boxes me-2"></i>
						Prodotti/Servizi
					</v-card-title>
					
					<v-data-table
						:headers="productHeaders"
						:items="order.products"
						:items-per-page="10"
						density="compact"
						class="elevation-0"
					>
						<template v-slot:item.product.nome="{ item }">
							{{ item.product?.nome || 'N/A' }}
						</template>
						
						<template v-slot:item.quantita="{ item }">
							{{ item.quantita }}
						</template>
						
						<template v-slot:item.prezzo="{ item }">
							€ {{ formatPrice(item.prezzo) }}
						</template>
						
						<template v-slot:item.aliquota_iva.aliquota="{ item }">
							{{ item.aliquotaIva?.aliquota || '0' }}%
						</template>
						
						<template v-slot:item.totale="{ item }">
							€ {{ formatPrice(item.quantita * item.prezzo) }}
						</template>
					</v-data-table>
				</v-card>
			</v-col>
		</v-row>

		<!-- Riepilogo totale -->
		<v-row class="pa-4" v-if="order.products && order.products.length > 0">
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
			return this.order.products.reduce((total, product) => total + (product.quantita * product.prezzo), 0);
		},
		
		// Calcola l'IVA totale
		calcolaIva() {
			return this.order.products.reduce((total, product) => {
				const aliquota = product.aliquotaIva?.aliquota || 0;
				return total + (product.quantita * product.prezzo * aliquota / 100);
			}, 0);
		},
		
		// Calcola il totale
		calcolaTotale() {
			return this.calcolaImponibile() + this.calcolaIva();
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