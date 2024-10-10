<template>
    <AuthenticatedLayout>
        <Snackbar :timeout="snackbar.timeout" :text="snackbar.text" :type="snackbar.type"
                  :show="snackbar.status" @closeSnackbar="snackbar.status = false"></Snackbar>

        <v-container>
            <div class="d-flex flex-column align-end mb-8">
                <h2 class="align-self-start" > {{this.dependency.name}} </h2>

                <div>
                    <InertiaLink :href="route('api.dependencies.assessmentStatus',{dependency:this.dependency.identifier})"
                    class="grey--text text--lighten-4">
                        <v-btn>
                            Estado de la evaluación
                        </v-btn>
                    </InertiaLink>

                    <v-btn
                        color="primario"
                        class="grey--text text--lighten-4 ml-4"
                        @click="setAdminDialogToCreate"
                    >
                        Añadir Administrador
                    </v-btn>
                </div>
            </div>


            <h3 class="mt-9"> Administradores de la dependencia</h3>
            <!--Tabla administradores de dependencia-->
            <v-card class="mt-4">
                <v-data-table
                    loading-text="Cargando, por favor espere..."
                    :headers="headersAdmins"
                    :items="dependencyAdmins"
                    :items-per-page="20"
                    :footer-props="{
                        'items-per-page-options': [10,20,30,-1]
                    }"
                    class="elevation-1"
                >
                    <template v-slot:item.actions="{item}">
                        <v-tooltip top>
                            <template v-slot:activator="{on,attrs}">
                                <v-icon
                                    class="primario--text"
                                    @click="confirmDeleteAdmin(item)"
                                >
                                    mdi-delete
                                </v-icon>
                            </template>
                            <span>Gestionar asignaciones</span>
                        </v-tooltip>
                    </template>
                </v-data-table>
            </v-card>

            <h3 class="mt-9"> Funcionarios en la dependencia</h3>
            <!--Tabla funcionarios de la dependencia-->
            <v-card class="mt-4">
                <v-data-table
                    loading-text="Cargando, por favor espere..."
                    :headers="headersFunctionaries"
                    :items="functionaries"
                    :items-per-page="20"
                    :footer-props="{
                        'items-per-page-options': [10,20,30,-1]
                    }"
                    class="elevation-1"
                >

                    <template v-slot:item.actions="{item}">
                        <v-tooltip top>
                            <template v-slot:activator="{on,attrs}">
                                <InertiaLink :href="route('api.functionaryProfiles.edit', {functionaryProfile:item.functionary_profile_id, dependency: dependency.identifier})">
                                    <v-icon
                                        v-bind="attrs"
                                        v-on="on"
                                        class="mr-2 primario--text"
                                    >
                                        mdi-clipboard-check
                                    </v-icon>
                                </InertiaLink>
                            </template>
                            <span>Gestionar asignaciones</span>
                        </v-tooltip>
                    </template>
                </v-data-table>
            </v-card>

            <!--Sección de diálogos-->
            <!--Asignar Admin de Dependencia-->
            <v-dialog
                v-model="createAdminDialog"
                persistent
                max-width="650px"
            >
                <v-card>
                    <v-card-title>
                        <span>
                        </span>
                        <span class="text-h4-border"> Añadir administrador a la dependencia </span>
                    </v-card-title>
                    <v-card-text>
                        <span>Ingrese el nombre del funcionario</span>
                        <v-autocomplete
                            label="Por favor selecciona un usuario"
                            :items="allFunctionaries"
                            v-model="newDependencyAdmin"
                            item-text="name"
                            item-value="id"
                            return-object
                        ></v-autocomplete>
                        <small>Los campos con * son obligatorios </small>
                    </v-card-text>
                    <v-card-actions>
                        <v-spacer></v-spacer>
                        <v-btn
                            color="primario"
                            text
                            @click="createAdminDialog = false"
                        >
                            Cancelar
                        </v-btn>
                        <v-btn
                            color="primario"
                            text
                            @click="createAdmin"
                        >
                            Guardar cambios
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <!--Confirmar borrar position-->
            <confirm-dialog
                :show="deleteAdminDialog"
                @canceled-dialog="deleteAdminDialog = false"
                @confirmed-dialog="deleteAdmin(deletedAdminId)"
            >
                <template v-slot:title>
                    Estás a punto de eliminar al administrador seleccionado
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
import {showSnackbar} from "@/HelperFunctions";

