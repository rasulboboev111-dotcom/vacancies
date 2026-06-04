<?php

namespace App\Http\Requests\User;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', User::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => strtolower(trim((string) $this->input('email')))]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required', 'string', 'email', 'max:255',
                function ($attribute, $value, $fail) {
                    if (User::whereRaw('LOWER(TRIM(email)) = ?', [$value])->exists()) {
                        $fail('Корбар бо чунин почтаи электронӣ аллакай мавҷуд аст.');
                    }
                },
            ],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'branch_id' => [
                Rule::requiredIf(fn () => $this->input('role') === User::ROLE_USER),
                'nullable',
                'exists:branches,id',
            ],
            'role' => ['required', 'string', Rule::in([User::ROLE_ADMIN, User::ROLE_USER])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Почтаи электронӣ ҳатмист.',
            'email.email' => 'Формати почтаи электронӣ нодуруст аст.',
            'password.required' => 'Парол ҳатмист.',
            'password.confirmed' => 'Паролҳо мувофиқат намекунанд.',
            'role.required' => 'Нақш ҳатмист.',
            'branch_id.required' => 'Барои нақши «Корбар» бояд филиал зикр карда шавад.',
        ];
    }
}
