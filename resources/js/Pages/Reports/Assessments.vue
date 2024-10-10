<template>
    <AuthenticatedLayout>
        <Snackbar :timeout="snackbar.timeout" :text="snackbar.text" :type="snackbar.type"
                  :show="snackbar.status" @closeSnackbar="snackbar.status = false"></Snackbar>

        <v-container fluid>
            <div class="d-flex flex-column align-end mb-2" id="malparido">
                <h2 class="align-self-start">Gestionar respuestas de evaluación</h2>
            </div>

            <v-toolbar
                dark
                color="primario"
                class="mb-1"
                height="auto"
            >
                <v-row class="py-3">
                    <v-col cols="3" >
                        <v-autocomplete
                            v-model="assessmentPeriod"
                            flat
                            solo-inverted
                            hide-details
                            :items="assessmentPeriods"
                            :item-text="(assessmentPeriod)=> capitalize(assessmentPeriod.name)"
                            :item-value="(assessmentPeriod)=> (assessmentPeriod.id)"
                            prepend-inner-icon="mdi-home-search"
                            label="Periodo de evaluación"
                        ></v-autocomplete>
                    </v-col>

                    <v-col cols="3" >
                        <v-autocomplete
                            v-model="dependency"
                            flat
                            solo-inverted
                            hide-details
                            :items="dependencies"
                            :item-text="(depedency)=> capitalize(depedency.name)"
                            item-value="identifier"
                            prepend-inner-icon="mdi-home-search"
                            label="Dependencia"
                        ></v-autocomplete>
                    </v-col>

                    <v-col cols="3">
                        <v-autocomplete
                            v-model="functionary"
                            flat
                            solo-inverted
                            hide-details
                            :items="filteredFunctionaries"
                            :item-text="(functionary)=> capitalize(functionary.name)"
                            item-value="user_id"
                            prepend-inner-icon="mdi-account-search"
                            label="Funcionario"
                        ></v-autocomplete>
                    </v-col>

                    <v-col cols="2">
                        <v-select
                            v-model="role"
                            flat
                            solo-inverted
                            hide-details
                            :items="roles"
                            item-text="id"
                            item-value="name"
                            prepend-inner-icon="mdi-eye-settings"
                            label="Rol"
                        ></v-select>
                    </v-col>

                    <v-col cols="1">
                        <v-tooltip top>
                            <template v-slot:activator="{ on, attrs }">
                                <v-btn
                                    v-on="on"
                                    v-bind="attrs"
                                    icon
                                    class="mr-2 secundario--text"
                                    @click="downloadExcel()"
                                >
                                    <v-icon>
                                        mdi-file-excel
                                    </v-icon>
                                </v-btn>
                            </template>
                            <span>Exportar resultados actuales a Excel</span>
                        </v-tooltip>
                    </v-col>
                </v-row>
            </v-toolbar>

            <!--Inicia tabla-->
            <v-card>
                <v-data-table
                    :search="search"
                    loading-text="Cargando, por favor espere..."
                    :no-data-text="noDataText"
                    :loading="isLoading"
                    :headers="headers"
                    :items="filteredGrades"
                    :items-per-page="20"
                    :footer-props="{
                        'items-per-page-options': [20,50,100,-1]
                    }"
                    class="elevation-1"
                >

                    <template
                        v-for="(competence,index) in competences"
                        v-slot:[`item.c${index+1}`]="{ item }"
                    >
                        {{item[`c${index+1}`]}}
                    </template>

                    <template v-slot:item.graph="{ item }">
                        <v-tooltip top
                        >
                            <template v-slot:activator="{on,attrs}">
                                <v-icon
                                    v-bind="attrs"
                                    v-on="on"
                                    class="mr-2 primario--text"
                                    @click="setDialogToShowChart(item)"
                                >
                                    mdi-chart-line
                                </v-icon>
                            </template>
                            <span>Graficar resultados</span>
                        </v-tooltip>
                    </template>

                    <template v-slot:item.op_answers="{ item }">
                        <v-tooltip top
                        >
                            <template v-slot:activator="{on,attrs}">
                                <v-icon
                                    v-bind="attrs"
                                    v-on="on"
                                    class="mr-2 primario--text"
                                    @click="setDialogToShowOpenAnswers(item)"
                                >
                                    mdi-text-box
                                </v-icon>
                            </template>
                            <span>Visualizar comentarios</span>
                        </v-tooltip>
                    </template>

                </v-data-table>
            </v-card>
        </v-container>

