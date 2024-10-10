<template>
    <AuthenticatedLayout>
        <Snackbar :timeout="snackbar.timeout" :text="snackbar.text" :type="snackbar.type"
                  :show="snackbar.status" @closeSnackbar="snackbar.status = false"></Snackbar>

        <v-container>
            <div class="d-flex flex-column align-end mb-8">
                <h2 class="align-self-start">Gestionar competencias</h2>
                <div>
                    <v-btn
                        :disabled="competences.length >= 6"
                        color="primario"
                        class="grey--text text--lighten-4"
                        @click="setCompetenceDialogToCreateOrEdit('create')"
                    >
                        Crear nueva competencia
                    </v-btn>
                </div>
            </div>

            <!--Inicia tabla-->
            <v-card>
                <v-card-title>
                    <v-text-field
                        v-model="search"
                        append-icon="mdi-magnify"
                        label="Filtrar por nombre"
                        single-line
                        hide-details
                    ></v-text-field>
                </v-card-title>
                <v-data-table
                    :search="search"
                    loading-text="Cargando, por favor espere..."
                    :loading="isLoading"
                    :headers="headers"
                    :items="competences"
                    :items-per-page="20"
                    :footer-props="{
                        'items-per-page-options': [20,50,100,-1]
                    }"
                    class="elevation-1"
                >
                    <template v-slot:item.actions="{ item }">
                        <v-icon
                            class="mr-2 primario--text"
                            @click="setCompetenceDialogToCreateOrEdit('edit',item)"
                        >
                            mdi-pencil
                        </v-icon>

                        <v-icon
                            class="primario--text"
                            @click="editOrder(item)"
                        >
                            mdi-format-list-numbered
                        </v-icon>

                        <v-icon
                            class="primario--text"
                            @click="confirmDeleteCompetence(item)"
                        >
                            mdi-delete
                        </v-icon>
                    </template>
                </v-data-table>
            </v-card>
            <!--Acaba tabla-->

            <!------------Seccion de dialogos ---------->

            <!--Crear o editar posición -->
            <v-dialog
                v-model="createOrEditDialog.dialogStatus"
                persistent
                max-width="650px"
            >
                <v-card>
                    <v-card-title>
                        <span>
                        </span>
                        <span class="text-h5">Crear/Editar competencia</span>
                    </v-card-title>
                    <v-card-text>
                        <v-container>
                            <v-row>
                                <v-col cols="12">
                                    <v-text-field
                                        label="Nombre de la competencia"
                                        required
                                        v-model="$data[createOrEditDialog.model].name"
                                    ></v-text-field>
                                </v-col>
                            </v-row>
                        </v-container>
                        <small>Los campos con * son obligatorios</small>
                    </v-card-text>
                    <v-card-actions>
                        <v-spacer></v-spacer>
                        <v-btn
                            color="primario"
                            text
                            @click="createOrEditDialog.dialogStatus = false"
                        >
                            Cancelar
                        </v-btn>
                        <v-btn
                            color="primario"
                            text
                            @click="handleSelectedMethod"
                        >
                            Guardar cambios
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <v-dialog
                v-model="editCompetenceOrderDialog"
                persistent
                max-width="450px"
            >
                <v-card>
                    <v-card-title>
                        <span>
                        </span>
                        <span
                            class="text-h5"> Intercambio de posición de la competencia {{this.currentCompetenceToOrder.name}}</span>
                    </v-card-title>
                    <v-card-text>
                        <v-container>
                            <v-row>
                                <v-col cols="10">
                                    <v-text-field
                                        color="primario"
                                        required
                                        v-model="newCompetenceOrder"
                                        label="Define la nueva posición de la competencia"
                                        type="number"
                                        min="1"
                                        :max="competences.length"
                                        class="mt-2"
                                    ></v-text-field>
                                </v-col>
                            </v-row>
                        </v-container>
                    </v-card-text>
                    <v-card-actions>
                        <v-spacer></v-spacer>
                        <v-btn
                            color="primario"
                            text
                            @click="editCompetenceOrderDialog= false"
                        >
                            Cancelar
                        </v-btn>
                        <v-btn
                            color="primario"
                            text
                            @click="updateCompetenceOrder"
                        >
                            Actualizar Orden
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>


            <!--Confirmar borrar assessmentPeriod-->
            <confirm-dialog
                :show="deleteCompetenceDialog"
                @canceled-dialog="deleteCompetenceDialog = false"
                @confirmed-dialog="deleteCompetence(deletedCompetenceId)"
            >
                <template v-slot:title>
                    Estás a punto de eliminar la posición seleccionada
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
import AssessmentPeriod from "@/models/AssessmentPeriod";
import Snackbar from "@/Components/Snackbar";
import Position from "@/models/Position";
import Competence from "@/models/Competence";

