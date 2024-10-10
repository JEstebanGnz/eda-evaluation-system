<template>
        <v-container>
            <Snackbar :timeout="snackbar.timeout" :text="snackbar.text" :type="snackbar.type"
                      :show="snackbar.status" @closeSnackbar="snackbar.status = false"></Snackbar>
            <h2 class="align-self-start mb-2"> Archivos/Adjuntos del compromiso </h2>
            <!--Inicia tabla-->
            <v-card style="margin-bottom: 30px">
                <v-data-table
                    :search="search"
                    loading-text="Cargando, por favor espere..."
                    no-results-text="Aún no hay ningún comentario para este compromiso"
                    :loading="isLoading"
                    :headers="headers"
                    :items="files"
                    :items-per-page="20"
                    :footer-props="{
                        'items-per-page-options': [20,50,100,-1]
                    }"
                    class="elevation-1"
                >
                    <template v-slot:item.actions="{ item }">
                            <v-tooltip top>
                                <template v-slot:activator="{ on, attrs }">
                                    <form :action="route('certifications.downloadFile',{certification: item.encoded_file_name})" method="GET" style="display: inline">
                                    <v-btn
                                        type="submit"
                                        v-on="on"
                                        v-bind="attrs"
                                        icon
                                        class="mr-2 primario--text"
                                    >
                                        <v-icon>
                                            mdi-download-circle
                                        </v-icon>
                                    </v-btn>
                                    </form>
                                </template>
                                <span>Descargar archivo</span>
                            </v-tooltip>

                        <v-tooltip bottom>
                            <template v-slot:activator="{ on, attrs }">
                                    <v-icon
                                        v-on="on"
                                        v-bind="attrs"
                                        class="mr-2 primario--text"
                                        @click="deleteFile(item)"
                                        v-if="role.name === 'administrador' && commitment.done === 0">
                                        mdi-delete
                                    </v-icon>
                            </template>
                            <span>Borrar archivo</span>
                        </v-tooltip>
                    </template>
                </v-data-table>
            </v-card>


            <!--Agregar archivos -->
            <v-dialog
                v-model="addFileDialog"
                persistent
                max-width="650px"
            >
                <v-card>
                    <v-card-title>
                        <span>
                        </span>
                        <span class="text-h5">Añadir un archivo al compromiso</span>
                    </v-card-title>
                    <v-card-text>
                        <v-container>
                            <v-row>
                                <v-col cols="12">
                                    <template>
                                        <v-file-input
                                            label="Click aquí para agregar el archivo"
                                            outlined
                                            dense
                                            accept="image/*,.pdf,.doc,.docx"
                                            @change="addFile"
                                        ></v-file-input>
                                        <h4>Formatos de archivo soportados: .pdf .doc </h4>
                                    </template>
                                </v-col>
                            </v-row>
                        </v-container>
                    </v-card-text>
                    <v-card-actions>
                        <v-spacer></v-spacer>
                        <v-btn
                            color="primario"
                            text
                            @click="addFileDialog = false"
                        >
                            Cancelar
                        </v-btn>
                        <v-btn
                            color="primario"
                            text
                            @click="checkAndSendFile"
                        >
                            Guardar archivo
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <!--Confirmar borrar archivo-->
            <confirm-dialog
                :show="deleteFileDialog"
                @canceled-dialog="deleteFileDialog = false"
                @confirmed-dialog="deleteFile(deletedFileId)"
            >
                <template v-slot:title>
                    Estás a punto de eliminar el archivo seleccionado
                </template>

                ¡Cuidado! esta acción es irreversible

                <template v-slot:confirm-button-text>
                    Borrar
                </template>
            </confirm-dialog>

        </v-container>
</template>

<script>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import {InertiaLink} from "@inertiajs/inertia-vue";
import {prepareErrorText, showSnackbar} from "@/HelperFunctions"
import ConfirmDialog from "@/Components/ConfirmDialog";
import Snackbar from "@/Components/Snackbar";
import Comment from "@/models/Comment"


export default {
    components: {
        ConfirmDialog,
        AuthenticatedLayout,
        InertiaLink,
        Snackbar,
    },

    props: {
        commitment: Object,
        role: Object
    },

    data: () => {
        return {
            //Table info
            search: '',
            headers:[
                {text: 'Nombre de archivo', value: 'original_file_name'},
                {text: 'Añadido por', value: 'user_name', sortable: false},
                {text: 'Acciones', value: 'actions', sortable: false, width:'15%'},
                {text: 'Última actualización', value: 'updated_at', sortable: false},
            ],

            files:[],

            //Snackbars
            snackbar: {
                text: "",
                type: 'alert',
                status: false,
                timeout: 2000,
            },

            fileSelected: null,

            deletedFileId: null,

            //Dialogs
            isLoading: true,
            addFileDialog: false,

            deleteFileDialog: false,
        }
    },
    async created() {
        await this.getFiles();
        this.isLoading = false;
    },

    methods: {

       setAddFileDialog(){
            this.addFileDialog = true;
        },

        getFiles: async function (){
            let data = this.commitment
            let request = await axios.get(route('api.certifications.index', data))
            this.files = request.data;
            console.log(request.data);

        },

        addFile: function (e){
            if (e === null){
                return;
            }
            this.fileSelected = e;
        },

        checkAndSendFile: async function (){
            const file = new FormData();
            file.append("file", this.fileSelected)
            file.append("commitment_id", this.commitment.id)
            try {
                let request = await axios.post(route('api.certifications.store'), file,
                    {headers:{'content-type': 'multipart/form-data'}});
                this.addFileDialog = false;
                this.fileSelected = null;
                showSnackbar(this.snackbar, request.data.message, 'success', 5000);
                await this.getFiles();
            } catch (e) {
                showSnackbar(this.snackbar, e.response.data.message, 'alert', 8000);
            }
        },

        deleteFile: async function(file){
            try {
                let request = await axios.delete(route('api.certifications.destroy', {certification: file}));
                this.deleteFileDialog = false;
                showSnackbar(this.snackbar, request.data.message, 'success');
                await this.getFiles();
            } catch (e) {
                showSnackbar(this.snackbar, e.response.data.message, 'red', 5000);
            }
        },

        confirmDeleteFile: function (file) {
            this.deletedFileId = file.id;
            this.deleteFileDialog = true;
        },

    },
}
</script>
