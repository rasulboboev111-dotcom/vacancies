<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { 
    LayoutDashboard, 
    Users, 
    Building2, 
    MapPin, 
    FolderOpen, 
    Briefcase,
    Clock,
    Plus,
    DoorOpen,
    CheckCircle2
} from '@lucide/vue';

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

// Colors list for cards/distributions
const colors = ['#009cf1', '#10B981', '#F59E0B', '#EF4444', '#009cf1', '#EC4899'];
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
                <v-card elevation="0" class="rounded-xl pa-5 bg-gradient-indigo border-glow relative overflow-hidden transition-hover">
                    <div class="d-flex justify-between align-center">
                        <div>
                            <span class="text-subtitle-2 text-white-50 font-weight-medium text-uppercase tracking-wider">Ҳамаи кормандон</span>
                            <h2 class="text-h3 font-weight-black mt-2 text-white">{{ stats.total_employees }}</h2>
                        </div>
                        <v-avatar rounded="xl" size="64" style="background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(4px);">
                            <Users style="width: 32px; height: 32px; color: white;" />
                        </v-avatar>
                    </div>
                    <div class="glass-shine"></div>
                </v-card>
            </v-col>

            <v-col cols="12" sm="6" md="3">
                <v-card elevation="0" class="rounded-xl pa-5 bg-gradient-emerald border-glow relative overflow-hidden transition-hover">
                    <div class="d-flex justify-between align-center">
                        <div>
                            <span class="text-subtitle-2 text-white-50 font-weight-medium text-uppercase tracking-wider">Филиалҳои фаъол</span>
                            <h2 class="text-h3 font-weight-black mt-2 text-white">{{ stats.total_branches }}</h2>
                        </div>
                        <v-avatar rounded="xl" size="64" style="background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(4px);">
                            <Building2 style="width: 32px; height: 32px; color: white;" />
                        </v-avatar>
                    </div>
                    <div class="glass-shine"></div>
                </v-card>
            </v-col>

            <v-col cols="12" sm="6" md="3">
                <v-card elevation="0" class="rounded-xl pa-5 bg-gradient-amber border-glow relative overflow-hidden transition-hover">
                    <div class="d-flex justify-between align-center">
                        <div>
                            <span class="text-subtitle-2 text-white-50 font-weight-medium text-uppercase tracking-wider">Вакансияҳои кушода</span>
                            <h2 class="text-h3 font-weight-black mt-2 text-white">{{ stats.open_vacancies }}</h2>
                        </div>
                        <v-avatar rounded="xl" size="64" style="background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(4px);">
                            <DoorOpen style="width: 32px; height: 32px; color: white;" />
                        </v-avatar>
                    </div>
                    <div class="glass-shine"></div>
                </v-card>
            </v-col>

            <v-col cols="12" sm="6" md="3">
                <v-card elevation="0" class="rounded-xl pa-5 bg-gradient-slate border-glow relative overflow-hidden transition-hover">
                    <div class="d-flex justify-between align-center">
                        <div>
                            <span class="text-subtitle-2 text-white-50 font-weight-medium text-uppercase tracking-wider">Вакансияҳои баста</span>
                            <h2 class="text-h3 font-weight-black mt-2 text-white">{{ stats.closed_vacancies }}</h2>
                        </div>
                        <v-avatar rounded="xl" size="64" style="background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(4px);">
                            <CheckCircle2 style="width: 32px; height: 32px; color: white;" />
                        </v-avatar>
                    </div>
                    <div class="glass-shine"></div>
                </v-card>
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
                    <v-divider class="mb-5"></v-divider>

                    <v-row v-if="stats.vacancy_by_branch && stats.vacancy_by_branch.length > 0">
                        <v-col v-for="branch in stats.vacancy_by_branch" :key="branch.id" cols="12" sm="6" md="4" class="mb-2">
                            <v-card elevation="0" class="pa-4 rounded-lg bg-surface-glass border transition-hover h-100">
                                <div class="d-flex justify-space-between align-center mb-3">
                                    <div class="font-weight-bold text-subtitle-1 d-flex align-center text-grey-darken-4">
                                        {{ branch.name }}
                                        <v-chip v-if="branch.code" size="x-small" color="indigo" class="ml-2 font-weight-bold text-uppercase">{{ branch.code }}</v-chip>
                                    </div>
                                </div>
                                <div class="d-flex" style="gap: 12px;">
                                    <div class="flex-grow-1 text-center pa-3 rounded-lg" style="background: rgba(245, 158, 11, 0.1);">
                                        <div class="text-h5 font-weight-black" style="color: #d97706;">{{ branch.open }}</div>
                                        <div class="text-caption font-weight-bold text-grey-darken-1">Кушода</div>
                                    </div>
                                    <div class="flex-grow-1 text-center pa-3 rounded-lg" style="background: rgba(100, 116, 139, 0.1);">
                                        <div class="text-h5 font-weight-black text-grey-darken-2">{{ branch.closed }}</div>
                                        <div class="text-caption font-weight-bold text-grey-darken-1">Баста</div>
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
                    <v-divider class="mb-5"></v-divider>
                    
                    <v-row>
                        <v-col v-for="(branch, index) in stats.branch_stats" :key="branch.id" cols="12" sm="6" md="4" class="mb-4">
                            <v-card elevation="0" class="pa-4 rounded-lg bg-surface-glass border transition-hover h-100">
                                <div class="d-flex justify-space-between align-center mb-3">
                                    <div class="font-weight-bold text-subtitle-1 d-flex align-center text-grey-darken-4">
                                        {{ branch.name }}
                                        <v-chip size="x-small" color="indigo" class="ml-2 font-weight-bold text-uppercase">{{ branch.code }}</v-chip>
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
                                ></v-progress-linear>
                                <div class="d-flex justify-end text-caption text-grey mt-2">
                                    <span>Ҳисса: {{ Math.round((branch.employees_count / (stats.total_employees || 1)) * 100) }}%</span>
                                </div>
                            </v-card>
                        </v-col>
                    </v-row>
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
                    <v-divider class="mb-4"></v-divider>
                    
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
                        ></v-progress-linear>
                    </div>
                </v-card>

                <v-card elevation="0" class="rounded-xl border pa-6 bg-surface-glass">
                    <v-card-title class="px-0 pt-0 pb-4 font-weight-bold text-h6 d-flex align-center text-indigo-darken-4">
                        <Briefcase style="width: 20px; height: 20px; margin-right: 8px;" class="text-indigo" />
                        Намуди шуғл
                    </v-card-title>
                    <v-divider class="mb-4"></v-divider>
                    
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
                        ></v-progress-linear>
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
                    <v-divider class="mb-5"></v-divider>
                    
                    <v-timeline density="compact" align="start" class="activity-timeline">
                        <v-timeline-item
                            v-for="activity in recent_activities"
                            :key="activity.id"
                            :dot-color="activity.event === 'created' ? 'success' : (activity.event === 'updated' ? 'primary' : 'error')"
                            size="x-small"
                        >
                            <div class="mb-1">
                                <span class="font-weight-black text-subtitle-2 text-indigo-darken-2">{{ activity.causer_name }}</span>
                                <v-chip size="x-small" :color="activity.event === 'created' ? 'success' : (activity.event === 'updated' ? 'primary' : 'error')" class="ml-2 px-2 text-uppercase font-weight-black" variant="tonal">{{ activity.event }}</v-chip>
                                <span class="text-caption text-grey ml-auto d-inline-block">{{ activity.created_at }}</span>
                            </div>
                            <div class="text-body-2 text-grey-darken-3 font-weight-medium bg-surface pa-2 rounded-lg border">
                                {{ activity.description }}
                            </div>
                        </v-timeline-item>
                        <v-timeline-item v-if="recent_activities.length === 0" dot-color="grey" size="x-small">
                            <div class="text-body-2 text-grey font-weight-medium">Ҳоло ягон фаъолият сабт нашудааст.</div>
                        </v-timeline-item>
                    </v-timeline>
                </v-card>
            </v-col>
        </v-row>
    </AuthenticatedLayout>
