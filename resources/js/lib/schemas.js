// Client-side validation schemas (zod). These mirror the server-side
// FormRequest rules so the user gets instant feedback before submitting; the
// backend remains the source of truth and re-validates every request.
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
        // Required core fields — a null select fails the type check, which we
        // relabel as "this field is required".
        full_name: required255('Ному насаб'),
        branch_id: z.number({ invalid_type_error: 'Филиал ҳатмист', required_error: 'Филиал ҳатмист' }),
        position_id: z.number({ invalid_type_error: 'Вазифа ҳатмист', required_error: 'Вазифа ҳатмист' }),
        category: z.string({ invalid_type_error: 'Категория ҳатмист' }).min(1, 'Категория ҳатмист'),
        type_id: z.string({ invalid_type_error: 'Намуди шуғл ҳатмист' }).min(1, 'Намуди шуғл ҳатмист'),
        gender: z.string({ invalid_type_error: 'Ҷинс ҳатмист' }).min(1, 'Ҷинс ҳатмист'),
        hire_date: z.string({ required_error: 'Санаи қабул ҳатмист' }).min(1, 'Санаи қабул ҳатмист'),
        // Optional
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

export const vacancySchema = z.object({
    branch_id: z.number().nullish(),
    department_id: z.number().nullish(),
    position: z.string().trim().max(255, 'Ҳадди ниҳоӣ 255 аломат').nullish(),
    title: optional255,
    openings: z
        .number({ invalid_type_error: 'Шумора ҳатмист', required_error: 'Шумора ҳатмист' })
        .int('Бояд адади бутун бошад')
        .min(1, 'Камаш 1')
        .max(10000, 'Хеле зиёд'),
    employment_type: z.string().nullish(),
    requirements: z.string().max(5000, 'Ҳадди ниҳоӣ 5000 аломат').nullish(),
    schedule: z.string().max(255, 'Ҳадди ниҳоӣ 255 аломат').nullish(),
    // Salary is a whole number; an empty field is treated as "not specified" (null).
    salary: z.preprocess(
        v => (v === '' || v === null || v === undefined ? null : v),
        z.coerce.number({ invalid_type_error: 'Маош бояд рақам бошад' })
            .int('Маош бояд рақами бутун бошад')
            .min(0, 'Маош манфӣ буда наметавонад')
            .max(1000000000, 'Хеле зиёд')
            .nullable(),
    ),
    description: z.string().max(5000, 'Ҳадди ниҳоӣ 5000 аломат').nullish(),
    opened_at: z.string().nullish(),
    status: z.string().nullish(),
});

/**
 * The user form is reused for create and edit. On create a password is
 * required; on edit it is optional (blank = keep the current one). Either way,
 * if a password is given the confirmation must match, and the "User" role
 * requires a branch — mirroring StoreUserRequest/UpdateUserRequest.
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
