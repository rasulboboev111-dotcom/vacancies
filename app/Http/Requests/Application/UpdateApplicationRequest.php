<?php

namespace App\Http\Requests\Application;

use App\Models\Application;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $application = Application::findOrFail($this->route('id'));

        return Gate::allows('update', $application);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'vacancy_id' => ['nullable', 'integer', 'exists:vacancies,id'],
            'source' => ['nullable', 'string', 'max:32'],
            'resume' => [
                'nullable',
                'file',
                'mimes:'.implode(',', config('intake.resume_mimes')),
                'max:'.config('intake.resume_max_kb'),
            ],
        ];
    }
}
