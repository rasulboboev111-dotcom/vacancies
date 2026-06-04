import { describe, expect, it } from 'vitest';
import { branchSchema, employeeSchema, userSchema } from '@/lib/schemas';

function validEmployee(overrides = {}) {
    return {
        full_name: 'Иван Иванов',
        branch_id: 1,
        position_id: 2,
        category: 'Мутахассис',
        type_id: 'штатный',
        gender: 'мужской',
        hire_date: '2020-01-01',
        ...overrides,
    };
}

describe('employeeSchema', () => {
    it('rejects empty required fields with the right field paths', () => {
        const result = employeeSchema.safeParse({
            full_name: '',
            branch_id: null,
            position_id: null,
            category: null,
            type_id: null,
            gender: null,
            hire_date: '',
        });

        expect(result.success).toBe(false);
        const fields = result.error.issues.map(issue => issue.path[0]);
        for (const field of ['full_name', 'branch_id', 'position_id', 'category', 'type_id', 'gender', 'hire_date'])
            expect(fields).toContain(field);
    });

    it('requires a dismissal reason once a dismissal date is set', () => {
        const result = employeeSchema.safeParse(validEmployee({ dismissal_date: '2024-01-01', dismissal_reason: '' }));

        expect(result.success).toBe(false);
        expect(result.error.issues.some(issue => issue.path[0] === 'dismissal_reason')).toBe(true);
    });

    it('rejects a malformed email', () => {
        expect(employeeSchema.safeParse(validEmployee({ email: 'not-an-email' })).success).toBe(false);
        expect(employeeSchema.safeParse(validEmployee({ email: '' })).success).toBe(true);
    });

    it('accepts a complete, valid employee', () => {
        expect(employeeSchema.safeParse(validEmployee()).success).toBe(true);
    });
});

describe('branchSchema', () => {
    it('requires name and code', () => {
        const result = branchSchema.safeParse({ name: '', code: '', address: '' });
        const fields = result.error.issues.map(issue => issue.path[0]);
        expect(fields).toContain('name');
        expect(fields).toContain('code');
    });
});

describe('userSchema', () => {
    it('requires a password on create but not on edit', () => {
        const base = { name: 'A', email: 'a@b.co', password: '', password_confirmation: '', role: 'Admin', branch_id: null };
        expect(userSchema({ isCreate: true }).safeParse(base).success).toBe(false);
        expect(userSchema({ isCreate: false }).safeParse(base).success).toBe(true);
    });

    it('requires a branch for the User role', () => {
        const schema = userSchema({ isCreate: true });
        const base = { name: 'A', email: 'a@b.co', password: 'secret12', password_confirmation: 'secret12', role: 'User', branch_id: null };
        expect(schema.safeParse(base).success).toBe(false);
        expect(schema.safeParse({ ...base, branch_id: 3 }).success).toBe(true);
    });
});
