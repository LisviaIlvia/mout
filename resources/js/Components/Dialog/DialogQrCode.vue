<template>
	<v-dialog
		v-model="setup.dialog"
		:width="effectiveDialogSetup.width"
		:fullscreen="effectiveDialogSetup.fullscreen"
		:scrim="effectiveDialogSetup.scrim"
		@click:outside="onOutsideClick"
	>
		<v-container :fluid="effectiveDialogSetup.fullscreen" 
		:class="{
			'overflow-dialog': true, 
			'pa-0 min-h100 overflow-auto': effectiveDialogSetup.fullscreen, 
			'pt-5': !effectiveDialogSetup.fullscreen
		}">
			<v-sheet :rounded="!effectiveDialogSetup.fullscreen" :class="{ 'min-h100': effectiveDialogSetup.fullscreen }">
				<v-container :fluid="effectiveDialogSetup.fullscreen" :class="effectiveDialogSetup.fullscreen ? 'sticky-container px-0 pb-0' : 'px-0 pb-0'">
					<v-row align="center" :class="effectiveDialogSetup.fullscreen ? 'mx-0' : 'mt-n10'">
						<v-col cols="auto">
							<v-avatar
							  rounded
							  :color="crudDialog?.color || 'primary'"
							  elevation="5"
							  class="ml-4 px-10 py-10 elevation-4 dialog-avatar"
							>
								<v-icon color="surface" size="x-large" icon="fa-solid fa-qrcode"></v-icon>
							</v-avatar>
						</v-col>
						<v-col class="mt-6">
							<h3 v-html="dialogCustomTitle ?? text.title"></h3>
						</v-col>
						<slot name="header" />
						<v-col cols="auto" class="text-right mt-n3" v-if="effectiveDialogSetup.fullscreen">
							<v-btn
								color="secondary"
								variant="outlined"
								@click="closeDialog"
							>Chiudi</v-btn>
						</v-col>
						<v-col cols="auto" class="text-right mt-n3" v-else>
							<v-btn color="secondary" icon="fa-solid fa-circle-info" variant="text">
							</v-btn>
							<v-tooltip
								activator="parent"
								location="right"
							>{{text.infoTooltip}}</v-tooltip>
						</v-col>
					</v-row>
					<v-divider class="mt-n5"></v-divider>
				</v-container>
				<v-container :fluid="effectiveDialogSetup.fullscreen" :class="['px-10 mt-8', { 'mb-4': !effectiveDialogSetup.fullscreen }]">
					<slot name="content" />
				</v-container>
				<v-container :class="(crudDialog?.bg || 'bg-surface') + ' rounded-be rounded-bs'" v-if="!effectiveDialogSetup.fullscreen">
					<v-row>
						<slot name="footer" />
						<v-spacer></v-spacer>
						<v-col cols="auto" class="text-right">
							<v-btn
								:color="crudDialog?.color || 'primary'"
								variant="outlined"
								@click="closeDialog"
							>Chiudi</v-btn>
						</v-col>
					</v-row>
				</v-container>
			</v-sheet>
		</v-container>
	</v-dialog>
</template>

<script>
export default {
	name: 'DialogQrCode',
	props: {
		dialogTitle: {
			type: String,
			required: true
		},
		dialogType: {
			type: String,
			required: true
		},
		dialogSetup: {
			type: Object,
			default: () => ({})
		},
		crudDialog: {
			type: Object,
			required: true
		},
		dialogCustomTitle: {
			type: String,
			default: null
		}
	},
	computed: {
		effectiveDialogSetup() {
			const defaults = {
				width: '50%',
				fullscreen: false,
				scrim: true
			};
			return { ...defaults, ...this.dialogSetup };
		}
	},
	data() {
		return {
			setup: {
				dialog: false
			},
			text: this.getText(),
			qrType: null,
			qrId: null,
			qrItem: null
		};
	},
	methods: {
		getText() {
			try {
				return this.$tooltipDialog?.show(this.dialogTitle, this.dialogType) || { 
					title: this.dialogTitle, 
					infoTooltip: 'Informazioni' 
				};
			} catch (error) {
				return { 
					title: this.dialogTitle, 
					infoTooltip: 'Informazioni' 
				};
			}
		},
		resetState() {
			this.setup.dialog = false;
			setTimeout(() => {
				this.setup.dialog = false;
				this.qrType = null;
				this.qrId = null;
				this.qrItem = null;
				this.$emit('reset');
			}, 200);
		},
		onOutsideClick(event) {
			if (event.target.className.includes('v-dialog--active')) {
				return;
			}
			this.resetState();
		},
		openDialogQrCode(type, id, item, callback, options = {}) {
			try {
				// Salva i dati per il contenuto
				this.qrType = type;
				this.qrId = id;
				this.qrItem = item;
				
				// Apre il dialog
				this.setup.dialog = true;
				this.$emit('dialog-qr-opened');
				
				if (callback) callback();
			} catch (error) {
				console.error('Errore apertura dialog QR:', error);
			}
		},
		closeDialog() {
			this.resetState();
		}
	}
}
</script>

<style scoped>
	.dialog-avatar {
		z-index: 10;
	}
	.min-h100 {
		min-height: 100%;
	}
	.sticky-container {
		position: -webkit-sticky;
		position: sticky;
		top: 0;
		z-index: 1000;
		background-color: #fff;
	}
	
	.overflow-dialog {
		overflow-y: auto;
	}
	::-webkit-scrollbar {
		width: 12px;
	}
	::-webkit-scrollbar-track {
		background: #E0F2F1;
		border-radius: 4px;
	}
	::-webkit-scrollbar-thumb {
		background-color: #B2DFDB;
		border-radius: 4px;
	}
	::-webkit-scrollbar-thumb:hover {
		background-color: #80CBC4;
		border-radius: 4px;
	}
</style>