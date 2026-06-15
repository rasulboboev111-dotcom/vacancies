import { computed, watch } from 'vue';

/**
 * Поддерживает согласованность выбора отдела в форме с выбранным филиалом.
 *
 * Возвращает `branchDepartments` — отделы текущего филиала формы — и ставит
 * watcher, который очищает выбранный отдел, когда смена филиала делает его
 * чужим. Работает через геттеры/сеттер, поэтому одинаково подходит и форме
 * Inertia (`form.branch_id`), и полям vee-validate (`ref.value`), и computed.
 *
 * @param {object}   io
 * @param {function} io.getBranchId       — текущий branch_id формы
 * @param {function} io.getDepartmentId   — текущий department_id формы
 * @param {function} io.setDepartmentId   — сбрасывает department_id (вызывается с null)
 * @param {function} io.getDepartments    — полный список отделов (реактивный геттер)
 */
export function useBranchDepartments({ getBranchId, getDepartmentId, setDepartmentId, getDepartments }) {
    const branchDepartments = computed(() =>
        getDepartments().filter(d => Number(d.branch_id) === Number(getBranchId())),
    );

    watch(getBranchId, (newBranchId) => {
        const departmentId = getDepartmentId();
        if (
            departmentId
            && !getDepartments().some(
                d => Number(d.id) === Number(departmentId) && Number(d.branch_id) === Number(newBranchId),
            )
        ) {
            setDepartmentId(null);
        }
    });

    return { branchDepartments };
}