<!--        //Respuestas abiertas-->
        <v-dialog
            v-model="showOpenAnswersDialog"
            persistent
        >
            <v-card>
                <v-card-text v-if="openAnswers.length > 0">
                    <h2 class="black--text pt-5" style="text-align: center"> Visualizando comentarios hacia el funcionario: {{ this.capitalize(this.openAnswersFunctionary) }}</h2>
                        <div v-for="question in openAnswers" class="mt-3">
                            <h4 class="black--text pt-3"> Pregunta: </h4>
                            <h4 style="font-weight: bold">{{question.name}}</h4>
                            <div style="margin-left: 20px">
                                <div v-for="person in question.answers" class="mt-3">
                                    <h4 class="black--text"> {{person.name}} - ({{person.role}}): </h4>
                                        <h4> {{person.answer}}</h4>
                                </div>
                            </div>
                        </div>
                </v-card-text>

                <v-card-text v-else>
                    <h2 class="black--text pt-5" style="text-align: center"> No hay comentarios disponibles para este funcionario</h2>
                </v-card-text>

                <v-card-actions>
                    <v-btn
                        color="primario"
                        class="grey--text text--lighten-4"
                        @click="setDialogToCancelOpenAnswers()"
                    >
                        Salir
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>


        <!--Canvas para la gráfica-->
        <v-dialog
            v-model="showChartDialog"
            persistent
        >
            <v-card>
                <v-card-text>
                    <h2 class="black--text pt-5" style="text-align: center"> Visualizando al funcionario: {{this.graphFunctionary.name}}</h2>
                </v-card-text>

                <v-container style="position: relative; height:60vh; width:90vw; background: #FAF9F6">
                <canvas id="graph"></canvas>
                </v-container>

                <h5 class="gray--text" style="text-align: left; margin-left: 60px; margin-bottom: 5px"> Puedes dar click izquierdo sobre la leyenda de la linea de tendencia que desees ocultar </h5>

                <v-card-actions>
                    <v-btn
                        color="primario"
                        class="white--text"
                        @click="confirmSavePDF = true"
                        :disabled="!graphFunctionary"
                    >
                        Descargar reporte en PDF
                    </v-btn>

                    <v-btn
                        color="primario"
                        class="grey--text text--lighten-4"
                        @click="setDialogToCancelChart()"
                    >
                        Salir
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!--Confirmar guardar PDF-->
        <confirm-dialog
            :show="confirmSavePDF"
            @canceled-dialog="confirmSavePDF = false"
            @confirmed-dialog="getPDF()"
        >
            <template v-slot:title>
                Reporte en PDF
            </template>

            Ahora serás redirigido a la pantalla para guardar el PDF. Allí, lo único que debes hacer es darle click al botón de
            <strong class="black--text"> Guardar </strong> en la parte inferior derecha de tu pantalla

            <template v-slot:confirm-button-text>
                Descargar PDF
            </template>
        </confirm-dialog>



    </AuthenticatedLayout>
</template>

<script>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import {InertiaLink} from "@inertiajs/inertia-vue";
import {prepareErrorText, showSnackbar} from "@/HelperFunctions"
import ConfirmDialog from "@/Components/ConfirmDialog";
import Form from "@/models/Form";
import Snackbar from "@/Components/Snackbar";
import Chart from "chart.js/auto"
import Question from "@/models/Question";
import Papa from 'papaparse';

