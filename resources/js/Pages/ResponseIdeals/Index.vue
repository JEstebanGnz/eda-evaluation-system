<template>
    <AuthenticatedLayout>
        <Snackbar :timeout="snackbar.timeout" :text="snackbar.text" :type="snackbar.type"
                  :show="snackbar.status" @closeSnackbar="snackbar.status = false"></Snackbar>

        <v-container>
            <div class="d-flex flex-column align-end mb-4">
                <h3 class="align-self-start">Definir ideales de respuesta por posición</h3>
                <div>
                    <v-btn
                        color="primario"
                        class="grey--text text--lighten-4 ml-4"
                        @click="setResponseIdealDialogToCreateOrEdit('create')"
                    >
                        Agregar nuevo ideal de respuesta
                    </v-btn>
                </div>
            </div>

            <!--Inicia tabla para mostrar todos los ideales de respuesta-->
            <v-card class="mt-5">
                <v-card-title>
                    <v-text-field
                        v-model="search"
                        append-icon="mdi-magnify"
                        label="Filtrar por nombre de posición"
                        single-line
                        hide-details
                    ></v-text-field>
                </v-card-title>
                <v-data-table
                    :search="search"
                    loading-text="Cargando, por favor espere..."
                    :loading="isLoading"
                    :headers="headersIndex"
                    :items="responseIdeals"
                    :items-per-page="20"
                    :footer-props="{
                        'items-per-page-options': [20,50,100,-1]
                    }"
                    class="elevation-1"
                >
                    <template
                        v-for="(competence,index) in competences"
                        v-slot:[`item.c${index+1}`]="{ item }"
                    >
                        {{ item.response[index].value }}
                    </template>

                    <template v-slot:item.actions="{ item }">
                        <v-tooltip top>
                            <template v-slot:activator="{on,attrs}">
                                <v-icon
                                    v-bind="attrs"
                                    v-on="on"
                                    class="mr-2 primario--text"
                                    @click="setResponseIdealDialogToCreateOrEdit('edit', item)"
                                >
                                    mdi-pencil
                                </v-icon>
                            </template>
                            <span>Editar ideal</span>
                        </v-tooltip>

                        <v-tooltip top>
                            <template v-slot:activator="{on,attrs}">
                                <v-icon
                                    v-bind="attrs"
                                    v-on="on"
                                    class="mr-2 primario--text"
                                    @click="confirmDeleteResponseIdeal(item)"
                                >
                                    mdi-delete
                                </v-icon>
                            </template>
                            <span>Borrar ideal</span>
                        </v-tooltip>
                    </template>
                </v-data-table>
            </v-card>


        <!--Modal para crear/editar nuevos ideales de respuesta-->
        <v-dialog
            v-model="createOrEditDialog.dialogStatus"
            persistent
            max-width="60%"
        >
            <v-card class="py-4 px-4">
                <v-card-title>
                    <h3 style="margin: auto"> Crear/editar nuevos ideales de respuesta </h3>
                </v-card-title>
                <v-row style="max-width: 80%; margin: auto" >
                    <v-col cols="12">
                        <v-select
                            color="primario"
                            v-model="$data[createOrEditDialog.model].position_id"
                            :items="positions"
                            label="Selecciona la posición"
                            :item-value="(position)=>position.id"
                            :item-text="(position)=>position.name"
                        ></v-select>
                    </v-col>
                </v-row>

               <v-data-table
                    loading-text="Cargando, por favor espere..."
                    :headers="headersCreateOrEdit"
                    :items="$data[createOrEditDialog.model].response"
                    :hide-default-footer="true"
                    style="margin: auto; max-width: 70%"
                >
                    <template v-slot:item.value="{ item }">
                        <v-tooltip top>
                            <template v-slot:activator="{on,attrs}">
                                <v-text-field
                                    label="Valor de la competencia"
                                    v-model="$data[createOrEditDialog.model].response[item.id].value"
                                    single-line
                                    style="text-align: center"
                                    type="number"
                                    min=1
                                    max="5"
                                    :rules="numberRules"
                                    step="0.1"
                                >
                                </v-text-field>
                            </template>
                        </v-tooltip>
                    </template>
                </v-data-table>

                <v-card-actions class="mt-5">
                    <v-btn
                        color="primario"
                        class="white--text"
                        @click="createOrEditDialog.dialogStatus = false"
                    >
                        Salir
                    </v-btn>

                    <v-btn
                        color="primario"
                        class="grey--text text--lighten-4"
                        @click="handleSelectedMethod"
                    >
                        Guardar Cambios
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!--Confirmar borrar position-->
        <confirm-dialog
            :show="deleteResponseIdealDialog"
            @canceled-dialog="deleteResponseIdealDialog = false"
            @confirmed-dialog="deleteResponseIdeal(deletedResponseIdealId)"
        >
            <template v-slot:title>
                Estás a punto de eliminar el ideal de respuesta seleccionado
            </template>

            ¡Cuidado! esta acción es irreversible

            <template v-slot:confirm-button-text>
                Borrar
            </template>
        </confirm-dialog>
        </v-container>
    </AuthenticatedLayout>
</template>

