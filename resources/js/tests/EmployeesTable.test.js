import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import EmployeesTable from '@/Pages/Employees/EmployeesTable.vue';

const employee = {
    id: 3,
    full_name: 'Алиев Али',
    position: { name: 'Барномасоз' },
    branch: { name: 'Душанбе' },
    category: 'A',
    employment_type_label: 'Доимӣ',
    phone_number: '900',
    birth_date: null,
    employment_start_date: null,
};

const employees = {
    data: [employee],
    from: 1,
    to: 1,
    total: 1,
    last_page: 1,
    current_page: 1,
};

function mountTable(overrides = {}) {
    return mount(EmployeesTable, {
        props: { employees, canManage: () => true, ...overrides },
    });
}

describe('employeesTable', () => {
    it('emits view when the row is clicked anywhere', async () => {
        const w = mountTable();

        await w.get('tr.employee-row').trigger('click');

        expect(w.emitted('view')).toHaveLength(1);
        expect(w.emitted('view')[0]).toEqual([employee]);
    });

    it('no longer renders a separate view (eye) action button', () => {
        const w = mountTable();

        expect(w.find('[title="Дидан"]').exists()).toBe(false);
    });

    it('keeps action buttons from bubbling into the row view event', async () => {
        const w = mountTable();

        await w.get('[title="Таҳрир"]').trigger('click');

        expect(w.emitted('edit')).toHaveLength(1);
        expect(w.emitted('view')).toBeUndefined();
    });
});
