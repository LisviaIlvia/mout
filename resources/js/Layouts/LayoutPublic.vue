<template>
	<v-app>
		<Head :title="pageTitle" />
		
		<!-- Header pubblico con logo -->
		<v-app-bar elevation="2" color="primary" dark>
			<!-- <v-app-bar-nav-icon @click="goBack" class="me-3">
				<i class="fa-solid fa-arrow-left"></i>
			</v-app-bar-nav-icon> -->
			
			<v-app-bar-title>
				<i class="fa-solid fa-building me-3"></i>
				{{ pageTitle }}
			</v-app-bar-title>
			<v-spacer></v-spacer>
			<v-chip color="white" text-color="primary" class="me-3">
				<i class="fa-solid fa-qrcode me-1"></i>
				Vista Pubblica
			</v-chip>
		</v-app-bar>
		
		<!-- Contenuto principale -->
		<v-main>
			<slot />
		</v-main>
		
		<!-- Footer pubblico -->
		<v-footer class="bg-grey-lighten-4">
			<v-container>
				<v-row justify="center" align="center">
					<v-col cols="12" class="text-center">
						<p class="text-caption text-grey">
							<i class="fa-solid fa-shield-alt me-1"></i>
							Documento visualizzato tramite QR Code - Accesso pubblico sicuro
						</p>
						<p class="text-caption text-grey">
							{{ new Date().toLocaleDateString('it-IT') }}
						</p>
					</v-col>
				</v-row>
			</v-container>
		</v-footer>
	</v-app>
</template>

<script>
import { Head, usePage } from '@inertiajs/vue3';

export default {
	name: 'LayoutPublic',
	components: {
		Head
	},
	computed: {
		pageTitle() {
			// Ottieni il titolo dalla pagina Inertia o usa un default
			const page = usePage();
			return page.props.title || 'Documento Pubblico';
		}
	},
	// methods: {
	// 	goBack() {
	// 		// Se c'è una pagina precedente nella cronologia, torna indietro
	// 		if (window.history.length > 1) {
	// 			window.history.back();
	// 		} else {
	// 			// Altrimenti vai alla home page
	// 			window.location.href = '/';
	// 		}
	// 	}
	// }
};
</script>

<style scoped>
.v-app-bar-title {
	display: flex;
	align-items: center;
}

.v-footer {
	border-top: 1px solid #e0e0e0;
}
</style> 