import { flushPromises, mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import PositionEmployeesDialog from '@/Pages/Positions/PositionEmployeesDialog.vue';

describe('positionEmployeesDialog', () => {
    it('lazy-loads and lists the position staff (name + department + branch) when opened', async () => {
        window.axios = {
            get: vi.fn().mockResolvedValue({
                data: {
                    employees: [
                        { id: 1, full_name: 'Bobur', department: 'IT', branch: 'Branch' },
                    ],
                },
            }),
        };

        const w = mount(PositionEmployeesDialog, {
            props: { modelValue: false, position: { id: 7, name: 'Developer' } },
        });

        // The fetch is deferred until the dialog actually opens.
        expect(window.axios.get).not.toHaveBeenCalled();

        await w.setProps({ modelValue: true });
        await flushPromises();

        expect(window.axios.get).toHaveBeenCalledTimes(1);
        const text = w.text();
        expect(text).toContain('Bobur');
        expect(text).toContain('IT');
        expect(text).toContain('Branch');
    });

    it('shows an empty state when nobody holds the position', async () => {
        window.axios = {
            get: vi.fn().mockResolvedValue({ data: { employees: [] } }),
        };

        const w = mount(PositionEmployeesDialog, {
            props: { modelValue: false, position: { id: 8, name: 'Empty' } },
        });

        await w.setProps({ modelValue: true });
        await flushPromises();

        expect(w.text()).toContain('Дар ин вазифа ҳоло корманд нест.');
    });
});
