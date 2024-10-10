<template>
    <AuthenticatedLayout>
        <Snackbar :timeout="snackbar.timeout" :text="snackbar.text" :type="snackbar.type"
                  :show="snackbar.status" @closeSnackbar="snackbar.status = false"></Snackbar>

        <v-container>
            <div class="d-flex flex-column align-end mb-8">
                <h2 class="align-self-start" >Gestionar compromisos</h2>
                <div  v-if="role.name === 'administrador'" class="mt-5">
                    <v-btn
                        color="primario"
                        class="grey--text text--lighten-4"
                        @click="setCommitmentDialogToCreateOrEdit('create')"
                    >
                        Crear nuevo compromiso
                    </v-btn>
                    <InertiaLink
                        as="v-btn"
                        color="primario"
                        class="grey--text text--lighten-4"
                        :href="route('trainings.index.view')"
                    >
                        Tipos de Compromisos
                    </InertiaLink>
                    <InertiaLink
                        as="v-btn"
                        color="primario"
                        class="grey--text text--lighten-4"
                        :href="route('reminders.index.view')"
                    >
                        Configurar Notificación
                    </InertiaLink>
                    <v-btn
                        color="primario"
                        class="grey--text text--lighten-4"
                        @click="downloadExcel"
                    >
                        Descargar Excel
                    </v-btn>
                </div>

                <div  v-if="role.name === 'funcionario'" class="mt-5">
                    <v-btn
                        color="primario"
                        class="grey--text text--lighten-4"
                        @click="getCommitmentsStatus"
                    >
                        Visualizar Estado Actual
                    </v-btn>
                </div>
            </div>

            <!--Inicia tabla-->

            <v-card>
                <v-card-title>
                    <v-text-field
                        v-model="search"
                        append-icon="mdi-magnify"
                        label="Filtrar por nombre, tipo de compromiso o fecha"
                        single-line
                        hide-details
                    ></v-text-field>
                </v-card-title>
                <v-data-table
                    :search="search"
                    loading-text="Cargando, por favor espere..."
                    :loading="isLoading"
                    :headers="headers"
                    :items="commitments"
                    :items-per-page="20"
                    :footer-props="{
                        'items-per-page-options': [20,50,100,-1]
                    }"
                    class="elevation-1"
                >

                    <template v-slot:item.done="{ item }">
                        {{item.done === 0 ? 'No' : 'Sí'}}
                    </template>

                    <template v-slot:item.actions="{ item }">
                        <v-tooltip top >
                            <template v-slot:activator="{on,attrs}">
                                <InertiaLink :href="route('api.commitments.edit', {commitment:item.id})">
                                    <v-icon
                                        v-bind="attrs"
                                        v-on="on"
                                        class="mr-2 primario--text"
                                    >
                                        mdi-location-enter
                                    </v-icon>
                                </InertiaLink>
                            </template>
                            <span>Visualizar compromiso</span>
                        </v-tooltip>

                        <div v-if="role.name === 'administrador' && item.done === 0">
                        <v-icon
                            class="mr-2 primario--text"
                            @click="setCommitmentDialogToCreateOrEdit('edit',item)"
                        >
                            mdi-pencil
                        </v-icon>
                        <v-icon
                            class="mr-2 primario--text"
                            @click="confirmDeleteCommitment(item)"
                        >
                            mdi-delete
                        </v-icon>
                        </div>
                    </template>
                </v-data-table>
            </v-card>
            <!--Acaba tabla-->

            <!--Crear o editar Compromiso-->
            <v-dialog
                v-model="createOrEditDialog.dialogStatus"
                persistent
                max-width="650px"
            >
                <v-card>
                    <v-card-title>
                        <span>
                        </span>
                        <span class="text-h5">Crear un nuevo compromiso</span>
                    </v-card-title>
                    <v-card-text>
                        <v-container>
                            <v-row>
                                <v-col cols="12">
                                    <span>Ingrese el nombre del funcionario</span>
                                    <v-autocomplete
                                        label="Por favor selecciona un usuario"
                                        :items="functionaries"
                                        v-model="$data[createOrEditDialog.model].user_id"
                                        item-text="name"
                                        item-value="user_id"
                                    ></v-autocomplete>
                                </v-col>
                                <v-col cols="12">
                                    <span>Ingrese el tipo de compromiso</span>
                                    <v-autocomplete
                                        label="Por favor selecciona un tipo de compromiso"
                                        :items="trainings"
                                        v-model="$data[createOrEditDialog.model].training_id"
                                        item-text="name"
                                        item-value="id"
                                    ></v-autocomplete>
                                </v-col>
                                <v-col cols="12" :md="12" class="d-flex flex-column">
                                    <span class="subtitle-1">
                                        Fecha límite para registrar compromiso
                                    </span>
                                    <v-date-picker v-model="$data[createOrEditDialog.model].due_date" full-width>
                                    </v-date-picker>
                                </v-col>

                            </v-row>
                        </v-container>
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

            <!--Confirmar borrar Commitment-->
            <confirm-dialog
                :show="deleteCommitmentDialog"
                @canceled-dialog="deleteCommitmentDialog = false"
                @confirmed-dialog="deleteCommitment(deletedCommitmentId)"
            >
                <template v-slot:title>
                    Estás a punto de eliminar el compromiso seleccionado
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
import Commitment from "@/models/Commitment";
import Papa from "papaparse";

