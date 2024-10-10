<template>
    <AuthenticatedLayout>
        <Snackbar :timeout="snackbar.timeout" :text="snackbar.text" :type="snackbar.type"
                  :show="snackbar.status" @closeSnackbar="snackbar.status = false"></Snackbar>

        <v-container>
            <div class="d-flex flex-column align-end mb-8">
                <h2 class="align-self-start">Gestionar notificaciones</h2>
                <div>
                    <v-btn
                        color="primario"
                        class="grey--text text--lighten-4"
                        @click="setReminderDialogToCreateOrEdit('create')"
                    >
                        Crear nueva notificación
                    </v-btn>
                </div>
            </div>

            <!--Inicia tabla-->
            <v-card>
                <v-card-title>
                    <v-text-field
                        v-model="search"
                        append-icon="mdi-magnify"
                        label="Filtrar por nombre de recordatorio"
                        single-line
                        hide-details
                    ></v-text-field>
                </v-card-title>
                <v-data-table
                    :search="search"
                    loading-text="Cargando, por favor espere..."
                    :loading="isLoading"
                    :headers="headers"
                    :items="reminders"
                    :items-per-page="20"
                    :footer-props="{
                        'items-per-page-options': [20,50,100,-1]
                    }"
                    class="elevation-1"
                >

                    <template v-slot:item.reminder_type="{item}">
                        {{item.reminder_type === 'commitment' ? 'Compromiso' : 'Evaluación'}}
                    </template>

                    <template v-slot:item.send_before="{item}">
                        {{item.send_before === 'start' ? 'Empezar evento' : 'Finalizar evento'}}
                    </template>

                    <template v-slot:item.actions="{ item }">
                        <v-icon
                            class="mr-2 primario--text"
                            @click="setReminderDialogToCreateOrEdit('edit',item)"
                        >
                            mdi-pencil
                        </v-icon>

                        <v-icon
                            class="primario--text"
                            @click="confirmDeleteReminder(item)"
                        >
                            mdi-delete
                        </v-icon>
                    </template>
                </v-data-table>
            </v-card>
            <!--Acaba tabla-->

            <!------------Seccion de dialogos ---------->

            <!--Crear o editar posición -->
            <v-dialog
                v-model="createOrEditDialog.dialogStatus"
                persistent
                max-width="650px"
            >
                <v-card>
                    <v-card-title>
                        <span>
                        </span>
                        <span class="text-h5">Crear/Editar notificación</span>
                    </v-card-title>
                    <v-card-text>
                        <v-container>
                            <v-row>
                                <v-col cols="12">
                                    <v-select
                                        label="Tipo de recordatorio"
                                        :items="reminderTypes"
                                        required
                                        v-model="$data[createOrEditDialog.model].reminder_type"
                                        item-text="text"
                                        item-value="name"
                                    ></v-select>
                                </v-col>
                            </v-row>
                            <v-row>
                                <v-col cols="12">
                                    <v-select
                                        label="Enviar antes de"
                                        :items="sendTypes"
                                        required
                                        v-model="$data[createOrEditDialog.model].send_before"
                                        item-text="text"
                                        item-value="name"
                                    ></v-select>
                                </v-col>
                            </v-row>
                            <v-row>
                                <v-text-field
                                    color="primario"
                                    v-model="$data[createOrEditDialog.model].days"
                                    label="Número de días de antelación para enviar el recordatorio"
                                    type="number"
                                    min=1
                                    max="21"
                                    class="mt-3"
                                >
                                </v-text-field>
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

            <!--Confirmar borrar recordatorio-->
            <confirm-dialog
                :show="deleteReminderDialog"
                @canceled-dialog="deleteReminderDialog = false"
                @confirmed-dialog="deleteReminder(deletedReminderId)"
            >
                <template v-slot:title>
                    Estás a punto de eliminar la notificación seleccionada
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
import Training from "@/models/Training";
import Competence from "@/models/Competence";
import Reminder from "@/models/Reminder";

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
                {text: 'Recordatorio', value: 'reminder_type'},
                {text: 'Enviar antes de', value: 'send_before'},
                {text: 'Días de antelación', value: 'days'},
                {text: 'Acciones', value: 'actions', sortable: false},
            ],
            reminders: [],
            reminderTypes:[
                {name: 'commitment', text: 'Compromiso'}
            ],
            sendTypes:[
                {name: 'finish', text: 'Terminar evento'}
            ],

            //AssessmentPeriods models
            newReminder: new Reminder(),
            editedReminder: new Reminder(),
            deletedReminderId: 0,
            //Snackbars
            snackbar: {
                text: "",
                type: 'alert',
                status: false,
                timeout: 2000,
            },
            //Dialogs
            deleteReminderDialog: false,
            createOrEditDialog: {
                model: 'newReminder',
                method: 'createReminder',
                dialogStatus: false,
            },
            isLoading: true,
        }
    },
    async created() {
        await this.getAllReminders();
        this.isLoading = false;
    },

    methods: {

        handleSelectedMethod: function () {
            this[this.createOrEditDialog.method]();
        },

        editReminder: async function () {
            //Verify request
            if (this.editedReminder.hasEmptyProperties()) {
                showSnackbar(this.snackbar, 'Debes diligenciar todos los campos obligatorios', 'red', 2000);
                return;
            }
            //Recollect information
            let data = this.editedReminder.toObjectRequest();

            try {
                let request = await axios.patch(route('api.reminders.update', {'reminder': this.editedReminder.id}), data);
                this.createOrEditDialog.dialogStatus = false;
                showSnackbar(this.snackbar, request.data.message, 'success');
                await this.getAllReminders();
                //Clear role information
                this.editedReminder = new Reminder();
            } catch (e) {
                showSnackbar(this.snackbar, prepareErrorText(e), 'alert');
            }
        },

        confirmDeleteReminder: function (reminder) {
            this.deletedReminderId = reminder.id;
            this.deleteReminderDialog = true;
        },

        deleteReminder: async function (reminder) {
            try {
                let request = await axios.delete(route('api.reminders.destroy', {reminder: reminder}));
                this.deleteReminderDialog = false;
                showSnackbar(this.snackbar, request.data.message, 'success');
                await this.getAllReminders();
            } catch (e) {
                showSnackbar(this.snackbar, e.response.data.message, 'red', 3000);
            }

        },
        getAllReminders: async function () {
            let request = await axios.get(route('api.reminders.index'));
            console.log(request.data);
            this.reminders = request.data;
        },
        setReminderDialogToCreateOrEdit(which, item = null) {
            if (which === 'create') {
                this.createOrEditDialog.method = 'createReminder';
                this.createOrEditDialog.model = 'newReminder';
                this.createOrEditDialog.dialogStatus = true;
            }

            if (which === 'edit') {
                this.editedReminder = Reminder.fromModel(item);
                this.createOrEditDialog.method = 'editReminder';
                this.createOrEditDialog.model = 'editedReminder';
                this.createOrEditDialog.dialogStatus = true;
            }

        },
        createReminder: async function () {

            if (this.newReminder.hasEmptyProperties()) {
                showSnackbar(this.snackbar, 'Debes diligenciar todos los campos obligatorios', 'red', 2000);
                return;
            }

            let data = this.newReminder.toObjectRequest();
            //Clear competence information
            this.newReminder = new Reminder();

            try {
                let request = await axios.post(route('api.reminders.store'), data);
                this.createOrEditDialog.dialogStatus = false;
                showSnackbar(this.snackbar, request.data.message, 'success', 2000);
                await this.getAllReminders();
            } catch (e) {
                showSnackbar(this.snackbar, e.response.data.message, 'alert', 3000);
            }
        },


    },
}
</script>
