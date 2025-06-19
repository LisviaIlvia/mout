<template>
	<v-card
		elevation="4"
		variant="flat"
	>
		<v-toolbar
			class="px-3"
		>
			<v-row class="align-center bg-grey-lighten-3">
				<v-col cols="12">
					<header-box title="Dettagli" icon="fa-solid fa-circle-info" />
				</v-col>
			</v-row>
		</v-toolbar>
		<v-row
			class="px-4 mt-6 pb-3"
		>
			<v-col
				cols="3"
				class="py-0"
			>
                <v-text-field
					v-model="form.mod_poltrona"
					label="Mod. Poltrona"
					:color="color"
					:readonly="readonly"
				></v-text-field>
            </v-col>
            <v-col
                cols="2"
                class="py-0"
            >
                <v-text-field
                    v-if="readonly"
                    :model-value="showDate()"
                    label="Data"
                    :color="color"
                    prepend-inner-icon="fa-solid fa-calendar"
                ></v-text-field>
                <v-date-input
                    v-else
                    v-model="form.data_evasione"
                    placeholder="gg/mm/aaaa"
                    variant="outlined"
                    label="Data evasione"
                    :color="color"
                    :prepend-icon="null"
                    prepend-inner-icon="fa-solid fa-calendar"
                ></v-date-input>
            </v-col>
            <v-col
				cols="3"
				class="py-0"
			>
                <v-text-field
					v-model="form.rivestimento"
					label="Rivestimento"
					:color="color"
					:readonly="readonly"
				></v-text-field>
            </v-col>
            <v-col
				cols="2"
				class="py-0"
			>
                <v-number-input
                    v-model="form.quantita"
                    label="Quantità"
                    :color="color"
                    variant="outlined"
                    control-variant="split"
                    :readonly="readonly"
                ></v-number-input>
            </v-col>
            <v-col
				cols="2"
				class="py-0"
			>
                <v-number-input
                    v-model="form.fianchi_finali"
                    label="Fianchi Finali"
                    :color="color"
                    variant="outlined"
                    control-variant="split"
                    :readonly="readonly"
                ></v-number-input>
            </v-col>
            <v-col
				cols="2"
				class="py-0"
			>
                <v-number-input
                    v-model="form.interasse_cm"
                    label="Interasse cm"
                    :color="color"
                    variant="outlined"
                    control-variant="split"
                    :readonly="readonly"
                ></v-number-input>
            </v-col>
            <v-col
				cols="2"
				class="py-0"
			>
                <v-number-input
                    v-model="form.largh_bracciolo_cm"
                    label="Larghezza Bracciolo cm"
                    :color="color"
                    variant="outlined"
                    control-variant="split"
                    :readonly="readonly"
                ></v-number-input>
            </v-col>
            <v-col
				cols="2"
				class="py-0"
			>
                <v-switch
                    v-model="form.ricamo_logo"
                    label="Ricamo Logo"
                    :color="color"
                    :readonly="readonly"
                />
            </v-col>
            <v-col
				cols="2"
				class="py-0"
			>
                <v-switch
                    v-model="form.pendenza"
                    label="Pendenza"
                    :color="color"
                    :readonly="readonly"
                />
            </v-col>
            <v-col
				cols="2"
				class="py-0"
			>
                <v-switch
                    v-model="form.fissaggio_pavimento"
                    label="Fissaggio a Pavimento"
                    :color="color"
                    :readonly="readonly"
                />
            </v-col>
            <v-col
				cols="2"
				class="py-0"
			>
                <v-switch
                    v-model="form.montaggio"
                    label="Montaggio"
                    :color="color"
                    :readonly="readonly"
                />
            </v-col>
		</v-row>
	</v-card>
</template>

<script>
export default {
	name: 'DocumentsDettagli',
	props: {
		color: {
			type: String,
			required: true
		},
		errors: {
			type: Object,
			default: () => ({})
		},
		readonly: {
			type: Boolean,
			default: false
		}
	},
	inject: ['crud'],
	emits: ['ready'],
	mounted() {
		this.$emit('ready');
	},
	data() {
		return this.initialState();
	},
	methods: {
		initialState() {
			return {
				form: {
                    mod_poltrona: null,
                    quantita: null,
                    fianchi_finali: null,
                    interasse_cm: null,
                    largh_bracciolo_cm: null,
                    rivestimento: null,
                    ricamo_logo: false,
                    pendenza: false,
                    fissaggio_pavimento: false,
                    montaggio: false,
                    data_evasione: null
				}
			}
		},
        formatDate() {
			let data_evasione = null;
			if(this.form.data_evasione !== null) {
				const day = `0${this.form.data_evasione.getDate()}`.slice(-2);
				const month = `0${this.form.data_evasione.getMonth() + 1}`.slice(-2);
				const year = this.form.data_evasione.getFullYear();
				data_evasione = `${year}-${month}-${day}`;
			}
			return data_evasione;
		},
        showDate() {
			const date = new Date(this.form.data_evasione);

			const day = String(date.getDate()).padStart(2, '0');
			const month = String(date.getMonth() + 1).padStart(2, '0');
			const year = date.getFullYear();

			return `${day}/${month}/${year}`;
		},
        getForm() {
            const data_evasione = this.formatDate();
            let form_data = {
                dettagli: { ...this.form }
            };
            form_data.dettagli.data_evasione = data_evasione;
            return form_data;
        }
	}
}
</script>
