<script setup>
import { Building2, Globe, MoreVertical, Pencil, Trash2, User } from '@lucide/vue';
import { formatDate } from '@/lib/date';
import { getRoleColor, getRoleLabel, getRoleVariant } from '@/Pages/Users/userRoles';

defineProps({
    users: { type: Array, required: true },
    currentUserId: { type: Number, default: null },
    // Правка/удаление пользователей — только суперадмин; обычный админ лишь
    // добавляет, поэтому меню действий ему не показывается.
    canManage: { type: Boolean, default: false },
});

defineEmits(['edit', 'delete']);
</script>

<template>
    <v-card elevation="0" class="rounded-xl border bg-surface-glass overflow-hidden">
        <v-table class="bg-transparent text-left">
            <thead>
                <tr class="bg-indigo-lighten-5">
                    <th class="font-weight-black text-indigo-darken-4 py-4 px-6 text-subtitle-2 text-uppercase text-left">
                        Корбар
                    </th>
                    <th class="font-weight-black text-indigo-darken-4 py-4 px-6 text-subtitle-2 text-uppercase text-left">
                        Почтаи электронӣ
                    </th>
                    <th class="font-weight-black text-indigo-darken-4 py-4 px-6 text-subtitle-2 text-uppercase text-left">
                        Нақш
                    </th>
                    <th class="font-weight-black text-indigo-darken-4 py-4 px-6 text-subtitle-2 text-uppercase text-left">
                        Филиал
                    </th>
                    <th class="font-weight-black text-indigo-darken-4 py-4 px-6 text-subtitle-2 text-uppercase text-center">
                        Бақайдгирӣ
                    </th>
                    <th class="font-weight-black text-indigo-darken-4 py-4 px-6 text-subtitle-2 text-uppercase text-right">
                        Амалҳо
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="user in users" :key="user.id" class="hover-row transition-colors">
                    <td class="py-4 px-6">
                        <div class="d-flex align-center">
                            <v-avatar color="indigo-lighten-4" class="mr-3" size="38">
                                <span class="text-indigo-darken-3 font-weight-black text-subtitle-2">
                                    {{ user.name.charAt(0).toUpperCase() }}
                                </span>
                            </v-avatar>
                            <div>
                                <div class="font-weight-bold text-grey-darken-4">
                                    {{ user.name }}
                                </div>
                                <div v-if="user.id === currentUserId" class="text-caption text-indigo font-weight-bold">
                                    Ин шумо
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="py-4 px-6 font-weight-medium text-grey-darken-2">
                        {{ user.email }}
                    </td>
                    <td class="py-4 px-6">
                        <v-chip
                            :color="getRoleColor(user.roles[0]?.name)"
                            size="small"
                            :variant="getRoleVariant(user.roles[0]?.name)"
                            class="font-weight-black"
                        >
                            {{ getRoleLabel(user.roles[0]?.name) }}
                        </v-chip>
                    </td>
                    <td class="py-4 px-6">
                        <div v-if="user.branch" class="d-flex align-center">
                            <Building2 style="width: 14px; height: 14px; margin-right: 6px;" class="text-grey" />
                            <span class="font-weight-medium text-grey-darken-3">{{ user.branch.name }}</span>
                        </div>
                        <v-chip v-else color="indigo" size="small" variant="tonal" class="font-weight-bold">
                            <Globe style="width: 14px; height: 14px; margin-right: 6px;" />
                            Ҳамаи филиалҳо
                        </v-chip>
                    </td>
                    <td class="py-4 px-6 font-weight-medium text-grey-darken-2 text-center">
                        {{ formatDate(user.created_at) }}
                    </td>
                    <td class="py-4 px-6 text-right">
                        <!-- Сам аккаунт суперадмина через UI не редактируется/удаляется. -->
                        <v-menu v-if="canManage && !user.roles?.some((r) => r.name === 'Superadmin')">
                            <template #activator="{ props: menuProps }">
                                <v-btn icon variant="text" size="small" class="hover-scale-btn" v-bind="menuProps">
                                    <MoreVertical style="width: 16px; height: 16px;" />
                                </v-btn>
                            </template>
                            <v-list density="comfortable" rounded="xl" class="border py-1">
                                <v-list-item title="Таҳрир" class="font-weight-bold" @click="$emit('edit', user)">
                                    <template #prepend>
                                        <Pencil style="width: 16px; height: 16px; margin-right: 8px;" class="text-primary" />
                                    </template>
                                </v-list-item>
                                <v-list-item
                                    v-if="user.id !== currentUserId"
                                    title="Нест кардан"
                                    class="text-black font-weight-bold"
                                    @click="$emit('delete', user)"
                                >
                                    <template #prepend>
                                        <Trash2 style="width: 16px; height: 16px; margin-right: 8px;" class="text-error" />
                                    </template>
                                </v-list-item>
                            </v-list>
                        </v-menu>
                    </td>
                </tr>
                <tr v-if="users.length === 0">
                    <td colspan="6" class="text-center py-12">
                        <User style="width: 48px; height: 48px; margin: 0 auto 8px; opacity: 0.5;" class="text-grey" />
                        <div class="text-h6 text-grey font-weight-medium">
                            Корбарон ёфт нашуданд.
                        </div>
                    </td>
                </tr>
            </tbody>
        </v-table>
    </v-card>
</template>

<style scoped>
.hover-row:hover {
    background-color: rgba(224, 231, 255, 0.25) !important;
}
</style>
