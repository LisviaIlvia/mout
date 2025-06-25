<template>
	<documents-header
		:intestatari="data.intestatari"
		:tipiIntestatari="data.tipiIntestatari"
		:numero="data.numero"
		:previousDocDate="data.previousDocDate"
		:nextDocDate="data.nextDocDate"
		:interlocutore="data.interlocutore"
		:entityId="elementId"
		:entityType="elementCustom"
		:color="crudDialog.color"
		:errors="crudDialog.errors"
		:readonly="readonly"
		:dettagliActive="dataElements.dettagli_active || false"
		@ready="handleReadyHeader"
		ref="headerDocumentRef"
	/>
	<!-- Aggiungo fornitori -->
	<documents-body
		:prodotti="data.prodotti"
		:aliquoteIva="data.aliquoteIva"
		:fornitori="data.fornitori"
		:isVendita="isVendita"
		:color="crudDialog.color"
		:errors="crudDialog.errors"
		:readonly="readonly"
		@ready="handleReadyBody"
		ref="bodyDocumentRef"
	/>
	<documents-footer
		:color="crudDialog.color"
		:errors="crudDialog.errors"
		@ready="handleReadyFooter"
		:readonly="readonly"
		ref="footerDocumentRef"
	/>

</template>

<script>
export default {
	name: 'DocumentsContent',
	props: {
		crudDialog: {
			type: Object,
			required: true
		},
		dataElements: {
			type: Object,
			required: true
		},
		elementId: {
			type: Number,
			default: null
		},
		elementCustom: {
			type: String,
			default: null
		},
		readonly: {
			type: Boolean,
			default: false
		},
		crud: {
			type: String,
			required: true
		}
	},
	provide() {
		return {
			crud: this.crud
		}
	},
	computed: {
		isVendita() {
			// Determina se è un documento di vendita controllando gli intestatari
			// Se contiene 'clienti', è un documento di vendita
			return this.data.tipiIntestatari && this.data.tipiIntestatari.some(tipo => tipo.value === 'clienti');
		}
	},
	mounted() {
		this.getData();
	},
	data() {
		return this.initialState();
	},
	methods: {
		initialState() {
			return {
				setup: {
					nameField: 'numero',
					containsFile: true
				},
				data: {
					aliquoteIva: [],
					tipiIntestatari: [],
					intestatari: {},
					numero: '',
					previousDocDate: '',
					nextDocDate: '',
					interlocutore: '',
					prodotti: [],
					metodiPagamento: [],
					document: null,
					contiBancari: [],
					spedizioni: [],
					ricorrenze: [],
					allegati: [],
					fornitori: []
				}
			};
		},
		getData() {
			this.data.aliquoteIva = this.dataElements.aliquote_iva || [];
			this.data.tipiIntestatari = this.dataElements.tipi_intestatari || [];
			this.data.prodotti = this.dataElements.prodotti || {};
			this.data.metodiPagamento = this.dataElements.metodi_pagamento || [];
			this.data.contiBancari = this.dataElements.conti_bancari || [];
			this.data.spedizioni = this.dataElements.spedizioni || [];
			this.data.ricorrenze = this.dataElements.ricorrenze || [];
			this.data.fornitori = this.dataElements.fornitori || [];
			
			// Gestisci i dati degli intestatari
			if (this.dataElements.intestatari) {
				this.data.intestatari = this.dataElements.intestatari;
			}
			
			if(this.dataElements.resource) {
				this.data.previousDocDate = this.dataElements.resource.previous_doc_date || '';
				this.data.interlocutore = this.dataElements.resource.interlocutore || '';
				// Se resource ha intestatari, sovrascrivi quelli generali
				if (this.dataElements.resource.intestatari) {
					this.data.intestatari = this.dataElements.resource.intestatari;
				}
				if(this.crud == 'create') {
					this.data.numero = this.dataElements.resource.numero || '';
				}
				if(this.crud == 'edit' || this.crud == 'show') {
					this.data.nextDocDate = this.dataElements.resource.next_doc_date || '';
				}
			}
		},
		// 
		getForm() {
			const headerFormData = this.$refs.headerDocumentRef?.getForm?.() || {};
			const bodyFormData = this.$refs.bodyDocumentRef?.getForm?.() || {};
			const footerFormData = this.$refs.footerDocumentRef?.getForm?.() || {};
			const combinedFormData = { ...this.form, ...headerFormData, ...bodyFormData, ...footerFormData };
			
			return combinedFormData;
		},
		handleReadyHeader() {
			const headerDoc = this.$refs.headerDocumentRef;
			headerDoc.trasportoActive = this.dataElements.trasporto_active || false;
			const entrata = this.dataElements.entrata;
			if(entrata) headerDoc.numeroBlock = false;
			headerDoc.data.stati = this.dataElements.stati || [];
			
			// Gestisci i dati in base al tipo di operazione
			if(this.crud == 'edit' || this.crud == 'show') {
				// Per edit e show, i dati possono essere in resource.document o direttamente in main
				const documentData = this.dataElements.resource?.document || this.dataElements.main || {};
				
				if(documentData.numero) {
					headerDoc.form.numero = documentData.numero;
				}
				if(documentData.data) {
					headerDoc.form.data = new Date(documentData.data);
				}
				if(documentData.entity_type) {
					headerDoc.form.entity_type = documentData.entity_type;
				}
				if(documentData.entity_id) {
					headerDoc.form.entity_id = documentData.entity_id;
				}
				if(this.dataElements.resource?.stato) {
					headerDoc.form.stato = this.dataElements.resource.stato;
				}
				if(documentData.indirizzo) {
					headerDoc.data.indirizzo = documentData.indirizzo;
				}
				
				// Gestisci i dettagli
				if (documentData.dettagli) {
					headerDoc.data.dettagli = documentData.dettagli;
				} else {
					console.log('DocumentsContent - Nessun dettaglio trovato in documentData:', documentData);
				}
				
				// Gestisci le relazioni solo per show
				if(this.crud == 'show' && entrata === false) {
					headerDoc.data.parents = this.dataElements.resource?.relation?.parents || [];
					headerDoc.data.children = this.dataElements.resource?.relation?.children || [];
				}
			}
			
			if(this.crud == 'create') {
				// Per la creazione, usa i dettagli di default dal backend
				if (this.dataElements.dettagli) {
					headerDoc.data.dettagli = this.dataElements.dettagli;
				}
			}
		},
		handleReadyBody() {
			const bodyDoc = this.$refs.bodyDocumentRef;
			bodyDoc.trasportoActive = this.dataElements.trasporto_active || false;
			bodyDoc.spedizioneActive = this.dataElements.spedizione_active || false;
			bodyDoc.metodoPagamentoActive = this.dataElements.metodo_pagamento_active || false;
			bodyDoc.rateActive = this.dataElements.rate_active || false;
			
			// Gestisci i dati in base al tipo di operazione
			if(this.crud == 'edit' || this.crud == 'show') {
				// Per edit e show, i dati possono essere in resource.document o direttamente in main
				const documentData = this.dataElements.resource?.document || this.dataElements.main || {};
				
				if(documentData.elementi) {
					bodyDoc.form.elementi = documentData.elementi;
				}
				if(documentData.spedizione) {
					bodyDoc.data.spedizione = documentData.spedizione;
				}
				if(documentData.metodo_pagamento_id) {
					bodyDoc.data.metodo_pagamento_id = documentData.metodo_pagamento_id;
				}
				if(documentData.conto_bancario_id) {
					bodyDoc.data.conto_bancario_id = documentData.conto_bancario_id;
				}
				if(documentData.rate) {
					let rate = documentData.rate || [];
					rate.forEach((rata) => {
						if (rata.data !== null) {
							rata.data = new Date(rata.data);
						}
					});
					bodyDoc.data.rate = rate;
				}
			}
		},
		handleReadyFooter() {
			const footerDoc = this.$refs.footerDocumentRef;
			if(this.dataElements.entrata === false) footerDoc.trasportoActive = this.dataElements.trasporto_active || false;
			
			// Gestisci i dati in base al tipo di operazione
			if(this.crud == 'edit' || this.crud == 'show') {
				// Per edit e show, i dati possono essere in resource.document o direttamente in main
				const documentData = this.dataElements.resource?.document || this.dataElements.main || {};
				
				if(documentData.note) {
					footerDoc.form.note = documentData.note;
				}
				if(documentData.allegati) {
					footerDoc.data.allegati = documentData.allegati;
				}
				if(documentData.trasporto) {
					footerDoc.data.trasporto = documentData.trasporto;
				}
			}
		}
	}
}
</script>
