<template>
    <AuthenticatedLayout>
        <Snackbar :timeout="snackbar.timeout" :text="snackbar.text" :type="snackbar.type"
                  :show="snackbar.status" @closeSnackbar="snackbar.status = false"></Snackbar>

        <v-container>
            <div class="d-flex flex-column align-end mb-8">
                <h2 class="align-self-start">Gestionar asignaciones</h2>
            </div>

            <!--Inicia tabla-->
            <v-card>
                <v-card-title>
                    <v-text-field
                        v-model="search"
                        append-icon="mdi-magnify"
                        label="Filtrar por nombre de cargo o posición"
                        single-line
                        hide-details
                    ></v-text-field>
                </v-card-title>
                <v-data-table
                    :search="search"
                    loading-text="Cargando, por favor espere..."
                    :loading="isLoading"
                    :headers="headers"
                    :items="assignments"
                    :items-per-page="20"
                    :footer-props="{
                        'items-per-page-options': [20,50,100,-1]
                    }"
                    class="elevation-1"
                >
                    <template v-slot:item.actions="{ item }">
                        <v-icon
                            class="mr-2 primario--text"
                            @click="setAssignmentDialogToCreateOrEdit('edit',item)"
                        >
                            mdi-pencil
                        </v-icon>
                        <v-icon
                            v-if="item.position_id !== null"
                            class="primario--text"
                            @click="confirmDeleteAssignment(item)"
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
                        <span class="text-h5">Asignar posición al cargo</span>
                    </v-card-title>
                    <v-card-text>
                        <v-container>
                            <v-row>
                                <v-col cols="12">
                                    <v-autocomplete
                                        label="Nombre de la posición"
                                        :items="positions"
                                        required
                                        v-model="editedAssignment.position_id"
                                        item-text="name"
                                        item-value="id"
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

            <!--Confirmar borrar assessmentPeriod-->
            <confirm-dialog
                :show="deleteAssignmentDialog"
                @canceled-dialog="deleteAssignmentDialog = false"
                @confirmed-dialog="deleteAssignment(deletedAssignment)"
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
import {prepareErrorText, showSnackbar} from "@/HelperFunctions"
import ConfirmDialog from "@/Components/ConfirmDialog";
import AssessmentPeriod from "@/models/AssessmentPeriod";
import Snackbar from "@/Components/Snackbar";
import Position from "@/models/Position";
import PositionAssignment from "@/models/PositionAssignment";

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
                {text: 'Cargo', value: 'job_title'},
                {text: 'Posición asignada', value: 'name'},
                {text: 'Acciones', value: 'actions', sortable: false},
            ],
            assignments: [],
            positions: [],
            //AssessmentPeriods models
            newAssignment: new PositionAssignment(),
            editedAssignment: new PositionAssignment(),
            deletedAssignment: '',
            deletedAssignmentId: 0,
            //Snackbars
            snackbar: {
                text: "",
                type: 'alert',
                status: false,
                timeout: 2000,
            },
            //Dialogs
            editAssignmentDialog: false,
            deleteAssignmentDialog: false,
            createOrEditDialog: {
                model: 'newAssignment',
                method: 'createAssignment',
                dialogStatus: false,
            },
            isLoading: true,
        }
    },
    async created() {
        await this.getAllAssignments();
        await this.getAllPositions();
        this.isLoading = false;
    },

    methods: {

        handleSelectedMethod: function () {
            this[this.createOrEditDialog.method]();
        },

        getAllAssignments: async function () {
            let request = await axios.get(route('api.positionsAssignment.index'));
            console.log(request.data);
            this.assignments = request.data;
        },

        getAllPositions: async function () {
            let request = await axios.get(route('api.positions.index'));
            console.log(request.data);
            this.positions = request.data;
        },

        editAssignment: async function () {
            //Verify request
            console.log(this.editedAssignment);
            //Recollect information

            try {
                let request = await axios.post(route('api.positionsAssignment.create'), {data: this.editedAssignment});
                this.createOrEditDialog.dialogStatus = false;
                showSnackbar(this.snackbar, request.data.message, 'success');
                await this.getAllAssignments();
                //Clear role information
                this.editedAssignment = new PositionAssignment();
            } catch (e) {
                showSnackbar(this.snackbar, prepareErrorText(e), 'alert');
            }
        },

        confirmDeleteAssignment: function (assignment) {
            this.deletedAssignment = assignment;
            this.deletedAssignmentId = assignment.id;
            this.deleteAssignmentDialog = true;
        },

        deleteAssignment: async function (assignment) {
            try {
                let request = await axios.post(route('api.positionsAssignment.destroy') , {data: assignment});
                this.deleteAssignmentDialog = false;
                showSnackbar(this.snackbar, request.data.message, 'success');
                await this.getAllPositions();
            } catch (e) {
                showSnackbar(this.snackbar, e.response.data.message, 'red', 3000);
            }
        },
        setAssignmentDialogToCreateOrEdit(which, item = null) {
            if (which === 'edit') {
                this.editedAssignment = item
                this.createOrEditDialog.method = 'editAssignment';
                this.createOrEditDialog.dialogStatus = true;
                console.log(item)
            }
        },
    },
}
</script>
