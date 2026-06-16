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

    protected function prepareForValidation(): void
    {
        $user = $this->user();
        $current = $this->application();

        // Админ может перенести заявку в другой филиал; пользователь филиала —
        // нет (branch_id остаётся текущим). Это даёт vacancy_id валидироваться по
        // тому филиалу, который реально сохранится, а не по устаревшему.
        $branchId = $user->isAdmin()
            ? ($this->filled('branch_id') ? (int) $this->input('branch_id') : $current->branch_id)
            : $current->branch_id;

        $merge = ['branch_id' => $branchId];

        // Форма редактирования не присылает vacancy_id, поэтому он сохраняется
        // как есть. Но при смене филиала прежняя вакансия принадлежит другому
        // филиалу — отвязываем её, иначе остаётся межфилиальная связь, которую
        // VacancyInBranch должна предотвращать.
        if ($branchId !== $current->branch_id && ! $this->has('vacancy_id')) {
            $merge['vacancy_id'] = null;
        }

        $this->merge($merge);

        // Поле source трогаем только если оно прислано: пустое (очищенное) →
        // MANUAL, чтобы не записать null; отсутствующее — не меняем (иначе любой
        // правки контактов сбросил бы исходный канал, например hrbot).
        if ($this->has('source')) {
            $this->merge([
                'source' => $this->filled('source') ? $this->input('source') : ApplicationSource::MANUAL->value,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $branchId = $this->input('branch_id');

        // Вакансия должна принадлежать филиалу заявки — иначе можно привязать
        // вакансию чужого филиала.
        return [
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'vacancy_id' => ['nullable', 'integer', new VacancyInBranch($branchId !== null ? (int) $branchId : null)],
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
