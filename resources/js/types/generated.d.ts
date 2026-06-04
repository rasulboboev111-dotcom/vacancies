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
title: string,
openings: number,
employment_type: string | null,
requirements: string | null,
schedule: string | null,
salary: string | null,
description: string | null,
status: string | null,
opened_at: string | null,
closed_at: string | null,
creator: Record<string, any> | null,
};
}
namespace Enums {
export type Category = 'Мутахассис' | 'Коргар' | 'Роҳбарият';
export type EmploymentType = 'штатный' | 'контракт';
export type Gender = 'мужской' | 'женский';
export type OrgStatus = 'Active' | 'Inactive';
export type VacancyStatus = 'open' | 'closed';
}
}
