import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import EmployeeRotationDialog from '@/Pages/Employees/EmployeeRotationDialog.vue';

// Inertia form mock — the dialog calls form.post on submit.
const { formMock } = vi.hoisted(() => ({
    formMock: {
        branch_id: null,
        position_id: null,
        department_id: null,
        rotation_date: '',
        reason: '',
        processing: false,
        errors: {},
        post: vi.fn(),
        reset: vi.fn(),
        clearErrors: vi.fn(),
    },
}));

vi.mock('@inertiajs/vue3', () => ({ useForm: () => formMock }));

const rotateBtn = w => w.findAll('button').find(b => b.text().includes('Иҷрои ротатсия'));

beforeEach(() => {
    formMock.post.mockClear();
});

describe('employeeRotationDialog', () => {
    it('submits the rotation preserving page state', async () => {
        const w = mount(EmployeeRotationDialog, {
            props: {
                modelValue: true,
                employee: { id: 9, full_name: 'Алӣ', branch_id: 1, position_id: 2, department_id: null },
                branches: [{ id: 1, name: 'Филиал-1' }],
                positions: [{ id: 2, name: 'Муҳандис' }],
                departments: [],
                isAdmin: true,
            },
        });

        await rotateBtn(w).trigger('click');

        // preserveState keeps the host (e.g. the structure page's "Кормандон"
        // tab) mounted across the back() redirect after a successful rotation.
        expect(formMock.post).toHaveBeenCalledWith(
            '/employees.rotate/9',
            expect.objectContaining({ preserveState: true }),
        );
        // Scroll is not preserved — the list jumps to the (re-sorted) top.
        expect(formMock.post.mock.calls[0][1].preserveScroll).toBeUndefined();
    });
});
