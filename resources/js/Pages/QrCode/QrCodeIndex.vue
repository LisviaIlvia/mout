<template>
	<v-col cols="4">
		<v-btn
			color="info"
			variant="outlined"
			@click="downloadQr('svg')"
			:loading="window.crudTable?.isQrCodeLoading(qrResult?.data?.id, 'svg') || loading.download"
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
			:loading="window.crudTable?.isQrCodeLoading(qrResult?.data?.id, 'png') || loading.download"
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
			:loading="window.crudTable?.isQrCodeLoading(qrResult?.data?.id, 'eps') || loading.download"
			block
			size="small"
		>
			<i class="fa-solid fa-file-code me-1"></i>
			EPS
		</v-btn>
		<p class="text-caption text-grey mt-1">PostScript</p>
	</v-col>
</template>