<template>
    <AuthenticatedLayout>
        <Snackbar :timeout="snackbar.timeout" :text="snackbar.text" :type="snackbar.type"
                  :show="snackbar.status" @closeSnackbar="snackbar.status = false"></Snackbar>

        <v-container>
            <div class="d-flex flex-column align-end mb-8">
                <h2 class="align-self-start">Gestionar tipos de compromisos</h2>
                <div>
                    <v-btn
                        color="primario"
                        class="grey--text text--lighten-4"
                        @click="setTrainingDialogToCreateOrEdit('create')"
                    >
                        Crear nuevo compromiso
                    </v-btn>
                </div>
            </div>

            <!--Inicia tabla-->
            <v-card>
                <v-card-title>
                    <v-text-field
                        v-model="search"
                        append-icon="mdi-magnify"
                        label="Filtrar por nombre de compromiso"
                        single-line
                        hide-details
                    ></v-text-field>
                </v-card-title>
                <v-data-table
                    :search="search"
                    loading-text="Cargando, por favor espere..."
                    :loading="isLoading"
                    :headers="headers"
                    :items="trainings"
                    :items-per-page="20"
                    :footer-props="{
                        'items-per-page-options': [20,50,100,-1]
                    }"
                    class="elevation-1"
                >
                    <template v-slot:item.actions="{ item }">
                        <v-icon
                            class="mr-2 primario--text"
                            @click="setTrainingDialogToCreateOrEdit('edit',item)"
                        >
                            mdi-pencil
                        </v-icon>

                        <v-icon
                            class="primario--text"
                            @click="confirmDeleteTraining(item)"
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
                        <span class="text-h5">Crear/Editar tipo de compromiso</span>
                    </v-card-title>
                    <v-card-text>
                        <v-container>
                            <v-row>
                                <v-col cols="12">
                                    <v-text-field
                                        label="Nombre del tipo de compromiso"
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

            <!--Confirmar borrar tipo de compromiso-->
            <confirm-dialog
                :show="deleteTrainingDialog"
                @canceled-dialog="deleteTrainingDialog = false"
                @confirmed-dialog="deleteTraining(deletedTrainingId)"
            >
                <template v-slot:title>
                    Estás a punto de eliminar el tipo de compromiso seleccionado
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
import Training from "@/models/Training";
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
                {text: 'Acciones', value: 'actions', sortable: false},
            ],
            trainings: [],
            //AssessmentPeriods models
            newTraining: new Training(),
            editedTraining: new Training(),
            deletedTrainingId: 0,
            //Snackbars
            snackbar: {
                text: "",
                type: 'alert',
                status: false,
                timeout: 2000,
            },
            //Dialogs
            deleteTrainingDialog: false,
            createOrEditDialog: {
                model: 'newTraining',
                method: 'createTraining',
                dialogStatus: false,
            },
            isLoading: true,
        }
    },
    async created() {
        await this.getAllTrainings();
        this.isLoading = false;
    },

    methods: {

        handleSelectedMethod: function () {
            this[this.createOrEditDialog.method]();
        },

        editTraining: async function () {
            //Verify request
            if (this.editedTraining.hasEmptyProperties()) {
                showSnackbar(this.snackbar, 'Debes diligenciar todos los campos obligatorios', 'red', 2000);
                return;
            }
            //Recollect information
            let data = this.editedTraining.toObjectRequest();

            try {
                let request = await axios.patch(route('api.trainings.update', {'training': this.editedTraining.id}), data);
                this.createOrEditDialog.dialogStatus = false;
                showSnackbar(this.snackbar, request.data.message, 'success');
                await this.getAllTrainings();
                //Clear role information
                this.editedTraining = new Training();
            } catch (e) {
                showSnackbar(this.snackbar, prepareErrorText(e), 'alert');
            }
        },

        confirmDeleteTraining: function (training) {
            this.deletedTrainingId = training.id;
            this.deleteTrainingDialog = true;
        },

        deleteTraining: async function (training) {
            try {
                let request = await axios.delete(route('api.trainings.destroy', {training: training}));
                this.deleteTrainingDialog = false;
                showSnackbar(this.snackbar, request.data.message, 'success');
                await this.getAllTrainings();
            } catch (e) {
                showSnackbar(this.snackbar, e.response.data.message, 'red', 7000);
            }

        },
        getAllTrainings: async function () {
            let request = await axios.get(route('api.trainings.index'));
            console.log(request.data);
            this.trainings = request.data;
        },
        setTrainingDialogToCreateOrEdit(which, item = null) {
            if (which === 'create') {
                this.createOrEditDialog.method = 'createTraining';
                this.createOrEditDialog.model = 'newTraining';
                this.createOrEditDialog.dialogStatus = true;
            }

            if (which === 'edit') {
                this.editedTraining = Training.fromModel(item);
                this.createOrEditDialog.method = 'editTraining';
                this.createOrEditDialog.model = 'editedTraining';
                this.createOrEditDialog.dialogStatus = true;
            }

        },
        createTraining: async function () {

            if (this.newTraining.hasEmptyProperties()) {
                showSnackbar(this.snackbar, 'Debes diligenciar todos los campos obligatorios', 'red', 2000);
                return;
            }

            let data = this.newTraining.toObjectRequest();
            //Clear competence information
            this.newTraining = new Training();

            try {
                let request = await axios.post(route('api.trainings.store'), data);
                this.createOrEditDialog.dialogStatus = false;
                showSnackbar(this.snackbar, request.data.message, 'success', 2000);
                await this.getAllTrainings();
            } catch (e) {
                showSnackbar(this.snackbar, e.response.data.message, 'alert', 3000);
            }
        },


    },
}
</script>
