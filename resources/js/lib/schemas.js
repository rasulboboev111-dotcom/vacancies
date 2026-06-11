// Клиентские схемы валидации (zod). Они повторяют серверные правила FormRequest,
// чтобы пользователь получал мгновенную обратную связь до отправки; бэкенд
// остаётся источником истины и перепроверяет каждый запрос.
import { z } from 'zod';

function required255(label) {
    return z
        .string({ required_error: `${label} ҳатмист` })
        .trim()
        .min(1, `${label} ҳатмист`)
        .max(255, 'Ҳадди ниҳоӣ 255 аломат');
}

export const positionSchema = z.object({
    name: required255('Номи вазифа'),
});

export const branchSchema = z.object({
    name: required255('Номи филиал'),
    code: z
        .string({ required_error: 'Рамз ҳатмист' })
        .trim()
        .min(1, 'Рамз ҳатмист')
        .max(10, 'Ҳадди ниҳоӣ 10 аломат'),
    address: z.string().trim().max(255, 'Ҳадди ниҳоӣ 255 аломат').nullish(),
});

export const departmentSchema = z.object({
    parent_id: z.number().nullish(),
    name: required255('Номи шуъба'),
    code: z.string().trim().max(20, 'Ҳадди ниҳоӣ 20 аломат').nullish(),
});

const optional255 = z.string().max(255, 'Ҳадди ниҳоӣ 255 аломат').nullish();

export const employeeSchema = z
    .object({
        // Обязательные основные поля — пустой select не проходит проверку типа,
        // что мы переименовываем в «это поле обязательно».
        full_name: required255('Ному насаб'),
        branch_id: z.number({ invalid_type_error: 'Филиал ҳатмист', required_error: 'Филиал ҳатмист' }),
        position_id: z.number({ invalid_type_error: 'Вазифа ҳатмист', required_error: 'Вазифа ҳатмист' }),
        category: z.string({ invalid_type_error: 'Категория ҳатмист' }).min(1, 'Категория ҳатмист'),
        type_id: z.string({ invalid_type_error: 'Намуди шуғл ҳатмист' }).min(1, 'Намуди шуғл ҳатмист'),
        gender: z.string({ invalid_type_error: 'Ҷинс ҳатмист' }).min(1, 'Ҷинс ҳатмист'),
        hire_date: z.string({ required_error: 'Санаи қабул ҳатмист' }).min(1, 'Санаи қабул ҳатмист'),
        // Необязательные
        department_id: z.number().nullish(),
        manager_id: z.number().nullish(),
        dismissal_date: z.string().nullish(),
        dismissal_reason: z.string().max(500, 'Ҳадди ниҳоӣ 500 аломат').nullish(),
        birth_date: z.string().nullish(),
        employment_start_date: z.string().nullish(),
        passport_start_date: z.string().nullish(),
        passport_end_date: z.string().nullish(),
        passport_number: optional255,
        passport_issued_by: optional255,
        inn: z.string().max(50, 'Ҳадди ниҳоӣ 50 аломат').nullish(),
        sin: optional255,
        address: optional255,
        phone_number: z.string().nullish(),
        email: z.union([z.literal(''), z.string().email('Формати почта нодуруст').max(255)]).nullish(),
        nationality: optional255,
        birth_place: optional255,
        education: optional255,
        specialty: optional255,
    })
    .refine(
        data => !data.dismissal_date || !!(data.dismissal_reason && data.dismissal_reason.trim()),
        { message: 'Сабаби озодшавӣ ҳатмист', path: ['dismissal_reason'] },
    );

// Повторяет форму «Заявка на подбор персонала»: каждый раздел на бумажной форме
// необязателен, поэтому здесь проверяются только число вакансий и принадлежность
// к группам выбора. `language_other` существует только на клиенте — он
// объединяется в массив languages перед отправкой.
export const vacancySchema = z.object({
    branch_id: z.number().nullish(),
    department_id: z.number().nullish(),
    position: z.string().trim().max(255, 'Максимум 255 символов').nullish(),
    location: z.string().max(255, 'Максимум 255 символов').nullish(),
    openings: z
        .number({ invalid_type_error: 'Укажите количество', required_error: 'Укажите количество' })
        .int('Должно быть целым числом')
        .min(1, 'Минимум 1')
        .max(10000, 'Слишком много'),
    supervisor: z.string().max(255, 'Максимум 255 символов').nullish(),
    education: z.string().nullish(),
    experience: z.string().nullish(),
    languages: z.array(z.string()).nullish(),
    language_other: z.string().max(100, 'Максимум 100 символов').nullish(),
    skills: z.string().max(5000, 'Максимум 5000 символов').nullish(),
    requirements: z.string().max(5000, 'Максимум 5000 символов').nullish(),
    responsibilities: z.string().max(5000, 'Максимум 5000 символов').nullish(),
    employment_type: z.string().nullish(),
    schedule_type: z.string().nullish(),
    schedule_other: z.string().max(255, 'Максимум 255 символов').nullish(),
    work_format: z.string().nullish(),
    // Доход — целое число; пустое поле трактуется как «не указано» (null).
    salary: z.preprocess(
        v => (v === '' || v === null || v === undefined ? null : v),
        z.coerce.number({ invalid_type_error: 'Доход должен быть числом' })
            .int('Доход должен быть целым числом')
            .min(0, 'Доход не может быть отрицательным')
            .max(1000000000, 'Слишком много')
            .nullable(),
    ),
    probation: z.string().nullish(),
    probation_other: z.string().max(255, 'Максимум 255 символов').nullish(),
    opening_reason: z.string().nullish(),
    priority: z.string().nullish(),
    opened_at: z.string().nullish(),
    deadline: z.string().nullish(),
    status: z.string().nullish(),
});

/**
 * Форма пользователя переиспользуется для создания и редактирования. При создании
 * пароль обязателен; при редактировании необязателен (пусто = оставить текущий).
 * В любом случае, если пароль задан, подтверждение должно совпадать, а роль "User"
 * требует филиала — повторяя StoreUserRequest/UpdateUserRequest.
 */
export function userSchema({ isCreate }) {
    const password = isCreate
        ? z.string({ required_error: 'Парол ҳатмист' }).min(8, 'Камаш 8 аломат')
        : z.string().min(8, 'Камаш 8 аломат').or(z.literal('')).optional();

    return z
        .object({
            name: required255('Номи корбар'),
            email: z
                .string({ required_error: 'Почта ҳатмист' })
                .trim()
                .min(1, 'Почта ҳатмист')
                .email('Формати почта нодуруст')
                .max(255, 'Ҳадди ниҳоӣ 255 аломат'),
            password,
            password_confirmation: z.string().or(z.literal('')).optional(),
            role: z.string({ required_error: 'Нақш ҳатмист' }).min(1, 'Нақш ҳатмист'),
            branch_id: z.number().nullish(),
        })
        .refine(data => !data.password || data.password === data.password_confirmation, {
            message: 'Паролҳо мувофиқат намекунанд',
            path: ['password_confirmation'],
        })
        .refine(data => data.role !== 'User' || data.branch_id != null, {
            message: 'Барои нақши «Корбар» филиал ҳатмист',
            path: ['branch_id'],
        });
}
