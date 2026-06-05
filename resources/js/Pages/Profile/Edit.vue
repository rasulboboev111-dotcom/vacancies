<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { Building2, CalendarDays, KeyRound, Mail, ShieldCheck, UserCog } from '@lucide/vue';
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    profile: {
        type: Object,
        default: () => ({}),
    },
});

const user = computed(() => usePage().props.auth.user);
const initial = computed(() => user.value.name.charAt(0).toUpperCase());
const roleLabel = computed(() =>
    user.value.roles.length > 0 ? user.value.roles.join(', ') : 'Нақш надорад',
);
</script>

<template>
    <Head title="Профил" />

    <AuthenticatedLayout>
        <template #header>
            <div class="d-flex align-center">
                <UserCog class="mr-3 text-indigo-accent-2 h-6 w-6" />
                <span>Профил</span>
            </div>
        </template>

        <!-- Hero header -->
        <v-card elevation="0" class="profile-hero rounded-xl mb-6 overflow-hidden">
            <div class="profile-hero__bg" />
            <div class="profile-hero__content d-flex flex-column flex-sm-row align-center pa-6">
                <v-avatar size="92" class="profile-hero__avatar mr-sm-6 mb-4 mb-sm-0">
                    <span class="text-h3 font-weight-black text-white">{{ initial }}</span>
                </v-avatar>

                <div class="text-center text-sm-left flex-grow-1">
                    <h1 class="text-h5 font-weight-black text-white mb-1">
                        {{ user.name }}
                    </h1>
                    <div class="d-flex align-center justify-center justify-sm-start text-white opacity-90 mb-3">
                        <Mail class="mr-2" style="width: 16px; height: 16px;" />
                        <span class="text-body-2 font-weight-medium">{{ user.email }}</span>
                    </div>

                    <div class="d-flex flex-wrap justify-center justify-sm-start ga-2">
                        <v-chip size="small" variant="elevated" color="white" class="font-weight-bold text-indigo-darken-3">
                            <ShieldCheck class="mr-1" style="width: 14px; height: 14px;" />
                            {{ roleLabel }}
                        </v-chip>
                        <v-chip size="small" variant="flat" class="profile-chip font-weight-bold text-white">
                            <Building2 class="mr-1" style="width: 14px; height: 14px;" />
                            {{ profile.branch_name ?? 'Ҳамаи филиалҳо' }}
                        </v-chip>
                        <v-chip v-if="profile.joined_at" size="small" variant="flat" class="profile-chip font-weight-bold text-white">
                            <CalendarDays class="mr-1" style="width: 14px; height: 14px;" />
                            Аз {{ profile.joined_at }}
                        </v-chip>
                    </div>
                </div>
            </div>
        </v-card>

        <!-- Forms -->
        <v-row>
            <v-col cols="12" md="6">
                <v-card elevation="0" class="rounded-xl profile-card pa-6 bg-surface-glass h-100">
                    <div class="d-flex align-center pb-4">
                        <div class="section-icon section-icon--indigo mr-3">
                            <UserCog style="width: 20px; height: 20px;" />
                        </div>
                        <div>
                            <h2 class="text-h6 font-weight-bold text-indigo-darken-4">Маълумоти профил</h2>
                            <p class="text-caption text-grey-darken-1 mb-0">Ном ва почтаи электрониро навсозӣ кунед</p>
                        </div>
                    </div>
                    <v-divider class="mb-5" />

                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                    />
                </v-card>
            </v-col>

            <v-col cols="12" md="6">
                <v-card elevation="0" class="rounded-xl profile-card pa-6 bg-surface-glass h-100">
                    <div class="d-flex align-center pb-4">
                        <div class="section-icon section-icon--amber mr-3">
                            <KeyRound style="width: 20px; height: 20px;" />
                        </div>
                        <div>
                            <h2 class="text-h6 font-weight-bold text-indigo-darken-4">Бехатарӣ</h2>
                            <p class="text-caption text-grey-darken-1 mb-0">Рамзи воридшавиро тағйир диҳед</p>
                        </div>
                    </div>
                    <v-divider class="mb-5" />

                    <UpdatePasswordForm />
                </v-card>
            </v-col>
        </v-row>
    </AuthenticatedLayout>
</template>

<style scoped>
.profile-hero {
    position: relative;
    border: 1px solid rgb(var(--v-theme-surface-variant), 0.4);
}
.profile-card {
    border: 1px solid #d7dce5 !important;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04) !important;
}
.profile-hero__bg {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(circle at 88% -20%, rgba(255, 255, 255, 0.25), transparent 45%),
        radial-gradient(circle at 0% 120%, rgba(79, 70, 229, 0.55), transparent 50%),
        linear-gradient(120deg, #009cf1 0%, #1565c0 55%, #4f46e5 100%);
}
.profile-hero__content {
    position: relative;
}
.profile-hero__avatar {
    background: rgba(255, 255, 255, 0.18);
    border: 3px solid rgba(255, 255, 255, 0.55);
    backdrop-filter: blur(4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.18);
}
.profile-chip {
    background: rgba(255, 255, 255, 0.18) !important;
    backdrop-filter: blur(4px);
}
.section-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 42px;
    border-radius: 12px;
    flex-shrink: 0;
}
.section-icon--indigo {
    background: rgba(79, 70, 229, 0.12);
    color: #4f46e5;
}
.section-icon--amber {
    background: rgba(245, 158, 11, 0.14);
    color: #d97706;
}
</style>
