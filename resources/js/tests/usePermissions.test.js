import { beforeEach, describe, expect, it, vi } from 'vitest';

import { usePermissions } from '@/composables/usePermissions';

// Controllable mock of the Inertia page so we can vary the auth user per test.
const { state } = vi.hoisted(() => ({ state: { user: null } }));

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => ({ props: { auth: { user: state.user } } }),
}));

describe('usePermissions', () => {
    beforeEach(() => {
        state.user = null;
    });

    it('grants everything to an admin', () => {
        state.user = { roles: ['Admin'], permissions: [], branch_id: null };
        const p = usePermissions();

        expect(p.isAdmin.value).toBe(true);
        expect(p.canManageInBranch('edit employees', 999)).toBe(true);
        expect(p.canCreateInBranch('create employees')).toBe(true);
    });

    it('limits a branch user to their own branch and granted permission', () => {
        state.user = { roles: ['User'], permissions: ['edit employees', 'create employees'], branch_id: 5 };
        const p = usePermissions();

        expect(p.isAdmin.value).toBe(false);
        expect(p.canManageInBranch('edit employees', 5)).toBe(true); // own branch
        expect(p.canManageInBranch('edit employees', 6)).toBe(false); // other branch
        expect(p.canManageInBranch('delete employees', 5)).toBe(false); // missing permission
        expect(p.canCreateInBranch('create employees')).toBe(true);
    });

    it('blocks a branchless non-admin from creating', () => {
        state.user = { roles: ['User'], permissions: ['create employees'], branch_id: null };
        const p = usePermissions();

        expect(p.canCreateInBranch('create employees')).toBe(false);
    });

    it('treats a missing user as no permissions', () => {
        state.user = null;
        const p = usePermissions();

        expect(p.isAdmin.value).toBe(false);
        expect(p.hasPermission('edit employees')).toBe(false);
    });
});
