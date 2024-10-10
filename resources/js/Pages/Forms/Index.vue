<template>
    <AuthenticatedLayout>
        <Snackbar :timeout="snackbar.timeout" :text="snackbar.text" :type="snackbar.type"
                  :show="snackbar.status" @closeSnackbar="snackbar.status = false"></Snackbar>

        <v-container>
            <div class="d-flex flex-column align-end mb-5">
                <h2 class="align-self-start">Gestionar formularios</h2>
                <div class="d-flex justify-end mt-5">
                    <v-bottom-sheet v-model="sheet">
                        <template v-slot:activator="{ on, attrs }">
                            <v-btn
                                class="mr-3"
                                color="red"
                                dark
                                v-bind="attrs"
                                v-on="on"
                            >
                                Otras opciones
                            </v-btn>
                        </template>
                        <v-list>
                            <v-subheader>Menú de otras opciones</v-subheader>
                            <v-list-item
                                @click="openMigrateFormsDialog"
                            >
                                <v-list-item-avatar>
                                    <v-icon>
                                        mdi-rotate-right
                                    </v-icon>
                                </v-list-item-avatar>
                                <v-list-item-title>Migrar formularios de periodos anteriores</v-list-item-title>
                            </v-list-item>
                            <v-list-item
                                @click="getFormsWithoutQuestions"
                            >
                                <v-list-item-avatar>
                                    <v-avatar
                                        size="32px"
                                        tile
                                    >
                                        <v-icon>
                                            mdi-file-question
                                        </v-icon>
                                    </v-avatar>
                                </v-list-item-avatar>
                                <v-list-item-title>Ver formularios sin preguntas</v-list-item-title>
                            </v-list-item>
                            <v-list-item
                                @click="getAllForms(true)"
                            >
                                <v-list-item-avatar>
                                    <v-avatar
                                        size="32px"
                                        tile
                                    >
                                        <v-icon>
                                            mdi-file-document-outline
                                        </v-icon>
                                    </v-avatar>
                                </v-list-item-avatar>
                                <v-list-item-title>Ver todos los formularios</v-list-item-title>
                            </v-list-item>
                        </v-list>
                    </v-bottom-sheet>
                    <v-btn
                        class="mr-3"
                        @click="openFormDialog('create','othersForm')"
                    >
                        Crear formulario
                    </v-btn>
                </div>
            </div>


            <h3 class="mt-10 mb-5">Formularios para evaluación</h3>
            <v-data-table
                loading-text="Cargando, por favor espere..."
                :loading="isLoading"
                :headers="othersTableHeaders"
                :items="othersForms"
                :items-per-page="20"
                :footer-props="{
                        'items-per-page-options': [20,50,100,-1]
                    }"
                class="elevation-1"
            >
                <template v-slot:item="{ item }">
                    <tr>
                        <td>
                            {{ item.name }}
                        </td>
                        <td>
                            {{ getTableAssessmentPeriod(item.assessmentPeriodId).name }}
                        </td>
                        <td>
                            {{ item.dependencyRole != null ? item.dependencyRole : 'Todos' }}
                        </td>
                        <td>
                            {{ item.position != null ? item.position : 'Todas' }}
                        </td>
                        <td>
                            {{ item.description === '' ? 'No proporcionada' : item.description }}
                        </td>

                        <td class="d-flex" style="gap: 5px">
                            <v-tooltip top>
                            <template v-slot:activator="{on,attrs}">
                            <v-icon
                                v-bind="attrs"
                                v-on="on"
                                class="mr-2 primario--text"
                                @click="openFormDialog('edit','othersForm',item)"
                            >
                                mdi-pencil
                            </v-icon>
                            </template>
                                <span>Editar formulario</span>
                            </v-tooltip>

                            <v-tooltip top>
                                <template v-slot:activator="{on,attrs}">
                            <v-icon
                                v-bind="attrs"
                                v-on="on"
                                class="primario--text"
                                @click="copy(item.id)"
                            >
                                mdi-content-copy
                            </v-icon>
                                </template>
                                <span>Copiar formulario</span>
                            </v-tooltip>

                            <v-tooltip top>
                                <template v-slot:activator="{on,attrs}">
                                    <InertiaLink as="v-icon" class="primario--text"
                                                 v-bind="attrs"
                                                 v-on="on"
                                                 :href="route('forms.show.view',{form:item.id})">
                                        mdi-format-list-bulleted
                                    </InertiaLink>
                                </template>
                                <span>Editar preguntas</span>
                            </v-tooltip>
                            <v-tooltip top>
                                <template v-slot:activator="{on,attrs}">
                            <v-icon
                                v-bind="attrs"
                                v-on="on"
                                class="primario--text"
                                @click="confirmDeleteForm(item)"
                            >
                                mdi-delete
                            </v-icon>
                                </template>
                                <span>Borrar formulario</span>
                            </v-tooltip>
                        </td>
                    </tr>
                </template>
            </v-data-table>
            <!--Acaba tabla-->

            <!------------Seccion de dialogos ---------->

            <!--Crear o editar form -->
            <v-dialog
                v-model="createOthersFormDialog"
                persistent
                max-width="650px"
            >
                <v-card>
                    <v-card-title>
                        <span>
                        </span>
                        <span class="text-h5">Crear un nuevo formulario  (par, jefe, autoevaluación y clientes internos/externos)</span>
                    </v-card-title>
                    <v-card-text>
                        <v-container>
                            <v-row>
                                <v-col cols="12">
                                    <v-text-field
                                        label="Nombre del formulario *"
                                        required
                                        v-model="othersForm.name"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12">
                                    <v-textarea
                                        label="Descripción del formulario *"
                                        required
                                        rows="3"
                                        v-model="othersForm.description"
                                    ></v-textarea>
                                </v-col>
                                <v-col cols="12">
                                    <v-select
                                        color="primario"
                                        v-model="othersForm.assessmentPeriodId"
                                        :items="assessmentPeriods"
                                        label="Periodo de evaluación"
                                        :item-text="(assessmentPeriod)=>assessmentPeriod.name"
                                        :item-value="(assessmentPeriod)=>assessmentPeriod.id"
                                    ></v-select>
                                </v-col>
                                <v-col cols="12">
                                    <v-select
                                        color="primario"
                                        v-model="othersForm.dependencyRole"
                                        :items="roles"
                                        label="Rol"
                                        :item-text="(role)=> role.name"
                                        :item-value="(role)=>role.value"
                                        :disabled="othersForm.assessmentPeriodId === null"
                                    ></v-select>
                                </v-col>
                                <v-col cols="12">
                                    <v-select
                                        color="primario"
                                        v-model="othersForm.position"
                                        :items="positions"
                                        label="Posición"
                                        :item-text="(position)=> this.othersForm.dependencyRole === null ? 'Todas' : position.name"
                                        :item-value="(position)=> this.othersForm.dependencyRole === null ? null : position.name"
                                        :disabled="othersForm.dependencyRole === null"
                                    ></v-select>
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
                            @click="createOthersFormDialog = false"
                        >
                            Cancelar
                        </v-btn>
                        <v-btn
                            color="primario"
                            text
                            @click="createForm('othersForm')"
                        >
                            Guardar cambios
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <v-dialog
                v-model="migrateFormsDialog"
                persistent
                max-width="700"
            >
                <v-card>
                    <v-card-title class="text-h5">
                        Migrar formularios anteriores
                    </v-card-title>
                    <v-card-text>Selecciona el periodo de evaluación del que quieres migrar los formularios
                    </v-card-text>
                    <v-select
                        color="primario"
                        v-model="selectedAssessmentPeriod"
                        :items="assessmentPeriodsMigrateList"
                        label="Selecciona un periodo de evaluación"
                        :item-value="(role)=>role"
                        :item-text="(role)=>role.name"
                        class="pa-6"
                    ></v-select>
                    <v-card-actions>
                        <v-spacer></v-spacer>
                        <v-btn
                            color="primario"
                            text
                            @click="migrateForms(selectedAssessmentPeriod)"
                        >
                            Aceptar
                        </v-btn>
                        <v-btn
                            color="primario"
                            text
                            @click="migrateFormsDialog = false"
                        >
                            Cancelar
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <!--Confirmar borrar form-->
            <confirm-dialog
                :show="deleteFormDialog"
                @canceled-dialog="deleteFormDialog = false"
                @confirmed-dialog="deleteForm(deletedFormId)"
            >
                <template v-slot:title>
                    Estás a punto de eliminar el formulario seleccionado
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
import Form from "@/models/Form";
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
            sheet: false,
            //Table info
            othersTableHeaders: [
                {text: 'Nombre', value: 'name'},
                {text: 'Periodo de evaluación', value: 'assessment_period.name'},
                {text: 'Rol', value: 'dependency_role'},
                {text: 'Posición', value: 'position'},
                {text: 'Descripción del formulario', value: 'description'},
                {text: 'Acciones', value: 'actions', sortable: false},
            ],
            forms: [],
            othersForms: [],
            //data for modals
            academicPeriods: [],
            assessmentPeriods: [],
            serviceAreas: [],
            dependencies: [],
            positions: [],
            roles: [],
            degrees: [],

            //Forms models
            studentForm: new Form(),
            isServiceAreaDisabled: false,
            isAcademicPeriodDisabled: false,
            othersForm: new Form(),
            isRoleDisabled: false,
            isTeacherLadderDisabled: false,
            isUnitDisabled: false,
            formMethod: 'create',
            deletedFormId: 0,

            //Snackbars
            snackbar: {
                text: "",
                type: 'alert',
                status: false,
                timeout: 2000,
            },

            //Dialogs
            deleteFormDialog: false,
            createStudentFormDialog: false,
            editStudentFormDialog: false,
            createOthersFormDialog: false,
            editOthersFormDialog: false,
            migrateFormsDialog: false,
            assessmentPeriodsMigrateList : [],
            selectedAssessmentPeriod: [],
            isLoading: true,

        }
    },
    async created() {
        await this.getAllForms();
        await this.getAssessmentPeriods();
        this.getRoles();
        await this.getPositions();
        this.isLoading = false;
    },

    methods: {

        async getFormsWithoutQuestions () {
            let request = await axios.get(route('api.forms.withoutQuestions'));
            this.forms = Form.createFormsFromArray(request.data);
            this.formatForms();
            showSnackbar(this.snackbar, 'Se han cargado los formularios sin preguntas', 'success');
            this.sheet = false;
        },

        migrateForms: async function (assessmentPeriod) {
            let request = await axios.get(route('api.forms.copyFromPeriod', {assessmentPeriod}));
            await this.getAllForms();
            this.migrateFormsDialog = false;
            showSnackbar(this.snackbar, 'Se han cargado los formularios del periodo de evaluación seleccionado', 'success');
            this.sheet = false;
        },

        getTableAssessmentPeriod: function (assessmentPeriodId) {
            const selectedAssessmentPeriod = this.assessmentPeriods.find(pAssessmentPeriod => pAssessmentPeriod.id == assessmentPeriodId);
            return selectedAssessmentPeriod === undefined ? 'Error al tratar de obtener periodo de evaluación' : selectedAssessmentPeriod;
        },

        setFormAsActive: async function (formId) {
            try {
                let request = await axios.post(route('api.forms.setActive', {'form': formId}));
                this.createOrEditDialog.dialogStatus = false;
                showSnackbar(this.snackbar, request.data.message, 'success');
                this.getAllForms();
            } catch (e) {
                showSnackbar(this.snackbar, prepareErrorText(e), 'alert');
            }
        },

        confirmDeleteForm: function (form) {
            this.deletedFormId = form.id;
            this.deleteFormDialog = true;
        },
        deleteForm: async function (formId) {
            try {
                let request = await axios.delete(route('api.forms.destroy', {form: formId}));
                this.deleteFormDialog = false;
                showSnackbar(this.snackbar, request.data.message, 'success');
                this.getAllForms();
            } catch (e) {
                showSnackbar(this.snackbar, e.response.data.message, 'red', 3000);
            }

        },
        openFormDialog(method, model, form = null) {
            this.formMethod = method;
            if (method === 'edit') {
                this[model] = Form.copy(form);
            } else {
                this[model] = new Form();
            }

            if (model === 'studentForm') {
                this.createStudentFormDialog = true;
            }
            if (model === 'othersForm') {
                console.log(this.othersForm);
                this.createOthersFormDialog = true;
            }
        },

        async openMigrateFormsDialog(){
            this.migrateFormsDialog = true
            let request = await axios.get(route('api.assessmentPeriods.index'));
            let assessmentPeriods = request.data;
            this.assessmentPeriodsMigrateList = assessmentPeriods.filter(assessmentPeriod => {
                return assessmentPeriod.active === 0;
            });
            console.log(this.assessmentPeriodsMigrateList);

        },

        getAllForms: async function (notify = false) {
            let request = await axios.get(route('api.forms.index'));
            console.log(request.data);
            this.forms = Form.createFormsFromArray(request.data);
            this.formatForms();
            if (notify) {
                showSnackbar(this.snackbar, 'Mostrando todos los formularios')
            }
        },
        copy: async function (formId) {
            try {
                await axios.post(route('api.forms.copy', {form: formId}));
                showSnackbar(this.snackbar, 'Formulario copiado exitosamente');
                await this.getAllForms();
            } catch (e) {
                showSnackbar(this.snackbar, e.response.data.message, 'red', 3000);
            }
        },

        getRoles() {
            this.roles = Form.getPossibleRoles();
            console.log(this.roles,'roles')
        },
        async getPositions() {
            let request = await axios.get(route('api.positions.index'))
            this.positions = request.data;
            this.positions.unshift({
                id: null,
                name: "Todas"
            });
            console.log(this.positions, 'positions');
        },

        getAssessmentPeriods: async function () {
            let request = await axios.get(route('api.assessmentPeriods.index'));
            this.assessmentPeriods = request.data;
            this.assessmentPeriods.unshift({
                id: null,
                name: "Todos"
            });
            console.log(this.assessmentPeriods, 'assessment periods');
        },

        formatForms: function () {
            const forms = this.forms;
            this.othersForms = [];
            forms.forEach((form) => {
                this.othersForms.push(form);
            })
        },

        createForm: async function (formModel) {
            if (this[formModel].hasEmptyProperties()) {
                showSnackbar(this.snackbar, 'Debes diligenciar todos los campos obligatorios', 'red', 2000);
                return;
            }
            if (formModel === 'othersForm') {
                this[formModel].type = 'otros';
            }
            const endpoint = route('api.forms.store', {form: this[formModel].id});
            const axiosMethod = 'post';
            let data = this[formModel].toObjectRequest();
            if(data.position === "Todas"){
                data.position = null
            }
            console.log(data);
            try {
                let request = await axios[axiosMethod](endpoint, data);
                if (formModel === 'othersForm') {
                    this.createOthersFormDialog = false;
                }
                showSnackbar(this.snackbar, request.data.message, 'success', 2000);
                await this.getAllForms();
                //Clear form information
                this[formModel] = new Form();

            } catch (e) {
                showSnackbar(this.snackbar, prepareErrorText(e), 'alert', 3000);
            }
        }
    },

    watch: {
        'othersForm.assessmentPeriodId'(newAssessmentPeriodId, oldAssessmentPeriodId) {
            if (newAssessmentPeriodId === null) {
                this.othersForm.dependencyRole = null;
            }
        },
        'othersForm.position'(newPosition, oldAcademicPeriod) {
            if (newPosition === null) {
                this.othersForm.dependencies = [null]
            }
        },
    },

}
</script>
