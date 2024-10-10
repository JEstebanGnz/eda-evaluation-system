<template>
    <AuthenticatedLayout>
        <Snackbar :timeout="snackbar.timeout" :text="snackbar.text" :type="snackbar.type"
                  :show="snackbar.status" @closeSnackbar="snackbar.status = false"></Snackbar>

        <v-container>
            <div class="d-flex flex-column align-end mb-8">
                <h2 class="align-self-start">Gestionar funcionarios</h2>
                <div>
                    <v-btn
                        color="primario"
                        class="grey--text text--lighten-4"
                        @click="syncFunctionaries"
                        :disabled="isSync"
                    >
                        Sincronizar funcionarios
                    </v-btn>
                </div>
            </div>

            <div class="d-flex flex-column align-center mt-12">
                <h3 v-if="isSync">
                    Por favor espera, estamos realizando la sincronización de los funcionarios...
                </h3>
            </div>

            <IndexChanges @getAllFunctionaries="getAllFunctionaries" ref="childComponent"></IndexChanges>

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
                    :items="functionaries"
                    :items-per-page="20"
                    :footer-props="{
                        'items-per-page-options': [20,50,100,-1]
                    }"
                    class="elevation-1"
                    :item-class="getRowColor"
                >

                    <template v-slot:item.user.commitments="{ item }">
                        {{item.user.commitments.length}}
                    </template>

                    <template v-slot:item.is_active="{ item }">
                        {{item.is_active === 1 ? 'Activo' : 'Inactivo'}}
                    </template>


                   <template v-slot:item.actions="{ item }">
                       <v-icon
                           v-if="item.is_active === 1"
                           class="mr-2 primario--text"
                           @click="changeFunctionaryStatus(item,0)"
                       >
                           mdi-close
                       </v-icon>
                       <v-icon
                            v-if="item.is_active !== 1"
                            class="mr-2 primario--text"
                            @click="changeFunctionaryStatus(item,1)"
                        >
                            mdi-check
                        </v-icon>

                    </template>
                </v-data-table>
            </v-card>
            <!--Acaba tabla-->
        </v-container>
    </AuthenticatedLayout>
</template>

<script>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import {InertiaLink} from "@inertiajs/inertia-vue";
import {prepareErrorText, showSnackbar} from "@/HelperFunctions"
import ConfirmDialog from "@/Components/ConfirmDialog";
import Snackbar from "@/Components/Snackbar";
import IndexChanges from "@/Pages/Functionaries/IndexChanges";

export default {
    components: {
        IndexChanges,
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
                {text: 'Nombre', value: 'user.name'},
                {text: 'Documento', value: 'identification_number'},
                {text: 'Dependencia', value: 'dependency_name'},
                {text: 'Posición', value: 'job_title'},
                {text: 'Estado', value: 'is_active'},
                {text: 'Compromisos asignados', value: 'user.commitments'},
                {text: 'Acciones', value: 'actions', sortable: false},
            ],
            functionaries: [],
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
        await this.getAllFunctionaries();
        this.isLoading = false;
    },

    methods: {

        getAllFunctionaries: async function () {
            let request = await axios.get(route('api.functionaries.index'));
            this.functionaries = request.data;
            await this.$refs.childComponent.getChanges();
            console.log(this.functionaries);
        },

        syncFunctionaries: async function () {
            try {
                this.isSync= true;
                let request = await axios.post(route('api.functionaryProfiles.sync'));
                showSnackbar(this.snackbar, request.data.message, 'success');
                await this.getAllFunctionaries();
                await this.$refs.childComponent.getChanges();
                this.isSync = false;
            } catch (e) {
                showSnackbar(this.snackbar, prepareErrorText(e), 'alert', 10000);
                this.isSync = false;
            }
        },

        changeFunctionaryStatus: async function (functionary, status) {
            try {
                let request = await axios.post(route('api.functionaryProfiles.changeStatus', {functionaryProfile: functionary.id}), {
                    status
                });
                showSnackbar(this.snackbar, request.data.message, 'success');
                await this.getAllFunctionaries();
            } catch (e) {
                showSnackbar(this.snackbar, e.response.data.message, 'red', 3000);
            }
        },

        getRowColor: function (item) {
            return item.is_active === 1 ? 'green lighten-5' :  'red lighten-5';
        },

    },


}
</script>
