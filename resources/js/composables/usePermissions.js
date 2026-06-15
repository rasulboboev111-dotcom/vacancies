import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * Хелперы авторизации, общие для страниц управления. Повторяют серверные
 * политики: админам можно всё; пользователям филиала нужно разрешение И они
 * должны действовать в пределах своего филиала.
 */
export function usePermissions() {
    const page = usePage();
    const user = computed(() => page.props.auth.user);
    const isAdmin = computed(() => user.value?.roles?.includes('Admin') ?? false);

    const hasPermission = permission => user.value?.permissions?.includes(permission) ?? false;

    function canManageInBranch(permission, branchId) {
        if (isAdmin.value)
            return true;
        if (!hasPermission(permission))
            return false;
        return Number(branchId) === Number(user.value?.branch_id);
    }

    function canCreateInBranch(permission) {
        if (isAdmin.value)
            return true;
        return hasPermission(permission) && user.value?.branch_id != null;
    }

    return { user, isAdmin, hasPermission, canManageInBranch, canCreateInBranch };
}
