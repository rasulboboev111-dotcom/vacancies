import { computed, watch } from 'vue';

/**
 * Поддерживает согласованность выбора отдела в форме с выбранным филиалом.
 *
 * Возвращает `branchDepartments` — отделы, принадлежащие текущему филиалу формы —
 * и устанавливает watcher, который очищает `form.department_id`, когда смена
 * выбранного филиала приводит к тому, что отдел ему больше не принадлежит.
 *
 * `departmentsGetter` — функция, возвращающая полный список отделов, так что
 * composable остаётся реактивным к изменениям пропсов. `form` — это форма Inertia,
 * содержащая `branch_id` и `department_id`.
 */
export function useBranchDepartments(form, departmentsGetter) {
    const branchDepartments = computed(() =>
        departmentsGetter().filter(d => Number(d.branch_id) === Number(form.branch_id)),
    );

    watch(() => form.branch_id, (newBranchId) => {
        if (
            form.department_id
            && !departmentsGetter().some(
                d => Number(d.id) === Number(form.department_id) && Number(d.branch_id) === Number(newBranchId),
            )
        ) {
            form.department_id = null;
        }
    });

    return { branchDepartments };
}
