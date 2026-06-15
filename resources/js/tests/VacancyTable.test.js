import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import VacancyTable from '@/Pages/Vacancies/VacancyTable.vue';

const vacancy = {
    id: 7,
    status: 'open',
    position: { name: 'Барномасоз' },
    department: { name: 'Шуъбаи IT' },
    branch: { name: 'Душанбе' },
    employment_type_label: 'Доимӣ',
    salary: 5000,
};

function mountTable(overrides = {}) {
    return mount(VacancyTable, {
        props: {
            vacancies: [vacancy],
            canManage: () => true,
            canDelete: () => true,
            ...overrides,
        },
    });
}

describe('vacancyTable', () => {
    it('emits view when the row is clicked anywhere', async () => {
        const w = mountTable();

        await w.get('tr.vacancy-row').trigger('click');

        expect(w.emitted('view')).toHaveLength(1);
        expect(w.emitted('view')[0]).toEqual([vacancy]);
    });

    it('truncates the name to one line and keeps the full text in the title', () => {
        const w = mountTable();

        const name = w.get('.vac-name');
        expect(name.classes()).toContain('text-truncate');
        expect(name.attributes('title')).toBe(vacancy.position.name);
    });

    it('no longer renders a separate view (eye) action button', () => {
        const w = mountTable();

        // The «Дидан» eye button was removed — the whole row opens the view now.
        expect(w.find('[title="Дидан"]').exists()).toBe(false);
    });

    it('keeps action buttons from bubbling into the row view event', async () => {
        const w = mountTable();

        await w.get('[title="Чопи заявка"]').trigger('click');

        expect(w.emitted('print')).toHaveLength(1);
        expect(w.emitted('view')).toBeUndefined();
    });
});
