<template>
	<div class="qr-code-component">
		<!-- QR Code Display -->
		<div v-if="qrCode" class="qr-code-wrapper">
			<div 
				v-html="qrCode" 
				class="qr-code-svg"
				:style="{ 
					width: size + 'px', 
					height: size + 'px',
					maxWidth: '100%',
					height: 'auto'
				}"
			></div>
			
			<!-- Overlay con azioni -->
			<div v-if="showActions" class="qr-code-overlay">
				<v-btn
					icon="fa-solid fa-download"
					size="small"
					color="white"
					@click="$emit('download', 'png')"
					:loading="loading"
				></v-btn>
				<v-btn
					icon="fa-solid fa-expand"
					size="small"
					color="white"
					@click="$emit('preview')"
				></v-btn>
			</div>
		</div>

		<!-- Loading State -->
		<div v-else-if="loading" class="qr-code-loading">
			<v-progress-circular
				indeterminate
				:size="size"
				:width="4"
				color="primary"
			></v-progress-circular>
		</div>

		<!-- Error State -->
		<div v-else-if="error" class="qr-code-error">
			<v-alert
				type="error"
				density="compact"
				class="ma-0"
			>
				{{ error }}
			</v-alert>
		</div>

		<!-- Empty State -->
		<div v-else class="qr-code-empty">
			<v-icon
				icon="fa-solid fa-qrcode"
				size="large"
				color="grey-lighten-1"
			></v-icon>
			<p class="text-caption text-grey-lighten-1 mt-2">
				Nessun QR code disponibile
			</p>
		</div>
	</div>
</template>

<script>
export default {
	name: 'QrCodeDisplay',
	props: {
		qrCode: {
			type: String,
			default: null
		},
		size: {
			type: Number,
			default: 200
		},
		loading: {
			type: Boolean,
			default: false
		},
		error: {
			type: String,
			default: null
		},
		showActions: {
			type: Boolean,
			default: false
		}
	},
	emits: ['download', 'preview']
};
</script>

<style scoped>
.qr-code-component {
	position: relative;
	display: inline-block;
}

.qr-code-wrapper {
	position: relative;
	display: inline-block;
	border: 1px solid #e0e0e0;
	border-radius: 8px;
	padding: 16px;
	background: white;
	transition: all 0.3s ease;
}

.qr-code-wrapper:hover {
	box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.qr-code-svg {
	display: block;
}

.qr-code-overlay {
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba(0, 0, 0, 0.7);
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 8px;
	opacity: 0;
	transition: opacity 0.3s ease;
	border-radius: 8px;
}

.qr-code-wrapper:hover .qr-code-overlay {
	opacity: 1;
}

.qr-code-loading,
.qr-code-error,
.qr-code-empty {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	border: 1px solid #e0e0e0;
	border-radius: 8px;
	padding: 32px;
	background: white;
	min-height: 200px;
}

.qr-code-error {
	border-color: #f44336;
}

.qr-code-empty {
	border-style: dashed;
}
</style> 