</template>

<style scoped>
.bg-gradient-indigo {
    background: #009cf1 !important;
}
.bg-gradient-emerald {
    background: #059669 !important;
}
.bg-gradient-amber {
    background: #d97706 !important;
}
.bg-gradient-slate {
    background: #475569 !important;
}
.bg-surface-glass {
    background: rgba(255, 255, 255, 0.7) !important;
    backdrop-filter: blur(12px);
}
.text-white-50 {
    color: rgba(255, 255, 255, 0.7) !important;
}
.white-10 {
    background: rgba(255, 255, 255, 0.15) !important;
}
.backdrop-blur {
    backdrop-filter: blur(4px);
}
.tracking-wider {
    letter-spacing: 0.05em;
}
.border-glow {
    box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.2);
}
.transition-hover {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.transition-hover:hover {
    transform: translateY(-4px);
    box-shadow: 0 15px 35px -10px rgba(0, 156, 241, 0.15), 0 5px 15px rgba(0, 0, 0, 0.05);
}
.glass-shine {
    position: absolute;
    top: 0;
    left: -50%;
    width: 100%;
    height: 100%;
    background: transparent;
    transform: skewX(-25deg);
    transition: 0.75s;
    pointer-events: none;
}
.v-card:hover .glass-shine {
    left: 120%;
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
