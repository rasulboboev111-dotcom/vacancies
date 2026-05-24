<script setup>
import { ref } from 'vue';
import { useTheme } from 'vuetify';
import { router } from '@inertiajs/vue3';

const drawer = ref(true);
const theme = useTheme();

function toggleTheme() {
    theme.global.name.value = theme.global.current.value.dark ? 'light' : 'dark';
}

function logout() {
    router.post(route('logout'));
}
</script>

<template>
    <v-app>
        <!-- Navigation Drawer -->
        <v-navigation-drawer
            v-model="drawer"
            elevation="3"
            class="border-r-0"
            color="surface"
        >
            <v-list-item class="py-5 bg-primary text-white">
                <template v-slot:prepend>
                    <v-icon size="x-large" icon="mdi-shield-account" class="mr-2"></v-icon>
                </template>
                <v-list-item-title class="text-h6 font-weight-bold">
                    HR Vacancies
                </v-list-item-title>
                <v-list-item-subtitle class="text-caption text-white opacity-70">
                    Система управления
                </v-list-item-subtitle>
            </v-list-item>

            <v-divider></v-divider>

            <v-list density="comfortable" nav class="mt-4 px-2">
                <v-list-item
                    prepend-icon="mdi-view-dashboard"
                    title="Дашборд"
                    :active="route().current('dashboard')"
                    color="primary"
                    rounded="lg"
                    class="mb-1"
                    @click="router.visit(route('dashboard'))"
                ></v-list-item>

                <v-list-item
                    prepend-icon="mdi-account-group"
                    title="Сотрудники"
                    :active="route().current('employees.*')"
                    color="primary"
                    rounded="lg"
                    class="mb-1"
                    @click="router.visit(route('employees.index'))"
                ></v-list-item>

                <v-list-item
                    prepend-icon="mdi-office-building"
                    title="Филиалы"
                    :active="route().current('branches.*')"
                    color="primary"
                    rounded="lg"
                    class="mb-1"
                    @click="router.visit(route('branches.index'))"
                ></v-list-item>

                <!-- Show only for Admin or HR Manager who has view audit logs permission or has role -->
                <v-list-item
                    v-if="$page.props.auth.user.roles.includes('Admin') || $page.props.auth.user.roles.includes('HR Manager')"
                    prepend-icon="mdi-history"
                    title="Логи действий"
                    :active="route().current('activity-logs.*')"
                    color="primary"
                    rounded="lg"
                    class="mb-1"
                    @click="router.visit(route('activity-logs.index'))"
                ></v-list-item>
            </v-list>
        </v-navigation-drawer>

        <!-- AppBar / Header -->
        <v-app-bar elevation="1" color="surface">
            <v-app-bar-nav-icon @click="drawer = !drawer"></v-app-bar-nav-icon>
            
            <v-toolbar-title class="font-weight-medium">
                <slot name="header" />
            </v-toolbar-title>

            <v-spacer></v-spacer>

            <!-- Theme Toggle -->
            <v-btn icon @click="toggleTheme" class="mr-2">
                <v-icon :icon="theme.global.current.value.dark ? 'mdi-weather-sunny' : 'mdi-weather-night'"></v-icon>
            </v-btn>

            <!-- User Menu -->
            <v-menu min-width="200px" rounded="lg">
                <template v-slot:activator="{ props }">
                    <v-btn icon v-bind="props" class="mr-3">
                        <v-avatar color="primary" size="38">
                            <span class="text-white text-subtitle-1">
                                {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                            </span>
                        </v-avatar>
                    </v-btn>
                </template>
                <v-card class="mt-2">
                    <v-card-text>
                        <div class="mx-auto text-center py-2">
                            <v-avatar color="primary" size="48" class="mb-2">
                                <span class="text-white text-h5">
                                    {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                                </span>
                            </v-avatar>
                            <h4 class="font-weight-bold">{{ $page.props.auth.user.name }}</h4>
                            <p class="text-caption text-grey mt-1">{{ $page.props.auth.user.email }}</p>
                            
                            <v-chip size="small" color="primary" class="mt-2 font-weight-medium">
                                {{ $page.props.auth.user.roles.join(', ') }}
                            </v-chip>

                            <v-divider class="my-3"></v-divider>
                            
                            <v-btn
                                variant="text"
                                prepend-icon="mdi-account"
                                rounded="lg"
                                block
                                class="text-left justify-start"
                                @click="router.visit(route('profile.edit'))"
                            >
                                Профиль
                            </v-btn>
                            
                            <v-btn
                                variant="text"
                                color="error"
                                prepend-icon="mdi-logout"
                                rounded="lg"
                                block
                                class="text-left justify-start mt-1"
                                @click="logout"
                            >
                                Выйти
                            </v-btn>
                        </div>
                    </v-card-text>
                </v-card>
            </v-menu>
        </v-app-bar>

        <!-- Main Content -->
        <v-main class="bg-background">
            <v-container fluid class="pa-6">
                <!-- Alert Messages -->
                <v-slide-y-transition>
                    <v-alert
                        v-if="$page.props.flash && $page.props.flash.success"
                        type="success"
                        variant="tonal"
                        closable
                        class="mb-4"
                    >
                        {{ $page.props.flash.success }}
                    </v-alert>
                </v-slide-y-transition>

                <v-slide-y-transition>
                    <v-alert
                        v-if="$page.props.flash && $page.props.flash.error"
                        type="error"
                        variant="tonal"
                        closable
                        class="mb-4"
                    >
                        {{ $page.props.flash.error }}
                    </v-alert>
                </v-slide-y-transition>

                <slot />
            </v-container>
        </v-main>
    </v-app>
</template>

<style scoped>
.v-list-item--active {
    font-weight: 600;
}
</style>
