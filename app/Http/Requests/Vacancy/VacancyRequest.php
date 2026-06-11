<?php

namespace App\Http\Requests\Vacancy;

use App\Enums\Education;
use App\Enums\Experience;
use App\Enums\OpeningReason;
use App\Enums\Probation;
use App\Enums\ScheduleType;
use App\Enums\VacancyEmploymentType;
use App\Enums\VacancyPriority;
use App\Enums\WorkFormat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Общая валидация для создания и обновления вакансии — поля формы
 * «Заявка на подбор персонала». Потомки привязывают branch_id в собственном
 * prepareForValidation() и могут ужесточать отдельные правила.
 */
abstract class VacancyRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $branchId = (int) $this->input('branch_id');

        $rules = [
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')],
            'department_id' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')->where('branch_id', $branchId)->whereNull('deleted_at'),
            ],
            'position' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'openings' => ['sometimes', 'required', 'integer', 'min:1', 'max:10000'],
            'supervisor' => ['nullable', 'string', 'max:255'],
            'education' => ['nullable', Rule::enum(Education::class)],
            'experience' => ['nullable', Rule::enum(Experience::class)],
            'languages' => ['nullable', 'array', 'max:10'],
            'languages.*' => ['string', 'max:100', 'distinct:ignore_case'],
            'skills' => ['nullable', 'string', 'max:5000'],
            'requirements' => ['nullable', 'string', 'max:5000'],
            'responsibilities' => ['nullable', 'string', 'max:5000'],
            'employment_type' => ['nullable', Rule::enum(VacancyEmploymentType::class)],
            'schedule_type' => ['nullable', Rule::enum(ScheduleType::class)],
            // «Иной» график обязан нести текст — иначе бланк печатает «Иной: ___» без значения.
            'schedule_other' => ['nullable', 'required_if:schedule_type,'.ScheduleType::OTHER->value, 'string', 'max:255'],
            'work_format' => ['nullable', Rule::enum(WorkFormat::class)],
            'salary' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'probation' => ['nullable', Rule::enum(Probation::class)],
            'probation_other' => ['nullable', 'required_if:probation,'.Probation::OTHER->value, 'string', 'max:255'],
            'opening_reason' => ['nullable', Rule::enum(OpeningReason::class)],
            'priority' => ['nullable', Rule::enum(VacancyPriority::class)],
            'opened_at' => ['nullable', 'date'],
            'deadline' => ['nullable', 'date'],
        ];

        // Дедлайн не может быть раньше даты заявки. Проверяем только когда дата
        // подачи задана: после её ввода after_or_equal сравнивает поля, иначе
        // правило приняло бы «opened_at» за литеральную дату и отклоняло бы любой дедлайн.
        if ($this->filled('opened_at')) {
            $rules['deadline'][] = 'after_or_equal:opened_at';
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'branch_id' => 'филиал',
            'department_id' => 'структурное подразделение',
            'position' => 'должность',
            'location' => 'место деятельности',
            'openings' => 'количество вакансий',
            'supervisor' => 'непосредственный руководитель',
            'education' => 'образование',
            'experience' => 'опыт работы',
            'languages' => 'знание языков',
            'languages.*' => 'язык',
            'skills' => 'ключевые навыки',
            'requirements' => 'дополнительные требования',
            'responsibilities' => 'основные обязанности',
            'employment_type' => 'тип занятости',
            'schedule_type' => 'график работы',
            'schedule_other' => 'иной график',
            'work_format' => 'формат работы',
            'salary' => 'уровень дохода',
            'probation' => 'испытательный срок',
            'probation_other' => 'иной испытательный срок',
            'opening_reason' => 'причина открытия позиции',
            'priority' => 'приоритет',
            'opened_at' => 'дата подачи заявки',
            'deadline' => 'планируемая дата закрытия',
        ];
    }
}
