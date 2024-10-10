<template>
    <AuthenticatedLayout>
        <Snackbar :timeout="snackbar.timeout" :text="snackbar.text" :type="snackbar.type"
                  :show="snackbar.status" @closeSnackbar="snackbar.status = false"></Snackbar>

        <v-container>
            <div class="d-flex flex-column align-end mb-8">
                <h2 class="align-self-start">Evaluaciones asignadas</h2>

                <div v-if="$page.props.user.customRoleId === 3" class="mt-5">
                    <v-btn
                        v-if="hasFinalReport"
                        color="primario"
                        class="grey--text text--lighten-4"
                        @click="getReport"
                    >
                        Descargar Reporte Final de Evaluación
                    </v-btn>
                </div>

            </div>

            <!--Inicia tabla-->
            <v-data-table
                loading-text="Cargando, por favor espere..."
                :loading="isLoading"
                :headers="headers"
                :items="assessments"
                :items-per-page="20"
                :footer-props="{
                        'items-per-page-options': [20,50,100,-1]
                    }"
                class="elevation-1"
            >
                <template v-slot:item.actions="{ item }">

                    <v-tooltip bottom v-if="item.test === null">
                        <template v-slot:activator="{ on, attrs }">
                            <v-icon
                                v-on="on"
                                v-bind="attrs"
                                class="mr-2 primario--text"
                            >
                                mdi-close-circle-outline
                            </v-icon>
                        </template>
                        <span>No hay una evaluación disponible para este funcionario </span>
                    </v-tooltip>

                    <form :action="route('tests.startTest',{testId: item.test.id})" method="POST"
                          v-else-if="item.pending === 1">
                        <input type="hidden" name="_token" :value="token">
                        <input type="hidden" name="data" :value="JSON.stringify(item)">
                        <v-tooltip bottom>
                            <template v-slot:activator="{ on, attrs }">
                                <v-btn
                                    type="submit"
                                    v-on="on"
                                    v-bind="attrs"
                                    icon
                                    class="mr-2 primario--text"
                                >
                                    <v-icon>
                                        mdi-send
                                    </v-icon>
                                </v-btn>
                            </template>
                            <span>Realizar evaluación</span>
                        </v-tooltip>
                    </form>

                    <v-tooltip bottom v-else>
                        <template v-slot:activator="{ on, attrs }">
                            <v-icon
                                v-on="on"
                                v-bind="attrs"
                                class="mr-2 primario--text"
                            >
                                mdi-check-all
                            </v-icon>
                        </template>
                        <span>Ya has realizado esta evaluación</span>

                    </v-tooltip>
                </template>
            </v-data-table>

            <!--Acaba tabla-->
        </v-container>
    </AuthenticatedLayout>
</template>

<script>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import {InertiaLink} from "@inertiajs/inertia-vue";
import {getCSRFToken, prepareErrorText, showSnackbar} from "@/HelperFunctions"
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
            headers: [
                {text: 'Nombre', value: 'name'},
                {text: 'Dependencia', value: 'dependency_name'},
                {text: 'Tipo de Evaluación', value: 'role'},
                {text: 'Acciones', value: 'actions', sortable: false},
            ],
            assessments: [],
            //token: '',
            //Snackbars
            snackbar: {
                text: "",
                type: 'alert',
                status: false,
                timeout: 2000,
            },
            isLoading: true,
            hasFinalReport: false,
        }
    },
    props: {
        token: String
    },

    async created() {
        await this.getAssessments();
        await this.userHasReportAvailable();
        this.isLoading = false;
    },

    methods: {
        getAssessments: async function () {
            let request = await axios.get(route('api.tests.index'));
            console.log(request.data);
            this.assessments = request.data;
        },

        async getReport(){
            var winName='MyWindow';
            var winURL= route('reports.assessmentPDF');
            var windowOption='resizable=yes,height=600,width=800,location=0,menubar=0,scrollbars=1';
            var params = { _token: this.token,
            };

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

        async userHasReportAvailable(){
            let request = await axios.get(route('reports.assessment.available'));
            this.hasFinalReport = request.data
            console.log(request.data);
        }
    },

}
</script>
