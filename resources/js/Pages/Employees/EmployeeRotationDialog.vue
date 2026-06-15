<script setup>
import { useForm } from '@inertiajs/vue3';
import { ArrowLeftRight } from '@lucide/vue';
import { watch } from 'vue';
import FormField from '@/Components/FormField.vue';
import { useBranchDepartments } from '@/composables/useBranchDepartments';
import { today } from '@/lib/date';

const props = defineProps({
    employee: { type: Object, default: null },
    branches: { type: Array, default: () => [] },
    positions: { type: Array, default: () => [] },
    departments: { type: Array, default: () => [] },
    isAdmin: { type: Boolean, default: false },
});

const open = defineModel({ type: Boolean, default: false });

const form = useForm({
    branch_id: null,
    position_id: null,
    department_id: null,
    rotation_date: today(),
    reason: '',
});

const { branchDepartments } = useBranchDepartments({
    getBranchId: () => form.branch_id,
    getDepartmentId: () => form.department_id,
    setDepartmentId: (value) => { form.department_id = value; },
    getDepartments: () => props.departments,
});

watch(open, (visible) => {
    if (!visible || !props.employee) {
        return;
    }
    const employee = props.employee;
    form.branch_id = employee.branch_id ? Number(employee.branch_id) : null;
    form.position_id = employee.position_id ? Number(employee.position_id) : null;
    form.department_id = employee.department_id ? Number(employee.department_id) : null;
    form.rotation_date = today();
    form.reason = '';
    form.clearErrors();
});

function submit() {
    form.post(route('employees.rotate', props.employee.id), {
        onSuccess: () => {
            open.value = false;
            form.reset();
        },
    });
}
</script>

<template>
    <v-dialog v-model="open" max-width="620px" persistent>
        <v-card class="rounded-xl overflow-hidden" elevation="8">
            <div style="background: #009cf1; padding: 24px 28px;">
                <div class="d-flex align-center justify-space-between">
                    <div class="d-flex align-center">
                        <v-avatar size="42" rounded="lg" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(4px);">
                            <ArrowLeftRight style="width: 22px; height: 22px; color: white;" />
                        </v-avatar>
                        <div class="ml-4">
                            <div style="color: rgba(255,255,255,0.7); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em;">
                                Ротатсияи кадрҳо
                            </div>
                            <div style="color: white; font-size: 1.1rem; font-weight: 800;">
                                {{ employee?.full_name }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <v-card-text class="pa-6 overflow-y-auto" style="max-height: 60vh;">
                <v-form class="app-form" @submit.prevent="submit">
                    <FormField label="Филиали нав" required>
                        <v-select
                            v-model="form.branch_id"
                            :items="branches"
                            item-title="name"
                            item-value="id"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            hide-details="auto"
                            :error-messages="form.errors.branch_id"
                            :disabled="!isAdmin"
                        />
                    </FormField>

                    <FormField label="Вазифаи нав" required class="mt-4">
                        <v-autocomplete
                            v-model="form.position_id"
                            :items="positions"
                            item-title="name"
                            item-value="id"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            hide-details="auto"
                            :error-messages="form.errors.position_id"
                        />
                    </FormField>

                    <FormField label="Шуъбаи нав" class="mt-4">
                        <v-autocomplete
                            v-model="form.department_id"
                            :items="branchDepartments"
                            item-title="name"
                            item-value="id"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            hide-details="auto"
                            clearable
                            :no-data-text="form.branch_id ? 'Шуъбаҳо ёфт нашуданд' : 'Аввал филиалро интихоб кунед'"
                            :error-messages="form.errors.department_id"
                        />
                    </FormField>

                    <FormField label="Санаи ротатсия" required class="mt-4">
                        <v-text-field
                            v-model="form.rotation_date"
                            type="date"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            hide-details="auto"
                            :error-messages="form.errors.rotation_date"
                        />
                    </FormField>

                    <FormField label="Сабаб / Асоси ротатсия" class="mt-4">
                        <v-textarea
                            v-model="form.reason"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            rows="3"
                            hide-details="auto"
                            :error-messages="form.errors.reason"
                        />
                    </FormField>
                </v-form>
            </v-card-text>

            <v-divider />

            <v-card-actions class="pa-5 d-flex justify-end" style="gap: 12px;">
                <v-btn variant="tonal" color="grey" rounded="lg" size="large" :disabled="form.processing" class="px-6 font-weight-bold" @click="open = false">
                    Бекор кардан
                </v-btn>
                <v-btn
                    color="indigo"
                    variant="flat"
                    rounded="lg"
                    size="large"
                    :loading="form.processing"
                    class="px-6 font-weight-bold text-white"
                    style="box-shadow: 0 8px 20px -6px rgba(79, 70, 229, 0.4);"
                    @click="submit"
                >
                    <template #prepend>
                        <ArrowLeftRight style="width: 18px; height: 18px;" />
                    </template>
                    Иҷрои ротатсия
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>