<script>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import {InertiaLink} from "@inertiajs/inertia-vue";
import {prepareErrorText, showSnackbar} from "@/HelperFunctions"
import ConfirmDialog from "@/Components/ConfirmDialog";
// import Chart from "chart.js/auto";
import Snackbar from "@/Components/Snackbar";
import ResponseIdeal from "@/models/ResponseIdeal";
import Position from "@/models/Position";

export default {
    components: {
        ConfirmDialog,
        AuthenticatedLayout,
        InertiaLink,
        Snackbar
    },

    data: () => {
        return {
            //Table info
            headersIndex:[],
            headersCreateOrEdit: [
                {text: 'Competencia', value: 'name', sortable: false},
                {text: 'Valor', value: 'value', margin:'auto', sortable: false},
            ],
            search: '',
            positions:[],
            responseIdeals:[],
            competences: [],
            newResponseIdeal: new ResponseIdeal (),
            editedResponseIdeal: new ResponseIdeal (),
            deletedResponseIdealId: '',
            //Snackbars
            snackbar: {
                text: "",
                color: 'red',
                status: false,
                timeout: 2000,
            },
            //Dialogs
            deleteResponseIdealDialog: false,
            createOrEditDialog: {
                model: 'newResponseIdeal',
                method: 'createResponseIdeal',
                dialogStatus: false,
            },
            //Overlays
            isLoading: true,
            numberRules: [
                v => !!v || 'El valor introducido debe ser un número mayor que 0 y menor que 5',
            ]
        }
    },
    async created() {
        await this.getCompetences();
        await this.getSuitablePositions();
        await this.getResponseIdeals()
        this.isLoading = false;
    },
    methods: {

        async getResponseIdeals() {
            let url = route('api.responseIdeals.index');
            let request = await axios.get(url);
            this.responseIdeals = request.data;
        },

        handleSelectedMethod: function () {
            this[this.createOrEditDialog.method]();
        },

        async getCompetences(){
            let request = await axios.get(route('api.competences.index'));
            console.log(request.data);
            this.competences = request.data;
            this.headersIndex[0] = {text:"Posición", value:"name", width: "15%"}
            this.competences.forEach(competence =>{
                this.headersIndex.push({text:competence.name, value:`c${competence.position}`, sortable:false})
            });
            this.headersIndex.push({text:"Acciones", value:'actions', sortable:false})
        },

        getSuitablePositions: async function () {
            let request = await axios.get(route('api.positions.index'));
            this.positions = request.data;
        },

        async createResponseIdeal(){

            if (this.newResponseIdeal.hasEmptyProperties()) {
                showSnackbar(this.snackbar, 'Debes diligenciar todos los campos obligatorios', 'red', 2000);
                return;
            }
            let data = this.newResponseIdeal.toObjectRequest();

            try {
                let request = await axios.post(route('api.responseIdeals.store'), data);
                this.createOrEditDialog.dialogStatus = false;
                //Clear role information
                // this.newResponseIdeal = new ResponseIdeal();
                showSnackbar(this.snackbar, request.data.message, 'success', 2000);
                await this.getResponseIdeals();
                await this.getSuitablePositions();

            } catch (e) {
                showSnackbar(this.snackbar, e.response.data.message, 'alert', 3000);
            }
        },

        async editResponseIdeal(){
            if (this.editedResponseIdeal.hasEmptyProperties()) {
                showSnackbar(this.snackbar, 'Debes diligenciar todos los campos obligatorios', 'red', 2000);
                return;
            }
            //Recollect information
            let data = this.editedResponseIdeal.toObjectRequest();

            console.log(data);

            try {
                let request = await axios.patch(route('api.responseIdeals.update', {'responseIdeal': this.editedResponseIdeal.id}), data);
                this.createOrEditDialog.dialogStatus = false;
                showSnackbar(this.snackbar, request.data.message, 'success');
                await this.getResponseIdeals();
                await this.getSuitablePositions();

            } catch (e) {
                showSnackbar(this.snackbar, prepareErrorText(e), 'alert');
            }
        },

        confirmDeleteResponseIdeal(responseIdeal){
            this.deletedResponseIdealId = responseIdeal.id;
            this.deleteResponseIdealDialog = true;
        },

        async deleteResponseIdeal(responseIdeal){
            try {
                let request = await axios.delete(route('api.responseIdeals.destroy', {responseIdeal: responseIdeal}));
                this.deleteResponseIdealDialog = false;
                showSnackbar(this.snackbar, request.data.message, 'success');
                await this.getResponseIdeals();
                await this.getSuitablePositions();
            } catch (e) {
                showSnackbar(this.snackbar, e.response.data.message, 'red', 3000);
            }

        },

        setResponseIdealDialogToCreateOrEdit(which, item = null) {
            if (which === 'create') {
                this.createOrEditDialog.method = 'createResponseIdeal';
                this.createOrEditDialog.model = 'newResponseIdeal';
                this.newResponseIdeal.response = this.newResponseIdeal.createInitialResponseFromCompetencesArray(this.competences)
                console.log(this.newResponseIdeal);
                this.createOrEditDialog.dialogStatus = true;
            }

            if (which === 'edit') {
                this.editedResponseIdeal = ResponseIdeal.fromModel(item);
                this.createOrEditDialog.method = 'editResponseIdeal';
                this.createOrEditDialog.model = 'editedResponseIdeal';
                this.createOrEditDialog.dialogStatus = true;
            }

        },

    },
}
</script>
