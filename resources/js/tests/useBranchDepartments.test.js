import { describe, expect, it, vi } from 'vitest';
import { nextTick, ref } from 'vue';
import { useBranchDepartments } from '@/composables/useBranchDepartments';

const departments = [
    { id: 1, branch_id: 10 },
    { id: 2, branch_id: 10 },
    { id: 3, branch_id: 20 },
];

function setup(branch, department, setSpy) {
    const branchId = ref(branch);
    const departmentId = ref(department);
    const { branchDepartments } = useBranchDepartments({
        getBranchId: () => branchId.value,
        getDepartmentId: () => departmentId.value,
        setDepartmentId: setSpy ?? ((value) => { departmentId.value = value; }),
        getDepartments: () => departments,
    });
    return { branchId, departmentId, branchDepartments };
}

describe('useBranchDepartments', () => {
    it('filters departments by the current branch', () => {
        const { branchId, branchDepartments } = setup(10, null);

        expect(branchDepartments.value.map(d => d.id)).toEqual([1, 2]);

        branchId.value = 20;
        expect(branchDepartments.value.map(d => d.id)).toEqual([3]);
    });

    it('clears the department when the new branch no longer owns it', async () => {
        const { branchId, departmentId } = setup(10, 1);

        branchId.value = 20; // отдел 1 принадлежит филиалу 10, не 20
        await nextTick();

        expect(departmentId.value).toBeNull();
    });

    it('does nothing when no department is selected', async () => {
        const setSpy = vi.fn();
        const { branchId } = setup(10, null, setSpy);

        branchId.value = 20;
        await nextTick();

        expect(setSpy).not.toHaveBeenCalled();
    });
});
