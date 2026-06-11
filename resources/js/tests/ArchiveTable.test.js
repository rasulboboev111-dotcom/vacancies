import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import ArchiveTable from '@/Pages/Employees/ArchiveTable.vue';

const employee = {
    id: 5,
    full_name: 'Алиев Али',
    branch: { name: 'Душанбе' },
    position: { name: 'Барномасоз' },
    department: { name: 'IT' },
    category: 'A',
    hire_date: null,
    dismissal_date: null,
};

function mountTable(overrides = {}) {
    return mount(ArchiveTable, {
        props: { employees: [employee], canRestore: true, ...overrides },
    });
}

describe('archiveTable', () => {
    it('emits view when the row is clicked anywhere', async () => {
        const w = mountTable();

        await w.get('tr.employee-row').trigger('click');

        expect(w.emitted('view')).toHaveLength(1);
        expect(w.emitted('view')[0]).toEqual([employee]);
    });

    it('no longer renders a separate view (eye) action button', () => {
        const w = mountTable();

        // Only the restore button remains in the actions cell.
        expect(w.findAll('.hover-scale-btn')).toHaveLength(1);
    });

    it('keeps the restore button from bubbling into the row view event', async () => {
        const w = mountTable();

        await w.get('[title="Барқарор кардан"]').trigger('click');

        expect(w.emitted('restore')).toHaveLength(1);
        expect(w.emitted('view')).toBeUndefined();
    });
});