export default {
    components: {
        ConfirmDialog,
        AuthenticatedLayout,
        InertiaLink,
        Snackbar,
    },

    props: {
        role: Object,
        token: String
    },

    data: () => {
        return {
            //Table info
            search: '',
            headers: [
                {text: 'Funcionario', value: 'user_name'},
                {text: 'Tipo de compromiso', value: 'training_name'},
                {text: 'Fecha máxima', value: 'due_date'},
                {text: 'Realizado', value: 'done'},
                {text: 'Fecha realizado', value: 'done_date'},
                {text: 'Acciones', value: 'actions', sortable: false},
            ],
            functionaries: [],
            commitments:[],
            trainings: [],
            //AssessmentPeriods models
            newCommitment: new Commitment(),
            editedCommitment: new Commitment(),
            deletedCommitmentId: 0,
            //Snackbars
            snackbar: {
                text: "",
                type: 'alert',
                status: false,
                timeout: 2000,
            },
            //Dialogs
            deleteCommitmentDialog: false,
            createOrEditDialog: {
                model: 'newCommitment',
                method: 'createCommitment',
                dialogStatus: false,
            },
            isLoading: true,
        }
    },
    async created() {
        await this.getCommitments();
        await this.getAllFunctionaries();
        await this.getTrainings();
        this.isLoading = false;
    },

    methods: {

        async getCommitments () {
            console.log(this.role, "The role")
            let request = await axios.get(route('commitments.index', {role: this.role.id}));
            this.commitments = request.data;
            console.log(this.commitments);
        },

        async getAllFunctionaries() {
            let request = await axios.get(route('api.functionaries.index'));
            this.functionaries = request.data;
        },

        async getTrainings(){
            let request = await axios.get(route('api.trainings.index'));
            this.trainings = request.data;
        },

        createCommitment: async function () {
            if (this.newCommitment.hasEmptyProperties()) {
                showSnackbar(this.snackbar, 'Debes diligenciar todos los campos obligatorios', 'red', 2000);
                return;
            }
            let data = this.newCommitment.toObjectRequest();
            //Clear role information
            this.newCommitment = new Commitment();
            try {
                let request = await axios.post(route('api.commitments.store'), data);
                this.createOrEditDialog.dialogStatus = false;
                showSnackbar(this.snackbar, request.data.message, 'success', 10000);
                await this.getCommitments();
            } catch (e) {
                showSnackbar(this.snackbar, e.response.data.message, 'alert', 7000);
            }
        },

        editCommitment: async function () {
            //Verify request
            if (this.editedCommitment.hasEmptyProperties()) {
                showSnackbar(this.snackbar, 'Debes diligenciar todos los campos obligatorios', 'red', 2000);
                return;
            }
            //Recollect information
            let data = this.editedCommitment.toObjectRequest();

            try {
                let request = await axios.patch(route('api.commitments.update', {'commitment': this.editedCommitment.id}), data);
                this.createOrEditDialog.dialogStatus = false;
                showSnackbar(this.snackbar, request.data.message, 'success');
                await this.getCommitments();

                //Clear role information
                this.editedCommitment = new Commitment();
            } catch (e) {
                showSnackbar(this.snackbar, prepareErrorText(e), 'alert', 7000);
            }
        },

        confirmDeleteCommitment: function (commitment) {
            this.deletedCommitmentId = commitment.id;
            this.deleteCommitmentDialog = true;
        },

        deleteCommitment: async function (commitment) {
            try {
                let request = await axios.delete(route('api.commitments.destroy', {commitment: commitment}));
                this.deleteCommitmentDialog = false;
                showSnackbar(this.snackbar, request.data.message, 'success');
                await this.getCommitments();
            } catch (e) {
                showSnackbar(this.snackbar, e.response.data.message, 'red', 5000);
            }

        },

        handleSelectedMethod: function () {
            this[this.createOrEditDialog.method]();
        },

        setCommitmentDialogToCreateOrEdit(which, item = null) {
            if (which === 'create') {
                this.createOrEditDialog.method = 'createCommitment';
                this.createOrEditDialog.model = 'newCommitment';
                this.createOrEditDialog.dialogStatus = true;
            }

            if (which === 'edit') {
                this.editedCommitment = Commitment.fromModel(item);
                this.createOrEditDialog.method = 'editCommitment';
                this.createOrEditDialog.model = 'editedCommitment';
                this.createOrEditDialog.dialogStatus = true;
            }
        },

        getCommitmentsStatus(){
            var winName='MyWindow';
            var winURL= route('reports.commitmentPDF');
            var windowOption='resizable=yes,height=600,width=800,location=0,menubar=0,scrollbars=1';
            var params = { _token: this.token};

            var form = document.createElement("form");
            form.setAttribute("method", "post");
            form.setAttribute("action", winURL);
            form.setAttribute("target",winName);
            for (var i in params) {
                if (params.hasOwnProperty(i)) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = i;
                    input.value = params[i];
                    form.appendChild(input);
                }
            }
            document.body.appendChild(form);
            window.open('', winName, windowOption);
            form.target = winName;
            form.submit();
            document.body.removeChild(form);
        },


        downloadExcel (){
            if (this.commitments.length === 0){
                showSnackbar(this.snackbar, "No hay datos para guardar", 'alert');
            }

            let headers = this.headers.filter(header => {
                return !header.hasOwnProperty('sortable')
            })

            let excelInfo = this.commitments.map(item =>{
                let data = {}
                headers.forEach( header =>{
                    data[header.text] = item[header.value]
                })
                return {
                    ...data
                }
            })
            let csv = Papa.unparse(excelInfo, {delimiter:';'});
            var csvData = new Blob(["\uFEFF"+csv], {type: 'text/csv;charset=utf-8;'});
            var csvURL =  null;
            if (navigator.msSaveBlob)
            {
                csvURL = navigator.msSaveBlob(csvData, 'Consolidado_Compromisos.csv');
            }
            else
            {
                csvURL = window.URL.createObjectURL(csvData);
            }
            var tempLink = document.createElement('a');
            tempLink.href = csvURL;
            tempLink.setAttribute('download', 'Consolidado_Compromisos.csv');
            tempLink.click();
        },
    },
}
</script>