export default {
    components: {
        ConfirmDialog,
        AuthenticatedLayout,
        InertiaLink,
        Snackbar,
    },
    data: () => {
        return {
            //Table info
            search: '',
            headers: [
                {text: 'Nombre', value: 'name'},
                {text: 'Competencia', value: 'position'},
                {text: 'Acciones', value: 'actions', sortable: false},
            ],
            competences: [],
            //AssessmentPeriods models
            newCompetence: new Competence(),
            editedCompetence: new Competence(),
            deletedCompetenceId: 0,
            currentCompetenceToOrder: '',
            newCompetenceOrder: null,
            //Snackbars
            snackbar: {
                text: "",
                type: 'alert',
                status: false,
                timeout: 2000,
            },
            //Dialogs
            editCompetenceOrderDialog: false,
            deleteCompetenceDialog: false,
            createOrEditDialog: {
                model: 'newCompetence',
                method: 'createCompetence',
                dialogStatus: false,
            },
            isLoading: true,
        }
    },
    async created() {
        await this.getAllCompetences();
        this.isLoading = false;
    },

    methods: {

        handleSelectedMethod: function () {
            this[this.createOrEditDialog.method]();
        },

        editOrder(competence){
            this.editCompetenceOrderDialog = true;
            this.currentCompetenceToOrder = competence;
        },

        editCompetence: async function () {
            //Verify request
            if (this.editedCompetence.hasEmptyProperties()) {
                showSnackbar(this.snackbar, 'Debes diligenciar todos los campos obligatorios', 'red', 2000);
                return;
            }
            //Recollect information
            let data = this.editedCompetence.toObjectRequest();

            try {
                let request = await axios.patch(route('api.competences.update', {'competence': this.editedCompetence.id}), data);
                this.createOrEditDialog.dialogStatus = false;
                showSnackbar(this.snackbar, request.data.message, 'success');
                await this.getAllCompetences();
                //Clear role information
                this.editedCompetence = new Competence();
            } catch (e) {
                showSnackbar(this.snackbar, prepareErrorText(e), 'alert');
            }
        },

        confirmDeleteCompetence: function (competence) {
            this.deletedCompetenceId = competence.id;
            this.deleteCompetenceDialog = true;
        },

        deleteCompetence: async function (competence) {
            try {
                let request = await axios.delete(route('api.competences.destroy', {competence: competence}));
                this.deleteCompetenceDialog = false;
                showSnackbar(this.snackbar, request.data.message, 'success');
                await this.getAllCompetences();
            } catch (e) {
                showSnackbar(this.snackbar, e.response.data.message, 'red', 3000);
            }

        },
        getAllCompetences: async function () {
            let request = await axios.get(route('api.competences.index'));
            console.log(request.data);
            this.competences = request.data;
        },
        setCompetenceDialogToCreateOrEdit(which, item = null) {
            if (which === 'create') {
                this.createOrEditDialog.method = 'createCompetence';
                this.createOrEditDialog.model = 'newCompetence';
                this.createOrEditDialog.dialogStatus = true;
            }

            if (which === 'edit') {
                this.editedCompetence = Competence.fromModel(item);
                this.createOrEditDialog.method = 'editCompetence';
                this.createOrEditDialog.model = 'editedCompetence';
                this.createOrEditDialog.dialogStatus = true;
            }

        },
        createCompetence: async function () {

            this.newCompetence.position = this.competences.length + 1;

            if (this.newCompetence.hasEmptyProperties()) {
                showSnackbar(this.snackbar, 'Debes diligenciar todos los campos obligatorios', 'red', 2000);
                return;
            }

            let data = this.newCompetence.toObjectRequest();
            //Clear competence information
            this.newCompetence = new Competence();

            try {
                let request = await axios.post(route('api.competences.store'), data);
                this.createOrEditDialog.dialogStatus = false;
                showSnackbar(this.snackbar, request.data.message, 'success', 2000);
                await this.getAllCompetences();
            } catch (e) {
                showSnackbar(this.snackbar, e.response.data.message, 'alert', 3000);
            }
        },

        async updateCompetenceOrder(){

            let data={
                position: this.currentCompetenceToOrder,
                oldPosition : this.currentCompetenceToOrder.position,
                newPosition : this.newCompetenceOrder
            };

            try {
                let request = await axios.post(route('competences.updateOrder'), {data});
                this.createOrEditDialog.dialogStatus = false;
                showSnackbar(this.snackbar, request.data.message, 'success');
                await this.getAllCompetences();
                this.editCompetenceOrderDialog = false;
                //Clear role information
                this.editedCompetence = new Competence();
            } catch (e) {
                showSnackbar(this.snackbar, prepareErrorText(e), 'alert');
            }
        }
    },
}
</script>
