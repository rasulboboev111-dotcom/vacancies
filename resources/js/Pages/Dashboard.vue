<script setup>
import { Head, Link } from '@inertiajs/vue3';
import {
    Briefcase,
    Building2,
    CheckCircle2,
    Clock,
    DoorOpen,
    FolderOpen,
    LayoutDashboard,
    MapPin,
    Mars,
    UserCheck,
    Users,
    Venus,
} from '@lucide/vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { getEventText } from '@/Pages/ActivityLogs/activityEvents';
import StatCard from '@/Pages/Dashboard/StatCard.vue';

defineProps({
    stats: {
        type: Object,
        required: true,
    },
    recent_activities: {
        type: Array,
        required: true,
    },
});

// Colors list for cards/distributions
const colors = ['#009cf1', '#10B981', '#F59E0B', '#EF4444', '#009cf1', '#EC4899'];

// Percentage helper for the distribution bars (guards division by zero).
const pct = (n, total) => (total ? Math.round((n / total) * 100) : 0);

// Sum of a {male, female, unspecified} gender tally.
const genderTotal = g => (g ? g.male + g.female + g.unspecified : 0);

// Timeline dot/chip colour for a recent-activity event.
function activityColor(event) {
    switch (event) {
        case 'created': return 'success';
        case 'updated': return 'primary';
        default: return 'error';
    }
}

const maleColor = '#009cf1';
const femaleColor = '#EC4899';
</script>