export default {
    components: {
        ConfirmDialog,
        AuthenticatedLayout,
        InertiaLink,
        Snackbar,
    },
    props: {
        dependency: Object
    },
    data: () => {
        return {
            //Table info
            //Arrays of objects
            functionaries: [],
            allFunctionaries: [],
            dependencyAdmins: [],

            headersAdmins:[
                {text: 'Nombre', value: 'name'},
                {text: 'Correo', value: 'email'},
                {text: 'Acciones', value: 'actions', width: '10%', sortable: false},
            ],

            //Objects
            newDependencyAdmin: '',
            deletedAdmin: '',

            headersFunctionaries: [
                {text: 'Nombre', value: 'name'},
                {text: 'Cargo', value: 'job_title'},
                {text: 'Jefe', value: 'boss'},
                {text: 'Par', value: 'peer'},
                {text: 'Cliente Interno/Externo', value: 'client'},
                {text: 'Asignaciones del funcionario', value: 'actions', width: '10%', sortable: false},
            ],
            //Snackbars
            snackbar: {
                text: "",
                type: 'alert',
                status: false,
                timeout: 2000,
            },

            //Dialogs
            createAdminDialog: false,
            deleteAdminDialog: false,

            isLoading: true,
        }
    },

    async created() {
        await this.getFunctionariesFromDependency();
        await this.getDependencyAdmins();
        await this.getAllFunctionaries();
    },

    methods:{

        async getFunctionariesFromDependency() {
            let request = await axios.get(route('api.functionaries.index', {dependency:this.dependency}));
            this.functionaries = request.data;
        },

        async getAllFunctionaries() {
            let request = await axios.get(route('api.functionaries.index'));
            this.allFunctionaries = request.data;
            console.log(this.functionaries);
        },

        async getDependencyAdmins(){
            let request = await axios.get(route('api.dependencies.admins', {dependency: this.dependency.identifier}))
            this.dependencyAdmins = request.data.admins_from_dependency;
            console.log(this.dependencyAdmins);
        },

        capitalize($field){
            return $field.toLowerCase().split(' ').map((word) => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
        },

        setAdminDialogToCreate(){
            this.createAdminDialog = true;
        },

        createAdmin: async function (){
            try {
                let data = this.newDependencyAdmin
                console.log(this.newDependencyAdmin);
                let request = await axios.post(route('api.dependencyAdmins.store', {dependency: this.dependency.identifier}), {data})
                console.log(request.data);
                this.createAdminDialog = false;
                await this.getDependencyAdmins();
                showSnackbar(this.snackbar, request.data.message, 'success', 2000);
            } catch (e) {
                showSnackbar(this.snackbar, e.response.data.message, 'alert', 3000);
            }

        },

        deleteAdmin: async function (admin) {
            try {
                let request = await axios.delete(route('api.dependencyAdmins.destroy', {dependencyAdmin: admin, dependency: this.dependency.identifier}));
                this.deleteAdminDialog = false;
                await this.getDependencyAdmins();
                showSnackbar(this.snackbar, request.data.message, 'success');
            } catch (e) {
                showSnackbar(this.snackbar, e.response.data.message, 'red', 3000);
            }

        },

        confirmDeleteAdmin: function (admin) {
            this.deletedAdminId = admin.id;
            console.log(this.deletedAdminId);
            this.deleteAdminDialog = true;
        },


    },
}
</script>

