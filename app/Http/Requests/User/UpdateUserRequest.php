<?php

namespace App\Http\Requests\User;

use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = User::find($this->route('id'));

        return $user !== null && Gate::allows('update', $user);
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
        $ignoreId = $this->route('id');

        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required', 'string', 'email', 'max:255',
                function ($attribute, $value, $fail) use ($ignoreId) {
                    if (User::whereRaw('LOWER(TRIM(email)) = ?', [$value])->where('id', '!=', $ignoreId)->exists()) {
                        $fail('Корбар бо чунин почтаи электронӣ аллакай мавҷуд аст.');
                    }
                },
            ],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'branch_id' => [
                Rule::requiredIf(fn () => $this->input('role') === User::ROLE_USER),
                'nullable',
                'exists:branches,id',
            ],
            'role' => ['required', 'string', Rule::in([User::ROLE_ADMIN, User::ROLE_USER])],
        ];
    }

    /**
     * Нельзя понизить единственного администратора — иначе управление
     * пользователями (доступное только админам) станет недостижимым.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $target = User::find($this->route('id'));

            if ($target === null || ! $target->isAdmin()) {
                return;
            }

            if ($this->input('role') !== User::ROLE_ADMIN
                && User::role(User::ROLE_ADMIN)->count() <= 1) {
                $validator->errors()->add('role', 'Охирин администраторро аз нақши «Админ» хориҷ кардан мумкин нест.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Почтаи электронӣ ҳатмист.',
            'email.email' => 'Формати почтаи электронӣ нодуруст аст.',
            'password.confirmed' => 'Паролҳо мувофиқат намекунанд.',
            'role.required' => 'Нақш ҳатмист.',
            'branch_id.required' => 'Барои нақши «Корбар» бояд филиал зикр карда шавад.',
        ];
    }
}
