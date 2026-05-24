<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
    recent_activities: {
        type: Array,
        required: true,
    },
});

const maleTotal = computed(() => {
    const maleStat = props.stats.gender_stats.find(s => s.gender === 'Мужской');
    return maleStat ? parseInt(maleStat.count) : 0;
});

const femaleTotal = computed(() => {
    const femaleStat = props.stats.gender_stats.find(s => s.gender === 'Женский');
    return femaleStat ? parseInt(femaleStat.count) : 0;
});

const genderRatio = computed(() => {
    const total = maleTotal.value + femaleTotal.value;
    if (total === 0) return 50;
    return Math.round((maleTotal.value / total) * 100);
});

// Colors list for cards/distributions
const colors = ['#6200EE', '#03DAC6', '#FF007F', '#FFB300', '#4CAF50', '#00BCD4'];
</script>

<template>
    <Head title="Дашборд" />

    <AuthenticatedLayout>
        <template #header>
            Панель управления
        </template>

        <!-- KPI Cards -->
        <v-row class="mb-6">
            <v-col cols="12" sm="6" md="3">
                <v-card elevation="2" class="rounded-xl overflow-hidden pa-4 relative border-l-4 border-primary">
                    <div class="d-flex justify-between align-center">
                        <div>
                            <span class="text-subtitle-2 text-grey-darken-1 font-weight-medium">Сотрудники</span>
                            <h2 class="text-h4 font-weight-bold mt-1 text-primary">{{ stats.total_employees }}</h2>
                        </div>
                        <v-avatar color="primary-lighten-5" rounded="lg" size="50">
                            <v-icon icon="mdi-account-group" color="primary" size="large"></v-icon>
                        </v-avatar>
                    </div>
                </v-card>
            </v-col>

            <v-col cols="12" sm="6" md="3">
                <v-card elevation="2" class="rounded-xl overflow-hidden pa-4 relative border-l-4 border-info">
                    <div class="d-flex justify-between align-center">
                        <div>
                            <span class="text-subtitle-2 text-grey-darken-1 font-weight-medium">Филиалы</span>
                            <h2 class="text-h4 font-weight-bold mt-1 text-info">{{ stats.total_branches }}</h2>
                        </div>
                        <v-avatar color="info-lighten-5" rounded="lg" size="50">
                            <v-icon icon="mdi-office-building" color="info" size="large"></v-icon>
                        </v-avatar>
                    </div>
                </v-card>
            </v-col>

            <v-col cols="12" sm="6" md="3">
                <v-card elevation="2" class="rounded-xl overflow-hidden pa-4 relative border-l-4 border-success">
                    <div class="d-flex justify-between align-center">
                        <div>
                            <span class="text-subtitle-2 text-grey-darken-1 font-weight-medium">Мужчины</span>
                            <h2 class="text-h4 font-weight-bold mt-1 text-success">{{ maleTotal }}</h2>
                        </div>
                        <v-avatar color="success-lighten-5" rounded="lg" size="50">
                            <v-icon icon="mdi-gender-male" color="success" size="large"></v-icon>
                        </v-avatar>
                    </div>
                </v-card>
            </v-col>

            <v-col cols="12" sm="6" md="3">
                <v-card elevation="2" class="rounded-xl overflow-hidden pa-4 relative border-l-4 border-error">
                    <div class="d-flex justify-between align-center">
                        <div>
                            <span class="text-subtitle-2 text-grey-darken-1 font-weight-medium">Женщины</span>
                            <h2 class="text-h4 font-weight-bold mt-1 text-error">{{ femaleTotal }}</h2>
                        </div>
                        <v-avatar color="error-lighten-5" rounded="lg" size="50">
                            <v-icon icon="mdi-gender-female" color="error" size="large"></v-icon>
                        </v-avatar>
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <!-- Branch Stats & Gender Balance -->
        <v-row class="mb-6">
            <!-- Branch Wise Count -->
            <v-col cols="12" md="8">
                <v-card elevation="2" class="rounded-xl pa-5 h-100">
                    <v-card-title class="px-0 pt-0 pb-4 font-weight-bold text-h6">
                        Распределение по филиалам
                    </v-card-title>
                    <v-divider class="mb-4"></v-divider>
                    
                    <v-list class="pa-0">
                        <div v-for="(branch, index) in stats.branch_stats" :key="branch.id" class="mb-4">
                            <div class="d-flex justify-space-between align-center mb-1">
                                <div class="font-weight-medium text-body-1">
                                    {{ branch.name }}
                                    <v-chip size="x-small" color="primary" class="ml-2 font-weight-medium">{{ branch.code }}</v-chip>
                                </div>
                                <div class="text-body-1 font-weight-bold">
                                    {{ branch.employees_count }} чел.
                                </div>
                            </div>
                            <v-progress-linear
                                :model-value="(branch.employees_count / (stats.total_employees || 1)) * 100"
                                color="primary"
                                height="8"
                                rounded
                                class="mb-1"
                            ></v-progress-linear>
                            <div class="d-flex justify-start text-caption text-grey">
                                <span class="mr-3">
                                    <v-icon icon="mdi-gender-male" size="small" color="success" class="mr-1"></v-icon>
                                    {{ branch.male_count }}
                                </span>
                                <span>
                                    <v-icon icon="mdi-gender-female" size="small" color="error" class="mr-1"></v-icon>
                                    {{ branch.female_count }}
                                </span>
                            </div>
                        </div>
                    </v-list>
                </v-card>
            </v-col>

            <!-- Gender Balance Progress -->
            <v-col cols="12" md="4">
                <v-card elevation="2" class="rounded-xl pa-5 h-100 d-flex flex-column">
                    <v-card-title class="px-0 pt-0 pb-4 font-weight-bold text-h6">
                        Гендерный баланс
                    </v-card-title>
                    <v-divider class="mb-4"></v-divider>
                    
                    <div class="flex-grow-1 d-flex flex-column justify-center align-center py-6">
                        <v-progress-circular
                            :model-value="genderRatio"
                            :size="150"
                            :width="15"
                            color="success"
                            bg-color="error"
                            class="mb-4"
                        >
                            <span class="text-h5 font-weight-bold">{{ genderRatio }}%</span>
                        </v-progress-circular>

                        <div class="w-100 mt-4 px-4">
                            <div class="d-flex justify-space-between mb-2">
                                <span class="d-flex align-center text-body-2 font-weight-medium">
                                    <v-icon icon="mdi-circle" color="success" size="x-small" class="mr-2"></v-icon>
                                    Мужчины
                                </span>
                                <span class="font-weight-bold text-body-2">{{ genderRatio }}%</span>
                            </div>
                            <div class="d-flex justify-space-between">
                                <span class="d-flex align-center text-body-2 font-weight-medium">
                                    <v-icon icon="mdi-circle" color="error" size="x-small" class="mr-2"></v-icon>
                                    Женщины
                                </span>
                                <span class="font-weight-bold text-body-2">{{ 100 - genderRatio }}%</span>
                            </div>
                        </div>
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <!-- Category & Type Splits & Activity Logs -->
        <v-row>
            <!-- Category and Type Splits -->
            <v-col cols="12" md="6">
                <v-card elevation="2" class="rounded-xl pa-5 mb-6">
                    <v-card-title class="px-0 pt-0 pb-4 font-weight-bold text-h6">
                        Категории сотрудников
                    </v-card-title>
                    <v-divider class="mb-4"></v-divider>
                    
                    <div v-for="(cat, idx) in stats.category_stats" :key="cat.category" class="mb-3">
                        <div class="d-flex justify-space-between text-body-2 font-weight-medium mb-1">
                            <span>{{ cat.category }}</span>
                            <span>{{ cat.count }} ({{ Math.round((cat.count / (stats.total_employees || 1)) * 100) }}%)</span>
                        </div>
                        <v-progress-linear
                            :model-value="(cat.count / (stats.total_employees || 1)) * 100"
                            :color="colors[idx % colors.length]"
                            height="6"
                            rounded
                        ></v-progress-linear>
                    </div>
                </v-card>

                <v-card elevation="2" class="rounded-xl pa-5">
                    <v-card-title class="px-0 pt-0 pb-4 font-weight-bold text-h6">
                        Тип занятости
                    </v-card-title>
                    <v-divider class="mb-4"></v-divider>
                    
                    <div v-for="(type, idx) in stats.type_stats" :key="type.type" class="mb-3">
                        <div class="d-flex justify-space-between text-body-2 font-weight-medium mb-1">
                            <span>{{ type.type }}</span>
                            <span>{{ type.count }} ({{ Math.round((type.count / (stats.total_employees || 1)) * 100) }}%)</span>
                        </div>
                        <v-progress-linear
                            :model-value="(type.count / (stats.total_employees || 1)) * 100"
                            :color="colors[(idx + 3) % colors.length]"
                            height="6"
                            rounded
                        ></v-progress-linear>
                    </div>
                </v-card>
            </v-col>

            <!-- Activity Logs -->
            <v-col cols="12" md="6">
                <v-card elevation="2" class="rounded-xl pa-5 h-100">
                    <v-card-title class="px-0 pt-0 pb-4 font-weight-bold text-h6">
                        Последняя активность
                    </v-card-title>
                    <v-divider class="mb-4"></v-divider>
                    
                    <v-timeline density="compact" align="start">
                        <v-timeline-item
                            v-for="activity in recent_activities"
                            :key="activity.id"
                            :dot-color="activity.event === 'created' ? 'success' : (activity.event === 'updated' ? 'primary' : 'error')"
                            size="x-small"
                        >
                            <div class="mb-1">
                                <span class="font-weight-bold text-body-2">{{ activity.causer_name }}</span>
                                <span class="text-caption text-grey ml-2">{{ activity.created_at }}</span>
                            </div>
                            <div class="text-body-2 text-grey-darken-2">
                                {{ activity.description }}
                            </div>
                        </v-timeline-item>
                        <v-timeline-item v-if="recent_activities.length === 0" dot-color="grey" size="x-small">
                            <div class="text-body-2 text-grey">Активностей пока не зарегистрировано.</div>
                        </v-timeline-item>
                    </v-timeline>
                </v-card>
            </v-col>
        </v-row>
    </AuthenticatedLayout>
</template>
