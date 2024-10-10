<template>
    <AuthenticatedLayout>
        <Snackbar :timeout="snackbar.timeout" :text="snackbar.text" :type="snackbar.type"
                  :show="snackbar.status" @closeSnackbar="snackbar.status = false"></Snackbar>

        <v-container>
            <div class="d-flex flex-column align-end mb-8">
                <h2 class="align-self-start">Gestionar Clientes externos</h2>
                <div>
                    <v-btn
                        color="primario"
                        class="grey--text text--lighten-4"
                        @click="setExternalClientDialogToCreateOrEdit('create')"
                    >
                        Crear nuevo cliente externo
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
                    :items="externalClients"
                    :items-per-page="20"
                    :footer-props="{
                        'items-per-page-options': [20,50,100,-1]
                    }"
                    class="elevation-1"
                >
                    <template v-slot:item.actions="{ item }">
                        <v-icon
                            class="mr-2 primario--text"
                            @click="setExternalClientDialogToCreateOrEdit('edit',item)"
                        >
                            mdi-pencil
                        </v-icon>
                        <v-icon
                            class="primario--text"
                            @click="confirmDeleteExternalClient(item)"
                        >
                            mdi-delete
                        </v-icon>
                        <v-icon
                            class="mr-2 primario--text"
                            @click="setExternalClientDialogToEditPassword(item)"
                        >
                            mdi-lock
                        </v-icon>
                    </template>
                </v-data-table>
            </v-card>
            <!--Acaba tabla-->

            <!------------Seccion de dialogos ---------->
            <!--Crear o editar cliente externo -->
            <v-dialog
                v-model="createOrEditDialog.dialogStatus"
                persistent
                max-width="650px"
            >
                <v-card>
                    <v-card-title>
                        <span>
                        </span>
                        <span class="text-h5">Crear/editar un cliente externo</span>
                    </v-card-title>
                    <v-card-text>
                        <v-container>
                            <v-row>
                                <v-col cols="6">
                                    <v-text-field
                                        label="Nombre del cliente externo"
                                        required
                                        v-model="$data[createOrEditDialog.model].name"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="6">
                                    <v-text-field
                                        :rules="emailRules"
                                        label="Correo cliente externo"
                                        required
                                        v-model="$data[createOrEditDialog.model].email"
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
                v-model="changeExternalClientPasswordDialog"
                persistent
                max-width="650px"
            >
                <v-card>
                    <v-card-title>
                        <span>
                        </span>
                        <span class="text-h5">Crear/Cambiar contraseña</span>
                    </v-card-title>
                    <v-card-text>
                        <v-container>
                            <p> Esta acción solicitará una nueva contraseña al usuario seleccionado y le enviará un correo notificándole del cambio, ¿está seguro de continuar?</p>
                        </v-container>

                    </v-card-text>
                    <v-card-actions>
                        <v-spacer></v-spacer>
                        <v-btn
                            color="primario"
                            text
                            @click="changeExternalClientPasswordDialog = false"
                        >
                            Cancelar
                        </v-btn>
                        <v-btn
                            color="primario"
                            text
                            @click="changeExternalClientPassword"
                        >
                            Confirmar
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <!--Confirmar borrar cliente externo-->
            <confirm-dialog
                :show="deleteExternalClientDialog"
                @canceled-dialog="deleteExternalClientDialog = false"
                @confirmed-dialog="deleteExternalClient(deletedExternalClientId)"
            >
                <template v-slot:title>
                    Eliminar cliente externo
                </template>
                Estás a punto de eliminar al cliente externo seleccionado.
                ¡Cuidado! esta acción es irreversible.

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
import ExternalClient from "@/models/ExternalClient";

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
                {text: 'Correo', value: 'email', sortable: false},
                {text: 'Acciones', value: 'actions', sortable: false}
            ],
            externalClients: [],
            //AssessmentPeriods models
            newExternalClient: new ExternalClient(),
            editedExternalClient: new ExternalClient(),
            deletedExternalClientId: 0,
            //Snackbars
            snackbar: {
                text: "",
                type: 'alert',
                status: false,
                timeout: 2000,
            },
            newPassword: '',
            //Dialogs
            deleteExternalClientDialog: false,
            changeExternalClientPasswordDialog: false,
            createOrEditDialog: {
                model: 'newExternalClient',
                method: 'createExternalClient',
                dialogStatus: false,
            },
            isLoading: true,
            emailRules: [ v => /.+@.+/.test(v) || 'Por favor digita una dirección de correo con el formato adecuado: correo@domininio' ]
        }
    },
    async created() {
        await this.getExternalClients();
        this.isLoading = false;
    },

    methods: {

        getExternalClients: async function () {
            let request = await axios.get(route('api.externalClients.index'));
            this.externalClients = request.data;
            console.log(this.externalClients);
        },

        handleSelectedMethod: function () {
            this[this.createOrEditDialog.method]();
        },

        createExternalClient: async function () {
            if (this.newExternalClient.hasEmptyProperties()) {
                showSnackbar(this.snackbar, 'Debes diligenciar todos los campos obligatorios', 'red', 2000);
                return;
            }
            let data = this.newExternalClient.toObjectRequest();
            try {
                let request = await axios.post(route('api.externalClients.store'), data);
                this.createOrEditDialog.dialogStatus = false;
                //Clear role information
                this.newExternalClient = new ExternalClient();
                showSnackbar(this.snackbar, request.data.message, 'success', 10000);
                await this.getExternalClients();
            } catch (e) {
                showSnackbar(this.snackbar, e.response.data.message, 'alert', 3000);
            }
        },

        editExternalClient: async function () {
            //Verify request
            if (this.editedExternalClient.hasEmptyProperties()) {
                showSnackbar(this.snackbar, 'Debes diligenciar todos los campos obligatorios', 'red', 2000);
                return;
            }
            //Recollect information
            let data = this.editedExternalClient.toObjectRequest();
            console.log(data);
            try {
                let request = await axios.patch(route('api.externalClients.update', {'externalClient': this.editedExternalClient.id}), data);
                this.createOrEditDialog.dialogStatus = false;
                showSnackbar(this.snackbar, request.data.message, 'success');
                await this.getExternalClients();

                //Clear role information
                this.editedExternalClient= new ExternalClient();
            } catch (e) {
                showSnackbar(this.snackbar, prepareErrorText(e), 'alert');
            }
        },

        confirmDeleteExternalClient: function (externalClient) {
            this.deletedExternalClientId = externalClient.id;
            this.deleteExternalClientDialog = true;
        },

        deleteExternalClient: async function (externalClient) {
            try {
                let request = await axios.delete(route('api.externalClients.destroy', {externalClient: externalClient}));
                this.deleteExternalClientDialog = false;
                showSnackbar(this.snackbar, request.data.message, 'success');
                await this.getExternalClients();
            } catch (e) {
                showSnackbar(this.snackbar, e.response.data.message, 'red', 3000);
            }
        },

        setExternalClientDialogToCreateOrEdit(which, item = null) {
            if (which === 'create') {
                this.createOrEditDialog.method = 'createExternalClient';
                this.createOrEditDialog.model = 'newExternalClient';
                this.createOrEditDialog.dialogStatus = true;
            }

            if (which === 'edit') {
                this.editedExternalClient = ExternalClient.fromModel(item);
                this.createOrEditDialog.method = 'editExternalClient';
                this.createOrEditDialog.model = 'editedExternalClient';
                this.createOrEditDialog.dialogStatus = true;
            }
        },

        setExternalClientDialogToEditPassword (item = null){
            this.editedExternalClient = ExternalClient.fromModel(item);
            this.createOrEditDialog.method = 'changeExternalClientPassword';
            this.createOrEditDialog.model = 'editedExternalClient';
            this.changeExternalClientPasswordDialog = true;
        },

        async changeExternalClientPassword(){

            let data = this.editedExternalClient.toObjectRequest();

            try {
                let request = await axios.post(route('api.externalClients.updatePassword'), data);
                this.changeExternalClientPasswordDialog = false;
                showSnackbar(this.snackbar, request.data.message, 'success');
                await this.getExternalClients();

                //Clear role information
                this.editedExternalClient = new ExternalClient();
            } catch (e) {
                showSnackbar(this.snackbar, prepareErrorText(e), 'alert');
            }
        }

    },
}
</script>
