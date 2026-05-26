<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { 
    LayoutDashboard, 
    Users, 
    GitFork, 
    Archive, 
    Building2, 
    History, 
    Trash2,
    LogOut,
    User,
    Menu,
    Shield,
    Briefcase
} from '@lucide/vue';

const drawer = ref(true);

function logout() {
    router.post(route('logout'));
}
</script>

<template>
    <v-app>
        <!-- Navigation Drawer -->
        <v-navigation-drawer
            v-model="drawer"
            elevation="0"
            class="border-r"
            color="surface"
            width="260"
        >
            <v-list-item class="py-5 text-white" style="background: linear-gradient(135deg, #009cf1 0%, #0f2d88 100%)">
                <template v-slot:prepend>
                    <img src="https://tojiktelecom.tj/logonew.svg" class="mr-3" style="height: 32px; width: auto; max-width: 60px; filter: brightness(0) invert(1);" alt="Tojiktelecom" />
                </template>
                <v-list-item-title class="text-h6 font-weight-black tracking-wide">
                    TOJIKTELECOM
                </v-list-item-title>
                <v-list-item-subtitle class="text-caption text-white opacity-70 font-weight-medium">
                    Управление кадрами
                </v-list-item-subtitle>
            </v-list-item>

            <v-divider></v-divider>

            <v-list density="comfortable" nav class="mt-4 px-2">
                <v-list-item
                    title="Дашборд"
                    :active="route().current('dashboard')"
                    color="indigo"
                    rounded="lg"
                    class="mb-1"
                    @click="router.visit(route('dashboard'))"
                >
                    <template v-slot:prepend>
                        <LayoutDashboard class="mr-3 h-5 w-5 opacity-70 nav-icon-color" />
                    </template>
                </v-list-item>

                <v-list-item
                    title="Сотрудники"
                    :active="route().current('employees.index')"
                    color="indigo"
                    rounded="lg"
                    class="mb-1"
                    @click="router.visit(route('employees.index'))"
                >
                    <template v-slot:prepend>
                        <Users class="mr-3 h-5 w-5 opacity-70 nav-icon-color" />
                    </template>
                </v-list-item>

                <v-list-item
                    title="Ротации"
                    :active="route().current('rotations.index')"
                    color="indigo"
                    rounded="lg"
                    class="mb-1"
                    @click="router.visit(route('rotations.index'))"
                >
                    <template v-slot:prepend>
                        <GitFork class="mr-3 h-5 w-5 opacity-70 nav-icon-color" />
                    </template>
                </v-list-item>

                <v-list-item
                    title="Архив (Пенсионеры)"
                    :active="route().current('employees.archive')"
                    color="indigo"
                    rounded="lg"
                    class="mb-1"
                    @click="router.visit(route('employees.archive'))"
                >
                    <template v-slot:prepend>
                        <Archive class="mr-3 h-5 w-5 opacity-70 nav-icon-color" />
                    </template>
                </v-list-item>

                <v-list-item
                    title="Филиалы"
                    :active="route().current('branches.*')"
                    color="indigo"
                    rounded="lg"
                    class="mb-1"
                    @click="router.visit(route('branches.index'))"
                >
                    <template v-slot:prepend>
                        <Building2 class="mr-3 h-5 w-5 opacity-70 nav-icon-color" />
                    </template>
                </v-list-item>

                <v-list-item
                    title="Должности"
                    :active="route().current('positions.*')"
                    color="indigo"
                    rounded="lg"
                    class="mb-1"
                    @click="router.visit(route('positions.index'))"
                >
                    <template v-slot:prepend>
                        <Briefcase class="mr-3 h-5 w-5 opacity-70 nav-icon-color" />
                    </template>
                </v-list-item>

                <!-- Show only for Admin -->
                <v-list-item
                    v-if="$page.props.auth.user.roles.includes('Admin')"
                    title="Пользователи"
                    :active="route().current('users.*')"
                    color="indigo"
                    rounded="lg"
                    class="mb-1"
                    @click="router.visit(route('users.index'))"
                >
                    <template v-slot:prepend>
                        <Shield class="mr-3 h-5 w-5 opacity-70 nav-icon-color" />
                    </template>
                </v-list-item>

                <!-- Show only for Admin or HR Manager who has view audit logs permission or has role -->
                <v-list-item
                    v-if="$page.props.auth.user.roles.includes('Admin') || $page.props.auth.user.roles.includes('HR Manager')"
                    title="Логи действий"
                    :active="route().current('activity-logs.*')"
                    color="indigo"
                    rounded="lg"
                    class="mb-1"
                    @click="router.visit(route('activity-logs.index'))"
                >
                    <template v-slot:prepend>
                        <History class="mr-3 h-5 w-5 opacity-70 nav-icon-color" />
                    </template>
                </v-list-item>

                <v-list-item
                    v-if="!$page.props.auth.user.roles.includes('Viewer')"
                    title="Корзина"
                    :active="route().current('trash.*')"
                    color="red"
                    rounded="lg"
                    class="mb-1"
                    @click="router.visit(route('trash.index'))"
                >
                    <template v-slot:prepend>
                        <Trash2 class="mr-3 h-5 w-5 opacity-70 nav-icon-color" />
                    </template>
                </v-list-item>
            </v-list>
        </v-navigation-drawer>

        <!-- AppBar / Header -->
        <v-app-bar elevation="0" class="border-b" color="surface">
            <template v-slot:prepend>
                <v-btn icon class="ml-1" @click="drawer = !drawer">
                    <Menu class="h-5 w-5" />
                </v-btn>
            </template>
            
            <v-toolbar-title class="font-weight-bold text-indigo-darken-3 text-subtitle-1">
                <slot name="header" />
            </v-toolbar-title>

            <v-spacer></v-spacer>

            <!-- User Menu -->
            <v-menu min-width="200px" rounded="xl">
                <template v-slot:activator="{ props: menuProps }">
                    <v-btn icon v-bind="menuProps" class="mr-3 hover-scale-btn">
                        <v-avatar color="indigo" size="38">
                            <span class="text-white text-subtitle-1 font-weight-bold">
                                {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                            </span>
                        </v-avatar>
                    </v-btn>
                </template>
                <v-card class="mt-2 border rounded-xl pa-3">
                    <v-card-text class="pa-2">
                        <div class="mx-auto text-center">
                            <v-avatar color="indigo" size="48" class="mb-2">
                                <span class="text-white text-h5 font-weight-bold">
                                    {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                                </span>
                            </v-avatar>
                            <h4 class="font-weight-bold text-indigo-darken-4">{{ $page.props.auth.user.name }}</h4>
                            <p class="text-caption text-grey mt-1 font-weight-medium">{{ $page.props.auth.user.email }}</p>
                            
                            <v-chip size="small" color="indigo" variant="tonal" class="mt-2 font-weight-black text-uppercase">
                                {{ $page.props.auth.user.roles.length > 0 ? $page.props.auth.user.roles.join(', ') : 'Нет роли' }}
                            </v-chip>

                            <v-divider class="my-3"></v-divider>
                            
                            <v-btn
                                variant="text"
                                rounded="lg"
                                block
                                class="text-left justify-start font-weight-bold text-grey-darken-3"
                                @click="router.visit(route('profile.edit'))"
                            >
                                <template v-slot:prepend>
                                    <User class="mr-2 h-4 w-4 text-indigo" />
                                </template>
                                Профиль
                            </v-btn>
                            
                            <v-btn
                                variant="text"
                                color="error"
                                rounded="lg"
                                block
                                class="text-left justify-start mt-1 font-weight-bold"
                                @click="logout"
                            >
                                <template v-slot:prepend>
                                    <LogOut class="mr-2 h-4 w-4" />
                                </template>
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
                        class="mb-4 rounded-lg font-weight-bold"
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
                        class="mb-4 rounded-lg font-weight-bold"
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
    font-weight: 800 !important;
}
.v-list-item--active .nav-icon-color {
    color: inherit !important;
    opacity: 1 !important;
}
.hover-scale-btn {
    transition: all 0.2s ease;
}
.hover-scale-btn:hover {
    transform: scale(1.08);
}
.text-white-50 {
    color: rgba(255, 255, 255, 0.7) !important;
}
</style>