<template>
    <Head title="Лавҳаи асосӣ" />

    <AuthenticatedLayout>
        <template #header>
            <div class="d-flex align-center">
                <LayoutDashboard class="mr-3 text-indigo-accent-2 h-6 w-6" />
                <span>Лавҳаи асосӣ</span>
            </div>
        </template>

        <!-- KPI Cards -->
        <v-row class="mb-6">
            <v-col cols="12" sm="6" md="3">
                <StatCard label="Ҳамаи кормандон" :value="stats.total_employees" :icon="Users" />
            </v-col>
            <v-col cols="12" sm="6" md="3">
                <StatCard label="Филиалҳои фаъол" :value="stats.total_branches" :icon="Building2" />
            </v-col>
            <v-col cols="12" sm="6" md="3">
                <Link :href="route('vacancies.index', { status: 'open' })" class="text-decoration-none d-block">
                    <StatCard label="Вакансияҳои кушода" :value="stats.open_vacancies" :icon="DoorOpen" />
                </Link>
            </v-col>
            <v-col cols="12" sm="6" md="3">
                <Link :href="route('vacancies.index', { status: 'closed' })" class="text-decoration-none d-block">
                    <StatCard label="Вакансияҳои баста" :value="stats.closed_vacancies" :icon="CheckCircle2" />
                </Link>
            </v-col>
        </v-row>

        <!-- Vacancies by branch -->
        <v-row class="mb-6">
            <v-col cols="12">
                <v-card elevation="0" class="rounded-xl border pa-6 bg-surface-glass">
                    <v-card-title class="px-0 pt-0 pb-4 font-weight-bold text-h6 d-flex align-center text-indigo-darken-4">
                        <Briefcase style="width: 20px; height: 20px; margin-right: 8px;" class="text-indigo" />
                        Вакансияҳо аз рӯи филиалҳо
                    </v-card-title>
                    <v-divider class="mb-5" />

                    <v-row v-if="stats.vacancy_by_branch && stats.vacancy_by_branch.length > 0">
                        <v-col v-for="branch in stats.vacancy_by_branch" :key="branch.id" cols="12" sm="6" md="4" class="mb-2">
                            <v-card elevation="0" class="pa-4 rounded-lg bg-surface-glass border transition-hover h-100">
                                <div class="d-flex justify-space-between align-center mb-3">
                                    <div class="font-weight-bold text-subtitle-1 d-flex align-center text-grey-darken-4">
                                        {{ branch.name }}
                                    </div>
                                </div>
                                <div class="d-flex" style="gap: 12px;">
                                    <div class="flex-grow-1 text-center pa-3 rounded-lg" style="background: rgba(245, 158, 11, 0.1);">
                                        <div class="text-h5 font-weight-black" style="color: #d97706;">
                                            {{ branch.open }}
                                        </div>
                                        <div class="text-caption font-weight-bold text-grey-darken-1">
                                            Кушода
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 text-center pa-3 rounded-lg" style="background: rgba(100, 116, 139, 0.1);">
                                        <div class="text-h5 font-weight-black text-grey-darken-2">
                                            {{ branch.closed }}
                                        </div>
                                        <div class="text-caption font-weight-bold text-grey-darken-1">
                                            Баста
                                        </div>
                                    </div>
                                </div>
                            </v-card>
                        </v-col>
                    </v-row>
                    <div v-else class="text-center py-8 text-grey font-weight-medium">
                        Ҳоло вакансия нест.
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <!-- Branch Stats -->
        <v-row class="mb-6">
            <v-col cols="12">
                <v-card elevation="0" class="rounded-xl border pa-6 bg-surface-glass h-100">
                    <v-card-title class="px-0 pt-0 pb-4 font-weight-bold text-h6 d-flex align-center text-indigo-darken-4">
                        <MapPin style="width: 20px; height: 20px; margin-right: 8px;" class="text-indigo" />
                        Тақсимоти кормандон аз рӯи филиалҳо
                    </v-card-title>
                    <v-divider class="mb-5" />

                    <v-row>
                        <v-col v-for="branch in stats.branch_stats" :key="branch.id" cols="12" sm="6" md="4" class="mb-4">
                            <v-card elevation="0" class="pa-4 rounded-lg bg-surface-glass border transition-hover h-100">
                                <div class="d-flex justify-space-between align-center mb-3">
                                    <div class="font-weight-bold text-subtitle-1 d-flex align-center text-grey-darken-4">
                                        {{ branch.name }}
                                    </div>
                                    <div class="text-h6 font-weight-black text-indigo">
                                        {{ branch.employees_count }} <span class="text-caption text-grey">нафар.</span>
                                    </div>
                                </div>
                                <v-progress-linear
                                    :model-value="(branch.employees_count / (stats.total_employees || 1)) * 100"
                                    color="indigo-accent-3"
                                    height="8"
                                    rounded
                                    striped
                                    class="mb-1"
                                />
                                <div class="d-flex justify-end text-caption text-grey mt-2">
                                    <span>Ҳисса: {{ Math.round((branch.employees_count / (stats.total_employees || 1)) * 100) }}%</span>
                                </div>
                            </v-card>
                        </v-col>
                    </v-row>
                </v-card>
            </v-col>
        </v-row>

        <!-- Gender distribution -->
        <v-row class="mb-6">
            <!-- All employees by gender -->
            <v-col cols="12" md="6">
                <v-card elevation="0" class="rounded-xl border pa-6 bg-surface-glass h-100">
                    <v-card-title class="px-0 pt-0 pb-4 font-weight-bold text-h6 d-flex align-center text-indigo-darken-4">
                        <Users style="width: 20px; height: 20px; margin-right: 8px;" class="text-indigo" />
                        Тақсимот аз рӯи ҷинс
                    </v-card-title>
                    <v-divider class="mb-5" />

                    <div class="d-flex mb-5" style="gap: 12px;">
                        <div class="flex-grow-1 text-center pa-4 rounded-lg" :style="{ background: 'rgba(0, 156, 241, 0.1)' }">
                            <Mars style="width: 22px; height: 22px;" :style="{ color: maleColor }" class="mb-1" />
                            <div class="text-h4 font-weight-black" :style="{ color: maleColor }">
                                {{ stats.gender_stats.male }}
                            </div>
                            <div class="text-caption font-weight-bold text-grey-darken-1">
                                Мардон
                            </div>
                        </div>
                        <div class="flex-grow-1 text-center pa-4 rounded-lg" :style="{ background: 'rgba(236, 72, 153, 0.1)' }">
                            <Venus style="width: 22px; height: 22px;" :style="{ color: femaleColor }" class="mb-1" />
                            <div class="text-h4 font-weight-black" :style="{ color: femaleColor }">
                                {{ stats.gender_stats.female }}
                            </div>
                            <div class="text-caption font-weight-bold text-grey-darken-1">
                                Занон
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-space-between text-body-2 font-weight-bold mb-1">
                            <span class="text-grey-darken-3">Мардон</span>
                            <span :style="{ color: maleColor }">{{ stats.gender_stats.male }} нафар. ({{ pct(stats.gender_stats.male, genderTotal(stats.gender_stats)) }}%)</span>
                        </div>
                        <v-progress-linear :model-value="pct(stats.gender_stats.male, genderTotal(stats.gender_stats))" :color="maleColor" height="8" rounded />
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-space-between text-body-2 font-weight-bold mb-1">
                            <span class="text-grey-darken-3">Занон</span>
                            <span :style="{ color: femaleColor }">{{ stats.gender_stats.female }} нафар. ({{ pct(stats.gender_stats.female, genderTotal(stats.gender_stats)) }}%)</span>
                        </div>
                        <v-progress-linear :model-value="pct(stats.gender_stats.female, genderTotal(stats.gender_stats))" :color="femaleColor" height="8" rounded />
                    </div>
                    <div v-if="stats.gender_stats.unspecified > 0">
                        <div class="d-flex justify-space-between text-body-2 font-weight-bold mb-1">
                            <span class="text-grey-darken-3">Зикр нашудааст</span>
                            <span class="text-grey">{{ stats.gender_stats.unspecified }} нафар. ({{ pct(stats.gender_stats.unspecified, genderTotal(stats.gender_stats)) }}%)</span>
                        </div>
                        <v-progress-linear :model-value="pct(stats.gender_stats.unspecified, genderTotal(stats.gender_stats))" color="grey" height="8" rounded />
                    </div>
                </v-card>
            </v-col>

            <!-- Managers by gender -->
            <v-col cols="12" md="6">
                <v-card elevation="0" class="rounded-xl border pa-6 bg-surface-glass h-100">
                    <v-card-title class="px-0 pt-0 pb-4 font-weight-bold text-h6 d-flex align-center text-indigo-darken-4">
                        <UserCheck style="width: 20px; height: 20px; margin-right: 8px;" class="text-indigo" />
                        Роҳбарон аз рӯи ҷинс
                    </v-card-title>
                    <v-divider class="mb-5" />

                    <div class="d-flex mb-5" style="gap: 12px;">
                        <div class="flex-grow-1 text-center pa-4 rounded-lg" :style="{ background: 'rgba(0, 156, 241, 0.1)' }">
                            <Mars style="width: 22px; height: 22px;" :style="{ color: maleColor }" class="mb-1" />
                            <div class="text-h4 font-weight-black" :style="{ color: maleColor }">
                                {{ stats.manager_gender_stats.male }}
                            </div>
                            <div class="text-caption font-weight-bold text-grey-darken-1">
                                Роҳбарон-мардон
                            </div>
                        </div>
                        <div class="flex-grow-1 text-center pa-4 rounded-lg" :style="{ background: 'rgba(236, 72, 153, 0.1)' }">
                            <Venus style="width: 22px; height: 22px;" :style="{ color: femaleColor }" class="mb-1" />
                            <div class="text-h4 font-weight-black" :style="{ color: femaleColor }">
                                {{ stats.manager_gender_stats.female }}
                            </div>
                            <div class="text-caption font-weight-bold text-grey-darken-1">
                                Роҳбарон-занон
                            </div>
                        </div>
                    </div>

                    <div class="text-center text-body-2 text-grey-darken-1 font-weight-medium mb-4">
                        Ҳамагӣ роҳбарон: <span class="font-weight-black text-indigo-darken-2">{{ genderTotal(stats.manager_gender_stats) }}</span> нафар.
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-space-between text-body-2 font-weight-bold mb-1">
                            <span class="text-grey-darken-3">Мардон</span>
                            <span :style="{ color: maleColor }">{{ pct(stats.manager_gender_stats.male, genderTotal(stats.manager_gender_stats)) }}%</span>
                        </div>
                        <v-progress-linear :model-value="pct(stats.manager_gender_stats.male, genderTotal(stats.manager_gender_stats))" :color="maleColor" height="8" rounded />
                    </div>
                    <div>
                        <div class="d-flex justify-space-between text-body-2 font-weight-bold mb-1">
                            <span class="text-grey-darken-3">Занон</span>
                            <span :style="{ color: femaleColor }">{{ pct(stats.manager_gender_stats.female, genderTotal(stats.manager_gender_stats)) }}%</span>
                        </div>
                        <v-progress-linear :model-value="pct(stats.manager_gender_stats.female, genderTotal(stats.manager_gender_stats))" :color="femaleColor" height="8" rounded />
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <!-- Category & Type Splits & Activity Logs -->
        <v-row>
            <!-- Category and Type Splits -->
            <v-col cols="12" md="6" class="d-flex flex-column gap-6">
                <v-card elevation="0" class="rounded-xl border pa-6 bg-surface-glass mb-6">
                    <v-card-title class="px-0 pt-0 pb-4 font-weight-bold text-h6 d-flex align-center text-indigo-darken-4">
                        <FolderOpen style="width: 20px; height: 20px; margin-right: 8px;" class="text-indigo" />
                        Категорияҳои кормандон
                    </v-card-title>
                    <v-divider class="mb-4" />

                    <div v-for="(cat, idx) in stats.category_stats" :key="cat.category" class="mb-4">
                        <div class="d-flex justify-space-between text-body-2 font-weight-bold mb-1">
                            <span class="text-grey-darken-3">{{ cat.category }}</span>
                            <span class="text-indigo">{{ cat.count }} нафар. ({{ Math.round((cat.count / (stats.total_employees || 1)) * 100) }}%)</span>
                        </div>
                        <v-progress-linear
                            :model-value="(cat.count / (stats.total_employees || 1)) * 100"
                            :color="colors[idx % colors.length]"
                            height="8"
                            rounded
                        />
                    </div>
                </v-card>

                <v-card elevation="0" class="rounded-xl border pa-6 bg-surface-glass">
                    <v-card-title class="px-0 pt-0 pb-4 font-weight-bold text-h6 d-flex align-center text-indigo-darken-4">
                        <Briefcase style="width: 20px; height: 20px; margin-right: 8px;" class="text-indigo" />
                        Намуди шуғл
                    </v-card-title>
                    <v-divider class="mb-4" />

                    <div v-for="(type, idx) in stats.type_stats" :key="type.type" class="mb-4">
                        <div class="d-flex justify-space-between text-body-2 font-weight-bold mb-1">
                            <span class="text-grey-darken-3">{{ type.type }}</span>
                            <span class="text-indigo">{{ type.count }} нафар. ({{ Math.round((type.count / (stats.total_employees || 1)) * 100) }}%)</span>
                        </div>
                        <v-progress-linear
                            :model-value="(type.count / (stats.total_employees || 1)) * 100"
                            :color="colors[(idx + 3) % colors.length]"
                            height="8"
                            rounded
                        />
                    </div>
                </v-card>
            </v-col>

            <!-- Activity Logs -->
            <v-col cols="12" md="6">
                <v-card elevation="0" class="rounded-xl border pa-6 bg-surface-glass h-100">
                    <v-card-title class="px-0 pt-0 pb-4 font-weight-bold text-h6 d-flex align-center text-indigo-darken-4">
                        <Clock style="width: 20px; height: 20px; margin-right: 8px;" class="text-indigo" />
                        Фаъолияти охирин
                    </v-card-title>
                    <v-divider class="mb-5" />

                    <v-timeline density="compact" align="start" class="activity-timeline">
                        <v-timeline-item
                            v-for="activity in recent_activities"
                            :key="activity.id"
                            :dot-color="activityColor(activity.event)"
                            size="x-small"
                        >
                            <div class="mb-1">
                                <span class="font-weight-black text-subtitle-2 text-indigo-darken-2">{{ activity.causer_name }}</span>
                                <v-chip size="x-small" :color="activityColor(activity.event)" class="ml-2 px-2 text-uppercase font-weight-black" variant="tonal">
                                    {{ getEventText(activity.event) }}
                                </v-chip>
                                <span class="text-caption text-grey ml-auto d-inline-block">{{ activity.created_at }}</span>
                            </div>
                            <div class="text-body-2 text-grey-darken-3 font-weight-medium bg-surface pa-2 rounded-lg border">
                                {{ activity.description }}
                            </div>
                        </v-timeline-item>
                        <v-timeline-item v-if="recent_activities.length === 0" dot-color="grey" size="x-small">
                            <div class="text-body-2 text-grey font-weight-medium">
                                Ҳоло ягон фаъолият сабт нашудааст.
                            </div>
                        </v-timeline-item>
                    </v-timeline>
                </v-card>
            </v-col>
        </v-row>
    </AuthenticatedLayout>
</template>

<style scoped>
.bg-surface-glass {
    background: rgba(255, 255, 255, 0.7) !important;
    backdrop-filter: blur(12px);
}
.activity-timeline {
    max-height: 480px;
    overflow-y: auto;
    padding-right: 8px;
}
.activity-timeline::-webkit-scrollbar {
    width: 6px;
}
.activity-timeline::-webkit-scrollbar-track {
    background: transparent;
}
.activity-timeline::-webkit-scrollbar-thumb {
    background: rgba(0, 156, 241, 0.2);
    border-radius: 3px;
}
.activity-timeline::-webkit-scrollbar-thumb:hover {
    background: rgba(0, 156, 241, 0.4);
}
</style>
