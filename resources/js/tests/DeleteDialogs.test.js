import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import EmployeeDeleteDialog from '@/Pages/Employees/EmployeeDeleteDialog.vue';
import BranchDeleteDialog from '@/Pages/Structure/BranchDeleteDialog.vue';
import DepartmentDeleteDialog from '@/Pages/Structure/DepartmentDeleteDialog.vue';

// Shared form mock — ConfirmDeleteDialog (used by all three wrappers) calls it.
const { formMock } = vi.hoisted(() => ({
    formMock: { processing: false, delete: vi.fn() },
}));

vi.mock('@inertiajs/vue3', () => ({ useForm: () => formMock }));

const deleteBtn = w => w.findAll('button').find(b => b.text().includes('Нест кардан'));

beforeEach(() => {
    formMock.delete.mockClear();
    formMock.processing = false;
});

describe('migrated delete dialogs (ConfirmDeleteDialog wiring)', () => {
    it('employeeDeleteDialog deletes the employee route preserving page state', async () => {
        const w = mount(EmployeeDeleteDialog, {
            props: { modelValue: true, employee: { id: 7, full_name: 'Алӣ' } },
        });
        expect(w.text()).toContain('Алӣ');
        await deleteBtn(w).trigger('click');
        // preserveState keeps the host page mounted so the embedded "Кормандон"
        // tab on the structure page does not reset after the back() redirect.
        expect(formMock.delete).toHaveBeenCalledWith('/employees.destroy/7', expect.objectContaining({ preserveState: true }));
    });

    it('branchDeleteDialog deletes the branch route with preserveScroll, without preserveState', async () => {
        const w = mount(BranchDeleteDialog, {
            props: { modelValue: true, branch: { id: 3, name: 'Филиал-3' } },
        });
        await deleteBtn(w).trigger('click');
        // preserveState stays opt-in: non-employee deletes keep the default (false).
        expect(formMock.delete).toHaveBeenCalledWith('/branches.destroy/3', expect.objectContaining({ preserveScroll: true, preserveState: false }));
    });

    it('departmentDeleteDialog blocks deletion when it has children', async () => {
        const w = mount(DepartmentDeleteDialog, {
            props: { modelValue: true, department: { id: 4, name: 'Шуъба', children_count: 2 } },
        });
        await deleteBtn(w).trigger('click');
        expect(formMock.delete).not.toHaveBeenCalled();
    });

    it('departmentDeleteDialog deletes when it has no children', async () => {
        const w = mount(DepartmentDeleteDialog, {
            props: { modelValue: true, department: { id: 4, name: 'Шуъба', children_count: 0 } },
        });
        await deleteBtn(w).trigger('click');
        expect(formMock.delete).toHaveBeenCalledWith('/departments.destroy/4', expect.any(Object));
    });
});
