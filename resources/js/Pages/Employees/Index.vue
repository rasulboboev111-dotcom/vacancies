<script setup>
import { Head } from '@inertiajs/vue3';
import { Users } from '@lucide/vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EmployeesPanel from '@/Pages/Employees/EmployeesPanel.vue';

defineProps({
    employees: { type: Object, required: true },
    branches: { type: Array, required: true },
    types: { type: Array, required: true },
    departments: { type: Array, default: () => [] },
    // Отложенные (группа Inertia "form") — подгружаются после первой отрисовки.
    categories: { type: Array, default: () => [] },
    positions: { type: Array, default: () => [] },
    nationalities: { type: Array, default: () => [] },
    educations: { type: Array, default: () => [] },
    specialties: { type: Array, default: () => [] },
    birthPlaces: { type: Array, default: () => [] },
    filters: { type: Object, required: true },
});
</script>

<template>
    <Head title="Кормандон" />

    <AuthenticatedLayout>
        <template #header>
            <div class="d-flex align-center">
                <Users class="mr-3 text-indigo-accent-2 h-6 w-6" />
                <span>Кормандон</span>
            </div>
        </template>

        <EmployeesPanel
            :employees="employees"
            :branches="branches"
            :types="types"
            :departments="departments"
            :categories="categories"
            :positions="positions"
            :nationalities="nationalities"
            :educations="educations"
            :specialties="specialties"
            :birth-places="birthPlaces"
            :filters="filters"
        />
    </AuthenticatedLayout>
</template>
