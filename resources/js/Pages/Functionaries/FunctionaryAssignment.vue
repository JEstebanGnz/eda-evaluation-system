<template>
    <AuthenticatedLayout>
        <Snackbar :timeout="snackbar.timeout" :text="snackbar.text" :type="snackbar.type"
                  :show="snackbar.status" @closeSnackbar="snackbar.status = false"></Snackbar>

        <v-container>
            <div class="d-flex flex-column align-end mb-8">
                <h2 class="align-self-start" > {{this.functionary.name}} </h2>

            <div class="d-flex flex-column align-end mb-8">
                <div>
                    <v-btn
                        color="primario"
                        class="grey--text text--lighten-4"
                        @click="setAssessmentDialogToCreateOrEdit('create')"
                    >
                        Crear nueva asignación
                    </v-btn>
                </div>
            </div>
            </div>

            <h3 class="mt-9"> Evaluaciones del funcionario</h3>
            <!--Tabla funcionarios de la dependencia-->
            <v-card class="mt-4">
                <v-data-table
                    loading-text="Cargando, por favor espere..."
                    :headers="headers"
                    :items="assessments"
                    :items-per-page="20"
                    :footer-props="{
                        'items-per-page-options': [10,20,30,-1]
                    }"
                    class="elevation-1"
                >
                    <template v-slot:item.done="{ item }">
                        <v-icon
                            color="red"
                            class="primario--text align-center"
                            v-if="item.pending === 1"
                        >
                            mdi-close-thick
                        </v-icon>
                        <v-icon
                            color="green"
                            class="primario--text align-center"
                            v-else>
                        >
                            mdi-check-bold
                        </v-icon>
                    </template>

                    <template v-slot:item.actions="{ item }">
                        <v-icon
                            class="mr-2 primario--text"
                            v-if="item.role !== 'autoevaluación'"
                            @click="setAssessmentDialogToCreateOrEdit('edit',item)"
                        >
                            mdi-pencil
                        </v-icon>
                        <v-icon
                            class="primario--text"
                            v-if="item.role !== 'autoevaluación'"
                            @click="confirmDeleteAssessment(item)"
                        >
                            mdi-delete
                        </v-icon>
                    </template>
                </v-data-table>
            </v-card>

            <!------------Seccion de dialogos ---------->
            <!--Crear o editar Asignación -->
            <v-dialog
                v-model="createOrEditDialog.dialogStatus"
                persistent
                max-width="650px"
            >
                <v-card>
                    <v-card-title>
                        <span>
                        </span>
                        <span class="text-h5">Crear/editar evaluación</span>
                    </v-card-title>
                    <v-card-text>
                        <v-container>
                            <v-row>
                                <v-col cols="6">
                                    <v-autocomplete
                                        color="primario"
                                        :items="roles"
                                        label="Rol"
                                        :item-text="(role)=> role.name"
                                        :item-value="(role)=>role.value"
                                        required
                                        v-model="$data[createOrEditDialog.model].role"
                                    ></v-autocomplete>
                                </v-col>
                                <v-col cols="6">
                                    <v-autocomplete
                                        v-if="$data[createOrEditDialog.model].role === 'jefe'
                                        || $data[createOrEditDialog.model].role === 'cliente interno'"
                                        label="Nombre del funcionario asignado para evaluar"
                                        :items="functionaries"
                                        required
                                        :item-text="(functionary)=> functionary.name"
                                        :item-value="(functionary)=>functionary.user_id"
                                        v-model="$data[createOrEditDialog.model].evaluator_id"
                                    ></v-autocomplete>
                                    <v-autocomplete
                                        v-if="$data[createOrEditDialog.model].role === 'par'"
                                        label="Nombre del funcionario asignado para evaluar"
                                        :items="functionariesAndExternalClients"
                                        required
                                        :item-text="(functionary)=> functionary.name"
                                        :item-value="(functionary)=>functionary.user_id"
                                        v-model="$data[createOrEditDialog.model].evaluator_id"
                                    ></v-autocomplete>
                                    <v-autocomplete
                                        v-if="$data[createOrEditDialog.model].role === 'cliente externo'"
                                        label="Nombre del cliente externo"
                                        :items="externalClients"
                                        required
                                        :item-text="(externalClient)=> externalClient.name"
                                        :item-value="(externalClient)=>externalClient.id"
                                        v-model="$data[createOrEditDialog.model].evaluator_id"
                                    ></v-autocomplete>
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

            <!--Confirmar borrar position-->
            <confirm-dialog
                :show="deleteAssessmentDialog"
                @canceled-dialog="deleteAssessmentDialog = false"
                @confirmed-dialog="deleteAssessment(deletedAssessmentId)"
            >
                <template v-slot:title>
                    Estás a punto de eliminar la asignación seleccionada
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
import ConfirmDialog from "@/Components/ConfirmDialog";
import Snackbar from "@/Components/Snackbar";
import {prepareErrorText, showSnackbar} from "@/HelperFunctions";
import Assessment from "@/models/Assessment";

