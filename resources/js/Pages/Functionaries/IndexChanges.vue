<template>
        <v-container>
            <Snackbar :timeout="snackbar.timeout" :text="snackbar.text" :type="snackbar.type"
                      :show="snackbar.status" @closeSnackbar="snackbar.status = false"></Snackbar>
            <div class="d-flex flex-column align-end mb-8" v-if="changes.length>0">
                <h2 class="align-self-start">Cambios pendientes por aceptar</h2>
                <h4 class="align-self-start grey--text mt-5"> El renglón superior de cada registro,
                    muestra la información como se encuentra directamente en nómina,
                    mientras que el inferior muestra cómo se encuentra la información en EDA.
                    Los renglones que no tienen datos indican que la persona ya no se encuentran vinculados con la Universidad.</h4>
                <div class="table-responsive">
                    <table class="changes" id="table1">
                        <thead>
                        <tr>
                            <th scope="col">#ID</th>
                            <th scope="col">Nombre</th>
                            <th scope="col">Dependencia</th>
                            <th scope="col">Posición</th>
                            <th scope="col">Fecha de contratación</th>
                            <th scope="col">Asignaciones del funcionario</th>
                            <th scope="col">Acciones</th>
                        </tr>
                        </thead>
                        <tbody>

                        <tr v-for="change in changes">
                            <th scope="row">{{change.id}}</th>
                            <td>
                                <p>
                                    {{change.payload.name}}
                                </p>
                                <hr>
                                <p>
                                    {{change.official_answers.name}}
                                </p>
                                <hr>
                            </td>
                            <td>
                                <p>
                                    {{change.payload.dependency_name}}
                                </p>
                                <hr>
                                <p>
                                    {{change.official_answers.dependency_name}}
                                </p>
                                <hr>
                            </td>
                            <td>
                                <p>
                                    {{change.payload.job_title}}
                                </p>
                                <hr>
                                <p>
                                    {{change.official_answers.job_title}}
                                </p>
                                <hr>
                            </td>
                            <td>
                                <p>
                                    {{change.payload.hire_date}}
                                </p>
                                <hr>
                                <p>
                                    {{change.official_answers.hire_date}}
                                </p>
                                <hr>
                            </td>

                            <td>
                                <p>

                                </p>
                                <hr>
                                <div class="buttons">
                                <p>
                                    <v-btn type="submit" @click="showAssignments(change)"
                                           color="primario"
                                           class="grey--text text--lighten-4">Observar
                                    </v-btn>
                                </p>
                                </div>
                                <hr>
                            </td>

                            <td>
                                <div class="buttons">
                                    <form @submit.prevent="manageChange('approve',change)">
                                        <v-btn type="submit"
                                                color="green white--text">Aprobar
                                        </v-btn>
                                    </form>

                                    <form @submit.prevent="manageChange('decline',change)">
                                        <v-btn type="submit" color="red white--text">Rechazar</v-btn>
                                    </form>
                                </div>

                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <v-dialog
                v-model="showAssignmentsDialog"
                persistent
                max-width="700px"
            >
                <!--Inicia tabla-->
                <v-card
                style="padding: 5px">
                    <v-card-title> <p> Asignaciones del funcionario {{this.functionarySelected.name}} </p></v-card-title>
                    <v-card-subtitle>
                        <v-text-field
                            v-model="search"
                            append-icon="mdi-magnify"
                            label="Filtrar por nombre"
                            single-line
                            hide-details
                        ></v-text-field>
                    </v-card-subtitle>
                    <v-data-table
                        :search="search"
                        loading-text="Cargando, por favor espere..."
                        :loading="isLoading"
                        :headers="headersAssignments"
                        :items="functionarySelected.assignments"
                        :items-per-page="20"
                        :footer-props="{
                        'items-per-page-options': [20,50,100,-1]
                    }"
                        :hide-default-footer="true"
                        class="elevation-1"
                    >
                        <template v-slot:item.pending="{ item }">
                            {{item.pending === 0 ? "Sí" : "No"}}
                        </template>
                    </v-data-table>

                    <v-card-actions
                    class="mt-3">
                        <v-spacer></v-spacer>
                        <v-btn
                            color="primario"
                            class="grey--text text--lighten-4"
                            @click="showAssignmentsDialog = false"
                        >
                            Salir
                        </v-btn>
                    </v-card-actions>
                </v-card>
                <!--Acaba tabla-->
            </v-dialog>
        </v-container>
</template>


<style>
.table-responsive{
    height: 90%;
    width: 95%;
    overflow: scroll;
    display: block;
    margin-left: auto;
    margin-right: auto;
}
.changes th {
    height: 50px;
    width: 50px;
    text-align: center;
    vertical-align: middle;
}
.changes tr:nth-child(even) {
    background-color: #f2f2f2;
}
.changes td {
    max-width: 100%;
    white-space: nowrap;
    height: 50px;
    width: 50px;
    text-align: center;
    vertical-align: middle;
}
.changes th, td{
    padding:2px
}
.buttons{
    display: flex;
}
.buttons form{
    margin-left: 10px;
}
</style>

<script>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import {InertiaLink} from "@inertiajs/inertia-vue";
import {prepareErrorText, showSnackbar} from "@/HelperFunctions"
import ConfirmDialog from "@/Components/ConfirmDialog";
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
                {text: 'Nombre', value: 'payload.name'},
                {text: 'Dependencia', value: 'payload.dependency_name'},
                {text: 'Posición', value: 'payload.job_title'},
                {text: 'Fecha de contratación', value: 'payload.hire_date'}
            ],

            headersAssignments: [
                {text: 'Nombre', value: 'name'},
                {text: 'Dependencia', value: 'dependency_name'},
                {text: 'Rol', value: 'role'},
                {text: 'Realizado', value: 'pending'}
            ],

            changes: [],
            functionarySelected: [],

            //Dialogs
            showAssignmentsDialog: false,
            //Snackbars
            snackbar: {
                text: "",
                type: 'alert',
                status: false,
                timeout: 2000,
            },
            isLoading: true,
            isSync: false
        }
    },
    async created() {
        await this.getChanges();
        this.isLoading = false;
    },

    methods: {

        getChanges: async function () {
            let request = await axios.get(route('functionaryProfiles.pendingChanges'));
            this.changes = request.data;
            this.changes.forEach(change =>{
               change.payload = JSON.parse(change.payload);
            });
            console.log(this.changes);

        },

        showAssignments (change){
            this.showAssignmentsDialog = true;
            this.functionarySelected = change;
            console.log(change, 'CAMBIO SELECCIONADO PARA VISUALIZAR')
        },

        manageChange: async function (which,change){
            console.log(change);
                try {
                    let request = await axios.post(route(`functionaryProfiles.change.${which}`,{userId: change.user_id}))
                    console.log(request.data);
                    await this.getChanges();


                    //Here we call a function on the parent component
                    this.$emit('getAllFunctionaries')
                    showSnackbar(this.snackbar, request.data.message, 'success');
                } catch (e) {
                    showSnackbar(this.snackbar, prepareErrorText(e), 'alert', 10000);
                }
        },

    },


}
</script>
