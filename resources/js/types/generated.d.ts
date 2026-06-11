declare namespace App {
namespace Data {
export type DepartmentListItemData = {
id: number,
branch_id: number | null,
parent_id: number | null,
name: string,
short_name: string | null,
code: string | null,
sort_order: number | null,
children_count: number,
};
export type DepartmentTreeData = {
id: number,
name: string,
short_name: string | null,
code: string | null,
manager_id: number | null,
manager_name: string | null,
employees_count: number,
open_vacancies: number,
children: App.Data.DepartmentTreeData[],
};
export type VacancyData = {
id: number,
branch_id: number | null,
branch: Record<string, any> | null,
department_id: number | null,
department: Record<string, any> | null,
position_id: number | null,
position: Record<string, any> | null,
location: string | null,
openings: number,
supervisor: string | null,
education: string | null,
education_label: string | null,
experience: string | null,
experience_label: string | null,
languages: string[],
skills: string | null,
requirements: string | null,
responsibilities: string | null,
employment_type: string | null,
employment_type_label: string | null,
schedule_type: string | null,
schedule_type_label: string | null,
schedule_other: string | null,
work_format: string | null,
work_format_label: string | null,
salary: number | null,
probation: string | null,
probation_label: string | null,
probation_other: string | null,
opening_reason: string | null,
opening_reason_label: string | null,
priority: string | null,
priority_label: string | null,
status: string | null,
opened_at: string | null,
deadline: string | null,
closed_at: string | null,
creator: Record<string, any> | null,
};
}
namespace Enums {
export type Category = 'Мутахассис' | 'Коргар' | 'Роҳбарият';
export type Education = 'высшее' | 'среднее специальное' | 'не имеет значения';
export type EmploymentType = 'штатный' | 'контракт';
export type Experience = 'без опыта' | 'от 1 года' | 'от 3 лет и более';
export type Gender = 'мужской' | 'женский';
export type OpeningReason = 'расширение штата' | 'новая позиция' | 'замена уволенного сотрудника' | 'декретная ставка / временное замещение';
export type OrgStatus = 'Active' | 'Inactive';
export type Probation = 'нет' | '1 месяц' | '3 месяца' | 'иное';
export type ScheduleType = '5/2' | 'иной';
export type VacancyEmploymentType = 'полная' | 'частичная' | 'проектная';
export type VacancyPriority = 'низкая' | 'средняя' | 'высокая';
export type VacancyStatus = 'open' | 'closed';
export type WorkFormat = 'офис' | 'удалённо' | 'гибрид';
}
}
