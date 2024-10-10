<template>
    <AuthenticatedLayout>
        <Snackbar :timeout="snackbar.timeout" :text="snackbar.text" :type="snackbar.type"
                  :show="snackbar.status" @closeSnackbar="snackbar.status = false"></Snackbar>

        <v-container>
            <div class="d-flex flex-column align-end mb-8">
                <h2 class="align-self-start">Gestionar posiciones</h2>
                <div>
                    <v-btn
                        color="primario"
                        class="grey--text text--lighten-4"
                        @click="setPositionDialogToCreateOrEdit('create')"
                    >
                        Crear nueva posición
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
                    :items="positions"
                    :items-per-page="20"
                    :footer-props="{
                        'items-per-page-options': [20,50,100,-1]
                    }"
                    class="elevation-1"
                >
                    <template v-slot:item.actions="{ item }">
                        <v-icon
                            class="mr-2 primario--text"
                            @click="setPositionDialogToCreateOrEdit('edit',item)"
                        >
                            mdi-pencil
                        </v-icon>
                        <v-icon
                            class="primario--text"
                            @click="confirmDeletePosition(item)"
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
                        <span class="text-h5">Crear una nueva posición</span>
                    </v-card-title>
                    <v-card-text>
                        <v-container>
                            <v-row>
                                <v-col cols="12">
                                    <v-text-field
                                        label="Nombre de la posición"
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

            <!--Confirmar borrar position-->
            <confirm-dialog
                :show="deletePositionDialog"
                @canceled-dialog="deletePositionDialog = false"
                @confirmed-dialog="deletePosition(deletedPositionId)"
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
                {text: 'Nombre de la posición', value: 'name'},
                {text: 'Acciones', value: 'actions', sortable: false},
            ],
            positions: [],
            //AssessmentPeriods models
            newPosition: new Position(),
            editedPosition: new Position(),
            deletedPositionId: 0,
            //Snackbars
            snackbar: {
                text: "",
                type: 'alert',
                status: false,
                timeout: 2000,
            },
            //Dialogs
            deletePositionDialog: false,
            createOrEditDialog: {
                model: 'newPosition',
                method: 'createPosition',
                dialogStatus: false,
            },
            isLoading: true,
        }
    },
    async created() {
        await this.getPositions();
        this.isLoading = false;
    },

    methods: {

        handleSelectedMethod: function () {
            this[this.createOrEditDialog.method]();
        },

        createPosition: async function () {
            if (this.newPosition.hasEmptyProperties()) {
                showSnackbar(this.snackbar, 'Debes diligenciar todos los campos obligatorios', 'red', 2000);
                return;
            }
            let data = this.newPosition.toObjectRequest();

            //Clear role information
            this.newPosition = new Position();

            try {
                let request = await axios.post(route('api.positions.store'), data);
                this.createOrEditDialog.dialogStatus = false;
                showSnackbar(this.snackbar, request.data.message, 'success', 2000);
                await this.getPositions();
            } catch (e) {
                showSnackbar(this.snackbar, e.response.data.message, 'alert', 3000);
            }
        },

        editPosition: async function () {
            //Verify request
            if (this.editedPosition.hasEmptyProperties()) {
                showSnackbar(this.snackbar, 'Debes diligenciar todos los campos obligatorios', 'red', 2000);
                return;
            }
            //Recollect information
            let data = this.editedPosition.toObjectRequest();

            try {
                let request = await axios.patch(route('api.positions.update', {'position': this.editedPosition.id}), data);
                this.createOrEditDialog.dialogStatus = false;
                showSnackbar(this.snackbar, request.data.message, 'success');
                await this.getPositions();

                //Clear role information
                this.editedPosition = new Position();
            } catch (e) {
                showSnackbar(this.snackbar, prepareErrorText(e), 'alert');
            }
        },

        confirmDeletePosition: function (position) {
            this.deletedPositionId = position.id;
            this.deletePositionDialog = true;
        },

        deletePosition: async function (position) {
            try {
                let request = await axios.delete(route('api.positions.destroy', {position: position}));
                this.deletePositionDialog = false;
                showSnackbar(this.snackbar, request.data.message, 'success');
                await this.getPositions();
            } catch (e) {
                showSnackbar(this.snackbar, e.response.data.message, 'red', 3000);
            }

        },
        getPositions: async function () {
            let request = await axios.get(route('api.positions.index'));
            this.positions = request.data;
        },
        setPositionDialogToCreateOrEdit(which, item = null) {
            if (which === 'create') {
                this.createOrEditDialog.method = 'createPosition';
                this.createOrEditDialog.model = 'newPosition';
                this.createOrEditDialog.dialogStatus = true;
            }

            if (which === 'edit') {
                this.editedPosition = Position.fromModel(item);
                this.createOrEditDialog.method = 'editPosition';
                this.createOrEditDialog.model = 'editedPosition';
                this.createOrEditDialog.dialogStatus = true;
            }

        },

    },
}
</script>