export default {
    components: {
        ConfirmDialog,
        AuthenticatedLayout,
        InertiaLink,
        Snackbar,
    },
    data: () => {
        return {
            sheet: false,
            //Table info
            search: '',
            headers: [],
            //Display data
            assessments: [],
            assessmentPeriod: '',
            assessmentPeriods: [],
            competences: [],
            dependency: '',
            dependencies:[],
            functionary: '',
            functionaries:[],
            grades: [],
            responseIdeals: [],
            role: "",
            roles: [],
            openAnswers: [],
            //selected
            graphFunctionary:'',
            graphFunctionaryDatasets: [],
            openAnswersFunctionary:'',
            snackbar: {
                text: "",
                type: 'alert',
                status: false,
                timeout: 2000,
            },
            //Snackbars
            showChartDialog: false,
            showOpenAnswersDialog: false,


            //Dialogs
            isLoading: true,
            confirmSavePDF: false,
            isUserAdmin: true,
            noDataText: 'Para comenzar, selecciona un periodo de evaluación y la dependencia que deseas visualizar',

            //Graph data
            chartColors: [{role: 'autoevaluación', name: 'blue'}, {role: 'jefe', name: 'red'}, {role: 'par', name: 'green'},
                {role: 'cliente interno', name: 'purple'}, {role: 'cliente externo', name: '#00BFA5'}, {role: 'promedio final', name: 'black'}],
            datasets:[]
        }
    },

    props: {
        propsDependencies: Array,
        assessmentPeriodsArray: Array,
        token: String
    },

    async created() {
        await this.checkUser();
        await this.getTableHeaders();
        await this.getAssessmentPeriods();
        await this.getDependencies();
        await this.getFunctionaries();
        await this.getRoles();
        await this.getResponseIdeals();
        await this.getIndividualGrades();
        await this.getAggregateGrades();
        this.isLoading = false;
    },

    methods: {
        checkUser: function (){
            if (this.propsDependencies !== undefined){
                this.isUserAdmin = false;
                console.log(this.propsDependencies);
            }
        },

        getTableHeaders: async function (){
            this.headers.push({text:"Funcionario", value:"name", width: "15%"})
            this.headers.push({text:"Posición", value:"position_name", width: "15%"})
            this.headers.push({text:"Dependencia", value:"dependency_name", width: "10%"})
            this.headers.push({text:"Rol", value:"role", width: "10%"})
            await this.getCompetences();
            this.headers.push({text:"Fecha de envío", value:"submitted_at", width: "15%"})
            this.headers.push({text:"Graficar resultados", value:"graph", width: "15%", sortable:false})
            this.headers.push({text:"Visualizar comentarios", value:"op_answers", width: "15%", sortable:false})
        },

        getCompetences: async function () {
            let request = await axios.get(route('api.competences.index'));
            console.log(request.data);
            this.competences = request.data;
            this.competences.forEach(competence =>{
                this.headers.push({text:competence.name, value:`c${competence.position}`, sortable:false})
            });
        },

        getAssessmentPeriods: async function () {
            let request = await axios.get(route('api.assessmentPeriods.index'));
            this.assessmentPeriods = request.data;
        },

        getDependencies: async function (){
            let request = await axios.get(route('api.dependencies.index', {assessmentPeriodId: this.assessmentPeriod}));
            console.log(request.data, "dependencies");
            this.dependencies = this.sortArrayAlphabetically(request.data);
            this.dependencies = this.dependencies.filter(dependency => {
                return dependency.functionaries_from_dependency.length > 0;
            })
            if (!this.isUserAdmin){
                this.dependencies = this.dependencies.filter (dependency => {
                    return this.propsDependencies.includes(dependency.identifier)
                })
                return this.dependencies
            }
            this.dependencies.unshift({name: 'Todas las dependencias', identifier:''})
        },

        getFunctionaries: async function (){
            let request = await axios.get(route('api.functionaries.index', {assessmentPeriodId:this.assessmentPeriod, report: 'yes'}));
            this.functionaries = request.data;
            console.log(this.functionaries, 'Todos los funcionarios');
        },

        matchProperty: function (array, propertyPath, reference) {
            return array.filter((item) => {
                const propertyArr = propertyPath.split(".");
                const propertyValue = propertyArr.reduce((obj, key) => {
                    return obj && obj[key];
                }, item);
                return propertyValue === reference;
            });
        },

        async getIndividualGrades(){
            let url = route('api.answers.index',{assessmentPeriodId: this.assessmentPeriod});
            let request = await axios.get(url);
            let individualGrades = request.data;
            console.log(request.data, "Individual grades");

            individualGrades.forEach(grade => {
                this.grades.push(grade);
            })
        },

        async getAggregateGrades(){
            let url = route('api.answers.aggregateGrades', {assessmentPeriodId: this.assessmentPeriod});
            let request = await axios.get(url);
            let aggregateGrades = request.data;
            console.log(aggregateGrades, 'promedios finales');
            aggregateGrades.forEach(grade =>{
                this.grades.push(grade);
            })
        },

        getOpenAnswers: async function (functionaryUserId){
            let url = route('api.answers.openAnswers', {functionaryUserId, assessmentPeriodId: this.assessmentPeriod});
            let request = await axios.get(url);
            this.openAnswers = request.data;
            console.log(this.openAnswers, 'openAnswers');
        },

        async getPDF(){

            this.confirmSavePDF = false;
            let base64Graph =  document.getElementById('graph').toDataURL('image/png')

            let functionaryGrades = this.grades.filter(grade => {
                return grade.user_id === this.graphFunctionary.user_id
            });

            console.log(functionaryGrades);
            let labels = this.competences.map((competence) => {return competence.name});
            var winName='MyWindow';
            var winURL= route('reports.assessmentPDF');
            var windowOption='resizable=yes,height=600,width=800,location=0,menubar=0,scrollbars=1';
            var params = { _token: this.token,
                assessmentPeriodId: this.assessmentPeriod,
                labels: JSON.stringify(labels),
                functionaryUserId: this.graphFunctionary.user_id,
                functionaryName: this.graphFunctionary.name,
                graph:base64Graph,
                grades: JSON.stringify(functionaryGrades),
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

        downloadExcel (){
            if (this.filteredGrades.length === 0){
                showSnackbar(this.snackbar, "No hay datos para guardar", 'alert');
            }

            let excelInfo = this.filteredGrades.map(item =>{
                let competencesData = {}
                this.competences.forEach((competence, index) =>{
                    competencesData[competence.name] = item[`c${competence.position}`].toLocaleString("pt-BR")
                })
                return {
                    Funcionario :item.name,
                    Posición: item.position_name,
                    Dependencia: item.dependency_name,
                    rol: item.role,
                    ...competencesData
                }
            })
            let csv = Papa.unparse(excelInfo, {delimiter:';'});
            var csvData = new Blob(["\uFEFF"+csv], {type: 'text/csv;charset=utf-8;'});
            var csvURL =  null;
            if (navigator.msSaveBlob)
            {
                csvURL = navigator.msSaveBlob(csvData, 'Resultados_Evaluación.csv');
            }
            else
            {
                csvURL = window.URL.createObjectURL(csvData);
            }
            var tempLink = document.createElement('a');
            tempLink.href = csvURL;
            tempLink.setAttribute('download', 'Resultados_Evaluación.csv');
            tempLink.click();
        },

        getGraph(){

            let graph = document.getElementById("graph").getContext("2d");

            this.chart = new Chart(graph, {
                type:"line",
                data:{
                    labels: this.competences.map(competence => competence.name),
                    datasets: this.datasets
                },
                options: {
                    plugins: {
                        filler: {
                            propagate: false
                        },
                    },
                    layout:{
                        padding:{
                            left: 0,
                            right: 0,
                            top: 0,
                            bottom: 0
                        }
                    },
                    legend: {
                        display: true,
                        /*   labels:{
                               padding:20
                           },*/
                        position: "bottom"
                    },
                    responsive: true,
                    tooltips: {
                        mode: "index",
                        intersect: false
                    },
                    hover: {
                        mode: "nearest",
                        intersect: false
                    },

                    scales: {
                        x:
                            {
                                display: true,
                                title: {
                                    display: true,
                                    text: 'Competencias',
                                    color: 'black',
                                    font: {
                                        size: 15,
                                        weight: 'bold',
                                        lineHeight: 1.2,
                                    },
                                },
                                position: 'top'
                            }
                        ,
                        y:
                            {
                                min: 0,
                                max: 5.4,
                                display: true,

                                title: {
                                    display: true,
                                    text: 'Valores obtenidos',
                                    color: 'black',
                                    font: {
                                        size: 15,
                                        weight: 'bold',
                                        lineHeight: 1.2,
                                    },
                                },

                                ticks:{
                                    callback: (value, index, values) => (index == (values.length-1)) ? undefined : value,
                                },
                            }
                    }
                },
            })
        },

        getRoles (){
            this.roles = [{id:  'Todos los roles', name: ''},{id: 'jefe', name: 'jefe'},
                {id: 'par', name: 'par'}, {id: 'autoevaluación', name: 'autoevaluación'},
                {id: 'cliente interno', name: 'cliente interno'}, {id: 'cliente externo', name: 'cliente externo'},
                {id: 'promedio final', name:'promedio final'}]
        },

        async getResponseIdeals() {
            let url = route('api.responseIdeals.index');
            let request = await axios.get(url);
            this.responseIdeals = request.data;
            console.log(this.responseIdeals, 'ideales de respuesta');
        },

        capitalize($field){
            return $field.toLowerCase().split(' ').map((word) => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
        },

        sortArrayAlphabetically(array){
            return array.sort( (p1, p2) =>
                (p1.name > p2.name) ? 1 : (p1.name > p2.name) ? -1 : 0);
        },

        getFilteredGradesByDependency(grades = null) {

            if (grades === null) {
                grades = this.grades
            }
            return grades.filter((grade) => {
                let doesGradeHaveDependency = false;
                if (grade.dependency_identifier === this.dependency) {
                    doesGradeHaveDependency = true;
                }
                return doesGradeHaveDependency;
            });
        },

        getFilteredGradesByFunctionary(grades = null) {
            if (grades === null) {
                grades = this.grades;
            }
            return this.matchProperty(grades, 'user_id', this.functionary)
        },

        getFilteredGradesByRole(grades = null) {
            if (grades === null) {
                grades = this.grades;
            }
            return this.matchProperty(grades, 'role', this.role)
        },

        mapResponseIdealDataset(functionary){
            let positionResponseIdeal = this.responseIdeals.filter(responseIdeal =>{
                return responseIdeal.name === functionary.position_name;
            })[0];

            this.datasets.unshift({
                label: `Nivel Esperado (${functionary.position_name})`,
                data: positionResponseIdeal.response.map(competence => competence.value),
                backgroundColor: 'orange',
                borderColor: 'orange',
                borderWidth: 2,
                borderDash: [5, 5],
            })
        },

        async mapRoleDatasets(functionary){
          let functionaryGrades = this.grades.filter(grade => {
              return grade.user_id === functionary.user_id
          });

          console.log(functionaryGrades, 'functionaryGrades');


          functionaryGrades.forEach(grade => {
              let gradeDataset = [];
              let borderWidth = 3;
              if(grade.role === "promedio final"){
                  borderWidth = 7;
              }
              let color = this.chartColors.find(color => color.role === grade.role);

              //For each functionary role, we have to create the dataset that we will show on the graph
              for (let i=1; i < 7; i++){
                  gradeDataset.push(grade[`c${i}`]);
              }
              //Now that we have the dataset, now we have to add it to the graph and style it too
              this.datasets.push({
                  label: this.capitalize(grade.role),
                  data: gradeDataset,
                  backgroundColor: color.name,
                  borderColor: color.name,
                  borderWidth: borderWidth
              })

          });
        },

        async setDialogToShowChart(functionary){
            this.showChartDialog = true
            this.graphFunctionary= functionary;
            this.mapResponseIdealDataset(functionary);
            await this.mapRoleDatasets(functionary);
            this.getGraph();
        },

        setDialogToCancelChart (){
            this.showChartDialog = false
            this.chart.destroy();
            this.datasets = []
        },

        async setDialogToShowOpenAnswers(functionary){
            this.openAnswersFunctionary = functionary.name;
            this.showOpenAnswersDialog = true
            console.log(functionary, "info del funcionario para preguntas abiertas")
            await this.getOpenAnswers(functionary.user_id);
        },

        setDialogToCancelOpenAnswers (){
            this.showOpenAnswersDialog = false;
            this.openAnswers = [];
        },

        addAllElementSelectionItem(model, text) {
            model.unshift({user_id: '', name: text});
        },
    },

    computed: {

        filteredGrades() {
            let finalGrades = this.grades;
            // if (!this.isUserAdmin){
            //     finalGrades = this.getFilteredGradesByDependency(finalGrades);
            // }
            // else {
            //     if (this.dependency !== '') {
            //         finalGrades = this.getFilteredGradesByDependency(finalGrades);
            //     }
            // }
             if (this.dependency !== '') {
                 finalGrades = this.getFilteredGradesByDependency(finalGrades);
             }
             if (this.functionary !== '') {
                finalGrades = this.getFilteredGradesByFunctionary(finalGrades);
             }
             if (this.role !== '') {
                finalGrades = this.getFilteredGradesByRole(finalGrades);
             }
            return finalGrades;
        },


        filteredFunctionaries(){
            let finalFunctionaries = this.functionaries;
            let finalGrades = this.grades;

            if (this.dependency !== '') {
                finalGrades = this.getFilteredGradesByDependency();
                finalFunctionaries = finalFunctionaries.filter((functionary) => {
                    return finalGrades.some((grade) => grade.user_id == functionary.user_id)
                });
            }
            this.addAllElementSelectionItem(finalFunctionaries, 'Todos los funcionarios');
            return finalFunctionaries;
        }
    },

    watch:{
        async assessmentPeriod(){
            await this.getAssessmentPeriods();
            await this.getDependencies();
            await this.getFunctionaries();
            await this.getRoles();
            await this.getIndividualGrades();
            await this.getAggregateGrades();
        }
    }
}
</script>
