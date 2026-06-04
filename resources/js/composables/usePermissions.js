import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * Authorisation helpers shared by the management pages. Mirrors the server
 * policies: admins may do everything; branch users need the permission AND
 * must act within their own branch.
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
