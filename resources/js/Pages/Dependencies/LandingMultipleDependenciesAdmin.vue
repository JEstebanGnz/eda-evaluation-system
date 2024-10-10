<template>
    <AuthenticatedLayout>
        <!--Snackbars-->
        <v-snackbar
            v-model="snackbar.status"
            :timeout="snackbar.timeout"
            :color="snackbar.color + ' accent-2'"
            top
            right
        >
            {{ snackbar.text }}
            <template v-slot:action="{ attrs }">
                <v-btn
                    text
                    v-bind="attrs"
                    @click="snackbar.status = false"
                >
                    Cerrar
                </v-btn>
            </template>
        </v-snackbar>

        <v-container class="d-flex flex-column justify-center align-center fill-height">
            <div>
                <v-row>
                    <v-col cols="12">
                        <p class="text-h5 text-center">
                            Por favor, selecciona la dependencia que deseas visualizar
                        </p>
                    </v-col>
                    <v-col cols="12">
                        <v-select
                            color="primario"
                            v-model="selectedDependencyIdentifier"
                            :items="dependencies"
                            label="Selecciona una dependencia"
                            :item-value="(role)=>role.identifier"
                            :item-text="(role)=>role.name"
                        ></v-select>
                    </v-col>
                    <v-col cols="12" class="d-flex justify-center">
                        <v-btn
                            color="primario"
                            class="grey--text text--lighten-4"
                            @click="selectDependency"
                        >
                            Seleccionar dependencia
                        </v-btn>
                    </v-col>
                </v-row>
            </div>
        </v-container>

    </AuthenticatedLayout>
</template>

<script>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import {InertiaLink} from "@inertiajs/inertia-vue";
import ConfirmDialog from "@/Components/ConfirmDialog";
import {showSnackbar} from "@/HelperFunctions";

export default {
    components: {
        AuthenticatedLayout,
        InertiaLink,
        ConfirmDialog
    },

    props:{
        dependencies: Array
    },

    data: () => ({
        snackbar: {
            text: "",
            color: 'red',
            status: false,
            timeout: 2000,
        },
        showDialog: false,
        selectedDependencyIdentifier: 0,
    }),

    methods: {

        async selectDependency() {
            if (this.selectedDependencyIdentifier === 0) {
                showSnackbar(this.snackbar, 'Por favor, selecciona una dependencia de la lista', 'red');
                return;
            }
            const url = route('api.dependencies.assessmentStatus', {dependency: this.selectedDependencyIdentifier});
            try {
                window.location.href = url;
            } catch (e) {
                showSnackbar(this.snackbar, e.response.data.message, 'red');
            }
        }
    }
}
</script>
