<template>
    <AuthenticatedLayout>
        <Snackbar :timeout="snackbar.timeout" :text="snackbar.text" :type="snackbar.type"
                  :show="snackbar.status" @closeSnackbar="snackbar.status = false"></Snackbar>

        <v-container>
            <div class="d-flex flex-column align-end mb-7">
                <h2 class="align-self-start">Gestionar Dependencias</h2>
                <div>
                    <v-btn
                        @click="syncDependencies"
                    >
                        Sincronizar dependencias
                    </v-btn>
                    <v-btn
                        color="primario"
                        class="grey--text text--lighten-4 ml-4"
                        @click="setDependencyDialogToCreateOrEdit('create')"
                    >
                        Crear nueva dependencia
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
                    :items="filteredDependencies"
                    :items-per-page="20"
                    :footer-props="{
                        'items-per-page-options': [20,50,100,-1]
                    }"
                    class="elevation-1"
                >
                    <template v-slot:item.name="{ item }"  >
                        {{ item.name}}
                    </template>

                    <template v-slot:item.is_custom="{ item }"  >
                        {{ item.is_custom ? 'Personalizada' : 'Integración' }}
                    </template>

                    <template v-slot:item.functionaries_from_dependency="{ item }">
                        {{ item.functionaries_from_dependency.length }}
                    </template>

                    <template v-slot:item.actions="{ item }">

                        <v-tooltip top
                                   v-if="item.is_custom"
                        >
                            <template v-slot:activator="{on,attrs}">

                                <v-icon
                                    v-bind="attrs"
                                    v-on="on"
                                    class="mr-2 primario--text"
                                    @click="setDependencyDialogToCreateOrEdit('edit',item)"
                                >
                                    mdi-pencil
                                </v-icon>

                            </template>
                            <span>Editar nombre de dependencia personalizada</span>
                        </v-tooltip>


                        <v-tooltip top
                                   v-if="item.is_custom"
                        >
                            <template v-slot:activator="{on,attrs}">

                                <v-icon
                                    v-bind="attrs"
                                    v-on="on"
                                    class="mr-2 primario--text"
                                    @click="confirmDeleteDependency(item)"
                                >
                                    mdi-delete
                                </v-icon>

                            </template>
                            <span>Borrar dependencia personalizada</span>
                        </v-tooltip>

                       <v-tooltip top>
                            <template v-slot:activator="{on,attrs}">
                                <InertiaLink :href="route('api.dependencies.edit', {dependency:item.identifier})">
                                    <v-icon
                                        v-bind="attrs"
                                        v-on="on"
                                        class="mr-2 primario--text"
                                    >
                                        mdi-account-group
                                    </v-icon>
                                </InertiaLink>
                            </template>
                            <span>Gestionar Dependencia</span>
                        </v-tooltip>

                    </template>
                </v-data-table>
            </v-card>
            <!--Acaba tabla-->

            <!------------Seccion de dialogos ---------->

            <!--Crear o editar dependency -->
            <v-dialog
                v-model="createOrEditDialog.dialogStatus"
                persistent
                max-width="650px"
            >
                <v-card>
                    <v-card-title>
                        <span>
                        </span>
                        <span class="text-h5">Crear/editar dependencia</span>
                    </v-card-title>
                    <v-card-text>
                        <v-container>
                            <v-row>
                                <v-col cols="12">
                                    <v-text-field
                                        label="Nombre de la dependencia"
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

            <confirm-dialog
                :show="deleteDependencyDialog"
                @canceled-dialog="deleteDependencyDialog = false"
                @confirmed-dialog="deleteDependency(deletedDependencyId)"
            >
                <template v-slot:title>
                    Estás a punto de eliminar la dependencia seleccionada
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
import Snackbar from "@/Components/Snackbar";
import Dependency from "@/models/Dependency";


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
            functionaries: [],
            search:'',
            headers: [
                {text: 'Nombre', value: 'name', align: 'center'},
                {text: 'Tipo de dependencia', value: 'is_custom'},
                {text: 'Usuarios', value: 'functionaries_from_dependency'},
                {text: 'Acciones', value: 'actions', sortable: false},
            ],
            assessmentPeriods: [],
            dependencies: [],
            //Units models
            newDependency: new Dependency(),
            editedDependency: new Dependency(),
            deletedDependencyId: 0,
            //Snackbars
            snackbar: {
                text: "",
                type: 'alert',
                status: false,
                timeout: 2000,
            },
            //Dialogs
            deleteDependencyDialog: false,
            createOrEditDialog: {
                model: 'newDependency',
                method: 'createDependency',
                dialogStatus: false,
            },
            isLoading: true,

        }
    },

    computed:{
        filteredDependencies(){
            return this.dependencies.filter(dependency => {
                return dependency.functionaries_from_dependency.length>0 || dependency.is_custom == 1;
            })
        }
    },

    async created() {
        await this.getAllDependencies();
        // this.capitalize();
        this.isLoading = false;
    },

    methods: {

        syncDependencies: async function () {
            try {
                let request = await axios.post(route('api.dependencies.sync'));
                console.log(request);
                await this.getAllDependencies();
                showSnackbar(this.snackbar, request.data.message, 'success');

            } catch (e) {
                showSnackbar(this.snackbar, prepareErrorText(e), 'alert');
            }
        },
        confirmDeleteDependency: function (dependency) {
            this.deletedDependencyId = dependency.identifier;
            console.log(this.deletedDependencyId);
            this.deleteDependencyDialog = true;
        },
        deleteDependency: async function (dependencyId) {
            try {
                console.log(dependencyId);
                let request = await axios.delete(route('api.dependencies.destroy', {dependency: dependencyId}));
                this.deleteDependencyDialog = false;
                showSnackbar(this.snackbar, request.data.message, 'success');
                this.getAllDependencies();
            } catch (e) {
                showSnackbar(this.snackbar, e.response.data.message, 'red', 10000);
            }

        },
        handleSelectedMethod: function () {
            this[this.createOrEditDialog.method]();
        },
        editDependency: async function () {
            //Verify request
            if (this.editedDependency.hasEmptyProperties()) {
                showSnackbar(this.snackbar, 'Debes diligenciar todos los campos obligatorios', 'alert', 2000);
                return;
            }
            let data = this.editedDependency.toObjectRequest();

            console.log(data);
            let request = await axios.patch(route('api.dependencies.update', {'dependency': this.editedDependency.identifier}),data);
            this.createOrEditDialog.dialogStatus = false;
            showSnackbar(this.snackbar, request.data.message, 'success');
            this.getAllDependencies();
            //Clear role information
            this.editedDependency = new Dependency();
            /*catch {
               showSnackbar(this.snackbar, prepareErrorText(), 'alert');
           }*/
        },

        createDependency: async function () {
            if (this.newDependency.hasEmptyProperties()) {
                showSnackbar(this.snackbar, 'Debes diligenciar todos los campos obligatorios', 'red', 2000);
                return;
            }
            let data = this.newDependency.toObjectRequest();
            console.log(data);

            try {

                let request = await axios.post(route('api.dependencies.store'), data);
                this.createOrEditDialog.dialogStatus = false;
                showSnackbar(this.snackbar, request.data.message, 'success', 2000);
                this.getAllDependencies();
                this.newDependency = new Dependency();

            } catch (e) {
                showSnackbar(this.snackbar, e.response.data.message, 'alert', 3000);
            }
        },

        getAllDependencies: async function () {
            let request = await axios.get(route('api.dependencies.index'));
            this.dependencies = request.data
            // await this.capitalize();
            console.log(this.dependencies)
        },

        setDependencyDialogToCreateOrEdit(which, item = null) {
            if (which === 'create') {
                this.createOrEditDialog.method = 'createDependency';
                this.createOrEditDialog.model = 'newDependency';
                this.createOrEditDialog.dialogStatus = true;
            }
            if (which === 'edit') {

                this.editedUnit = Dependency.fromModel(item);
                this.createOrEditDialog.method = 'editDependency';
                this.createOrEditDialog.model = 'editedDependency';
                this.createOrEditDialog.dialogStatus = true;
            }
        },
    },


}
</script>