export default {
    components: {
        ConfirmDialog,
        AuthenticatedLayout,
        InertiaLink,
        Snackbar,
    },
    props: {
        functionary: Object,
        dependency: Object
    },

    data: () => {
        return {
            //Table info
            assessments: [],
            functionaries: [],
            externalClients: [],
            functionariesAndExternalClients:[],
            roles:[],
            headers: [
                {text: 'Asignación', value: 'role'},
                {text: 'Nombre', value: 'name'},
                {text: 'Realizado', value: 'done', width: '10%', sortable: false},
                {text: 'Acciones', value: 'actions', width: '20%', sortable: false},
            ],
            //Snackbars
            snackbar: {
                text: "",
                type: 'alert',
                status: false,
                timeout: 2000,
            },
            isLoading: true,
            //Assessment models
            newAssessment: new Assessment(),
            editedAssessment: new Assessment(),
            deletedAssessmentId: 0,
            //Dialogs
            deleteAssessmentDialog: false,
            createOrEditDialog: {
                model: 'newAssessment',
                method: 'createAssessment',
                dialogStatus: false,
            },
        }
    },

    async created() {
        await this.getFunctionaryAssessments();
        await this.getPossibleEvaluators();
        await this.getExternalClients();
        this.mapFunctionariesAndExternalClientsArray();
        this.getRoles();
    },

    methods:{

        async getFunctionaryAssessments(){
            let request = await axios.get(route('api.assessments.index', {functionary: this.functionary}))
            console.log(request.data);
            console.log(this.functionary);
            this.assessments = request.data;
            console.log(this.assessments, 'assessments del funcionario');
        },

        async getExternalClients(){
            let request = await axios.get(route('api.externalClients.index'));
            this.externalClients = request.data;
            console.log(this.externalClients, 'External Clients');
        },

        mapFunctionariesAndExternalClientsArray(){
            this.functionariesAndExternalClients = this.functionaries.concat(this.externalClients);
            console.log(this.functionariesAndExternalClients, 'mixed array');

        },

        async getPossibleEvaluators() {
            let request = await axios.get(route('api.functionaries.index', {functionaryProfile: this.functionary}));
            this.functionaries = request.data;
            console.log(request.data, 'Functionaries');
        },

        getRoles(){
            this.roles = Assessment.getPossibleRoles();
        },

        capitalize($field){
            return $field.toLowerCase().split(' ').map((word) => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
        },

        handleSelectedMethod: function () {
            this[this.createOrEditDialog.method]();
        },

        editAssessment: async function () {
            //Verify request
            if (this.editedAssessment.hasEmptyProperties()) {
                showSnackbar(this.snackbar, 'Debes diligenciar todos los campos obligatorios', 'red', 2000);
                return;
            }
            //Recollect information
            let data = this.editedAssessment.toObjectRequest();
            console.log(data);

            try {
                let request = await axios.patch(route('api.assessments.update', {'assessment': this.editedAssessment.id}), data);
                this.createOrEditDialog.dialogStatus = false;
                showSnackbar(this.snackbar, request.data.message, 'success');
                await this.getFunctionaryAssessments();
                //Clear role information
                this.editedAssessment = new Assessment();
            } catch (e) {
                showSnackbar(this.snackbar, prepareErrorText(e), 'alert');
            }
        },

        confirmDeleteAssessment: function (assessment) {
            this.deletedAssessmentId = assessment.id;
            this.deleteAssessmentDialog = true;
        },

        deleteAssessment: async function (assessment) {
            try {
                let request = await axios.delete(route('api.assessments.destroy', {assessment: assessment}));
                this.deleteAssessmentDialog = false;
                showSnackbar(this.snackbar, request.data.message, 'success');
                await this.getFunctionaryAssessments();
            } catch (e) {
                showSnackbar(this.snackbar, e.response.data.message, 'red', 3000);
            }

        },

        setAssessmentDialogToCreateOrEdit(which, item = null) {
            if (which === 'create') {
                this.createOrEditDialog.method = 'createAssessment';
                this.createOrEditDialog.model = 'newAssessment';
                this.createOrEditDialog.dialogStatus = true;
            }

            if (which === 'edit') {
                this.editedAssessment = Assessment.fromModel(item);
                this.createOrEditDialog.method = 'editAssessment';
                this.createOrEditDialog.model = 'editedAssessment';
                console.log(this.editedAssessment);
                this.createOrEditDialog.dialogStatus = true;
            }

        },
        createAssessment: async function () {
            if (this.newAssessment.hasEmptyProperties()) {
                showSnackbar(this.snackbar, 'Debes diligenciar todos los campos obligatorios', 'red', 2000);
                return;
            }

            this.newAssessment.evaluated_id = this.functionary.user_id;
            this.newAssessment.dependency_identifier = this.dependency.identifier

            let data = this.newAssessment.toObjectRequest();
            //Clear role information
            this.newAssessment = new Assessment();
            try {
                let request = await axios.post(route('api.assessments.store'), data);
                this.createOrEditDialog.dialogStatus = false;
                showSnackbar(this.snackbar, request.data.message, 'success', 2000);
                await this.getFunctionaryAssessments();
            } catch (e) {
                showSnackbar(this.snackbar, e.response.data.message, 'alert', 3000);
            }
        },
    },
}
</script>

