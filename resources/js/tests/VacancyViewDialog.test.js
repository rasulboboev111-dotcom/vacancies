import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import VacancyViewDialog from '@/Pages/Vacancies/VacancyViewDialog.vue';

const vacancy = {
    id: 1,
    status: 'open',
    position: { name: 'Барномасоз' },
    department: { name: 'Шуъбаи IT' },
    languages: [],
    skills: null,
    requirements: null,
    responsibilities: null,
};

describe('vacancyViewDialog', () => {
    it('renders all six form sections', () => {
        const w = mount(VacancyViewDialog, {
            props: { modelValue: true, vacancy },
        });

        const text = w.text();
        expect(text).toContain('1. Информация о вакансии');
        expect(text).toContain('2. Требования к кандидату');
        expect(text).toContain('3. Должностные обязанности');
        expect(text).toContain('4. Условия работы');
        expect(text).toContain('5. Причина открытия позиции и сроки');
        expect(text).toContain('6. Согласование заявки');
    });

    it('shows every field label even when the value is empty', () => {
        const w = mount(VacancyViewDialog, {
            props: { modelValue: true, vacancy },
        });

        const text = w.text();
        for (const label of [
            'Ключевые навыки и знание программ',
            'Дополнительные требования к кандидату',
            'Основные обязанности',
            'Знание языков',
            'Испытательный срок',
            'Статус вакансии',
        ]) {
            expect(text).toContain(label);
        }
    });

    it('hides the branch field from non-admins and shows it to admins', () => {
        const base = { modelValue: true, vacancy };

        expect(mount(VacancyViewDialog, { props: base }).text()).not.toContain('Филиал');
        expect(mount(VacancyViewDialog, { props: { ...base, isAdmin: true } }).text()).toContain('Филиал');
    });
});
