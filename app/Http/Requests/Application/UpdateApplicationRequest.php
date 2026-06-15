<?php

namespace App\Http\Requests\Application;

use App\Enums\ApplicationSource;
use App\Models\Application;
use App\Rules\VacancyInBranch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateApplicationRequest extends FormRequest
{
    private ?Application $application = null;

    public function authorize(): bool
    {
        return Gate::allows('update', $this->application());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Вакансия должна принадлежать филиалу заявки — иначе можно привязать
        // вакансию чужого филиала.
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'vacancy_id' => ['nullable', 'integer', new VacancyInBranch($this->application()->branch_id)],
            'source' => ['nullable', Rule::in(ApplicationSource::values())],
            'resume' => [
                'nullable',
                'file',
                'mimes:'.implode(',', config('intake.resume_mimes')),
                'max:'.config('intake.resume_max_kb'),
            ],
        ];
    }

    /**
     * Заявка из маршрута, загружаемая один раз за запрос (используется и в
     * authorize(), и в rules()).
     */
    private function application(): Application
    {
        return $this->application ??= Application::findOrFail($this->route('id'));
    }
}
