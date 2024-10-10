<template>
    <AuthenticatedLayout>
        <Snackbar :timeout="snackbar.timeout" :text="snackbar.text" :type="snackbar.type"
                  :show="snackbar.status" @closeSnackbar="snackbar.status = false"></Snackbar>

        <v-container>
            <div class="d-flex flex-column align-end mb-8">
                <h2 class="align-self-start">Gestionar periodos de evaluación</h2>
                <div>
                    <v-btn
                        color="primario"
                        class="grey--text text--lighten-4"
                        @click="setAssessmentPeriodDialogToCreateOrEdit('create')"
                    >
                        Crear nuevo periodo
                    </v-btn>
                </div>
            </div>

            <!--Inicia tabla-->

            <v-card>
                <v-card-title>
                    <v-text-field
                        v-model="search"
                        append-icon="mdi-magnify"
                        label="Filtrar por nombre o fecha"
                        single-line
                        hide-details
                    ></v-text-field>
                </v-card-title>
                <v-data-table
                    :search="search"
                    loading-text="Cargando, por favor espere..."
                    :loading="isLoading"
                    :headers="headers"
                    :items="assessmentPeriods"
                    :items-per-page="20"
                    :footer-props="{
                        'items-per-page-options': [20,50,100,-1]
                    }"
                    class="elevation-1"
                    :item-class="getRowColor"
                >
                    <template v-slot:item.actions="{ item }">
                        <v-icon
                            class="mr-2 primario--text"
                            @click="setAssessmentPeriodDialogToCreateOrEdit('edit',item)"
                        >
                            mdi-pencil
                        </v-icon>
                        <v-icon
                            class="primario--text"
                            @click="confirmDeleteAssessmentPeriod(item)"
                        >
                            mdi-delete
                        </v-icon>
                        <v-icon v-if="!(item.active)"
                                class="mr-2 primario--text"
                                @click="setAssessmentPeriodAsActive(item.id)"
                        >
                            mdi-cursor-default-click
                        </v-icon>
                    </template>
                </v-data-table>
            </v-card>
            <!--Acaba tabla-->

            <!------------Seccion de dialogos ---------->

            <!--Crear o editar assessmentPeriod -->
            <v-dialog
                v-model="createOrEditDialog.dialogStatus"
                persistent
                max-width="650px"
            >
                <v-card>
                    <v-card-title>
                        <span>
                        </span>
                        <span class="text-h5">Crear un nuevo periodo académico</span>
                    </v-card-title>
                    <v-card-text>
                        <v-container>
                            <v-row>
                                <v-col cols="12">
                                    <v-text-field
                                        label="Nombre del periodo de evaluación"
                                        required
                                        v-model="$data[createOrEditDialog.model].name"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12" :md="6" class="d-flex flex-column">
                                    <span class="subtitle-1">
                                        Fecha de inicio evaluación
                                    </span>
                                    <v-date-picker v-model="$data[createOrEditDialog.model].assessmentStartDate" full-width>
                                    </v-date-picker>
                                </v-col>
                                <v-col cols="12" :md="6" class="d-flex flex-column">
                                    <span class="subtitle-1">
                                        Fecha de finalización evaluación
                                    </span>
                                    <v-date-picker v-model="$data[createOrEditDialog.model].assessmentEndDate" full-width>
                                    </v-date-picker>
                                </v-col>

                                <v-col cols="12" :md="6" class="d-flex flex-column">
                                    <span class="subtitle-1">
                                        Fecha de inicio compromisos
                                    </span>
                                    <v-date-picker v-model="$data[createOrEditDialog.model].commitmentStartDate" full-width>
                                    </v-date-picker>
                                </v-col>
                                <v-col cols="12" :md="6" class="d-flex flex-column">
                                    <span class="subtitle-1">
                                        Fecha de finalización compromisos
                                    </span>
                                    <v-date-picker v-model="$data[createOrEditDialog.model].commitmentEndDate" full-width>
                                    </v-date-picker>
                                </v-col>
                                <v-col cols="12" :md="12" class="d-flex flex-column">
                                <v-btn
                                    v-if="$data[createOrEditDialog.model].id !== null && $data[createOrEditDialog.model].active === 0"
                                    color="primario"
                                    class="grey--text text--lighten-4"
                                    @click="migrateActiveAssessmentPeriodInfo($data[createOrEditDialog.model])"
                                >
                                    Migrar información periodo activo
                                </v-btn>
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

            <!--Confirmar borrar assessmentPeriod-->
            <confirm-dialog
                :show="deleteAssessmentPeriodDialog"
                @canceled-dialog="deleteAssessmentPeriodDialog = false"
                @confirmed-dialog="deleteAssessmentPeriod(deletedAssessmentPeriodId)"
            >
                <template v-slot:title>
                    Estás a punto de eliminar el periodo de evaluación seleccionado
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
                {text: 'Nombre del periodo', value: 'name'},
                {text: 'Fecha de inicio evaluación', value: 'assessment_start_date'},
                {text: 'Fecha de fin evaluación', value: 'assessment_end_date'},
                {text: 'Fecha de inicio compromisos', value: 'commitment_start_date'},
                {text: 'Fecha de fin compromisos', value: 'commitment_end_date'},
                {text: 'Acciones', value: 'actions', sortable: false},
            ],
            assessmentPeriods: [],
            //AssessmentPeriods models
            newAssessmentPeriod: new AssessmentPeriod(),
            editedAssessmentPeriod: new AssessmentPeriod(),
            deletedAssessmentPeriodId: 0,
            //Snackbars
            snackbar: {
                text: "",
                type: 'alert',
                status: false,
                timeout: 2000,
            },
            //Dialogs
            deleteAssessmentPeriodDialog: false,
            createOrEditDialog: {
                model: 'newAssessmentPeriod',
                method: 'createAssessmentPeriod',
                dialogStatus: false,
            },
            isLoading: true,
        }
    },
    async created() {
        await this.getAllAssessmentPeriods();
        this.isLoading = false;
    },

    methods: {

        migrateActiveAssessmentPeriodInfo: async function(item){
            try{
                let request = await axios.post(route('api.assessmentPeriods.migrateActive', {assessmentPeriod: item}));
                showSnackbar(this.snackbar, request.data.message, 'success');
            } catch (e) {
                showSnackbar(this.snackbar, prepareErrorText(e), 'alert');
            }
        },

        setAssessmentPeriodAsActive: async function (assessmentPeriodId) {
            try {
                let request = await axios.post(route('api.assessmentPeriods.setActive', {'assessmentPeriod': assessmentPeriodId}));
                this.createOrEditDialog.dialogStatus = false;
                showSnackbar(this.snackbar, request.data.message, 'success');
                this.getAllAssessmentPeriods();
            } catch (e) {
                showSnackbar(this.snackbar, prepareErrorText(e), 'alert');
            }
        },
        getRowColor: function (item) {
            return item.active ? 'green lighten-5' : '';
        },
        handleSelectedMethod: function () {
            this[this.createOrEditDialog.method]();
        },
        editAssessmentPeriod: async function () {
            //Verify request
            if (this.editedAssessmentPeriod.hasEmptyProperties()) {
                showSnackbar(this.snackbar, 'Debes diligenciar todos los campos obligatorios', 'red', 2000);
                return;
            }
            //Recollect information
            let data = this.editedAssessmentPeriod.toObjectRequest();

            try {
                let request = await axios.patch(route('api.assessmentPeriods.update', {'assessmentPeriod': this.editedAssessmentPeriod.id}), data);
                this.createOrEditDialog.dialogStatus = false;
                showSnackbar(this.snackbar, request.data.message, 'success');
                this.getAllAssessmentPeriods();
                //Clear role information
                this.editedAssessmentPeriod = new AssessmentPeriod();
            } catch (e) {
                showSnackbar(this.snackbar, prepareErrorText(e), 'alert');
            }
        },

        confirmDeleteAssessmentPeriod: function (assessmentPeriod) {
            this.deletedAssessmentPeriodId = assessmentPeriod.id;
            this.deleteAssessmentPeriodDialog = true;
        },
        deleteAssessmentPeriod: async function (assessmentPeriodId) {
            try {
                let request = await axios.delete(route('api.assessmentPeriods.destroy', {assessmentPeriod: assessmentPeriodId}));
                this.deleteAssessmentPeriodDialog = false;
                showSnackbar(this.snackbar, request.data.message, 'success');
                this.getAllAssessmentPeriods();
            } catch (e) {
                showSnackbar(this.snackbar, e.response.data.message, 'red', 3000);
            }

        },
        getAllAssessmentPeriods: async function () {
            let request = await axios.get(route('api.assessmentPeriods.index'));
            this.assessmentPeriods = request.data;
            console.log(this.assessmentPeriods);
        },
        setAssessmentPeriodDialogToCreateOrEdit(which, item = null) {
            if (which === 'create') {
                this.createOrEditDialog.method = 'createAssessmentPeriod';
                this.createOrEditDialog.model = 'newAssessmentPeriod';
                this.createOrEditDialog.dialogStatus = true;
            }

            if (which === 'edit') {
                this.editedAssessmentPeriod = AssessmentPeriod.fromModel(item);
                this.createOrEditDialog.method = 'editAssessmentPeriod';
                this.createOrEditDialog.model = 'editedAssessmentPeriod';
                this.createOrEditDialog.dialogStatus = true;

                console.log(this.editedAssessmentPeriod);

            }

        },
        createAssessmentPeriod: async function () {
            if (this.newAssessmentPeriod.hasEmptyProperties()) {
                showSnackbar(this.snackbar, 'Debes diligenciar todos los campos obligatorios', 'red', 2000);
                return;
            }
            let data = this.newAssessmentPeriod.toObjectRequest();

            //Clear role information
            this.newAssessmentPeriod = new AssessmentPeriod();

            try {
                let request = await axios.post(route('api.assessmentPeriods.store'), data);
                this.createOrEditDialog.dialogStatus = false;
                showSnackbar(this.snackbar, request.data.message, 'success', 2000);
                this.getAllAssessmentPeriods();
            } catch (e) {
                showSnackbar(this.snackbar, e.response.data.message, 'alert', 3000);
            }
        }
    },
}
</script>
