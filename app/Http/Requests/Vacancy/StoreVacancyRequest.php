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
use App\Models\Vacancy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreVacancyRequest extends FormRequest
{
    /**
     * The branch_id the request originally asked for, captured before it is
     * normalized in prepareForValidation() so authorize() can detect a branch
     * user trying to target another branch.
     */
    private ?int $requestedBranchId = null;

    public function authorize(): bool
    {
        if (! Gate::allows('create', Vacancy::class)) {
            return false;
        }

        $user = $this->user();

        if (! $user->isAdmin()) {
            if ($user->branch_id === null) {
                return false;
            }
            if ($this->requestedBranchId !== null && $this->requestedBranchId !== (int) $user->branch_id) {
                return false;
            }
        }

        return true;
    }

    /**
     * Pin the vacancy to a branch: admins choose it, branch users to their own.
     */
    protected function prepareForValidation(): void
    {
        $user = $this->user();
        $this->requestedBranchId = $this->filled('branch_id') ? (int) $this->input('branch_id') : null;

        $branchId = $user->isAdmin()
            ? $this->integer('branch_id')
            : (int) ($user->branch_id ?? 0);

        // A vacancy is for at least one person; default to 1 when unspecified.
        $this->merge([
            'branch_id' => $branchId,
            'openings' => $this->filled('openings') ? $this->integer('openings') : 1,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $branchId = (int) $this->input('branch_id');

        return [
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')],
            'department_id' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')->where('branch_id', $branchId)->whereNull('deleted_at'),
            ],
            'position' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'openings' => ['required', 'integer', 'min:1', 'max:10000'],
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
            'schedule_other' => ['nullable', 'string', 'max:255'],
            'work_format' => ['nullable', Rule::enum(WorkFormat::class)],
            'salary' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'probation' => ['nullable', Rule::enum(Probation::class)],
            'probation_other' => ['nullable', 'string', 'max:255'],
            'opening_reason' => ['nullable', Rule::enum(OpeningReason::class)],
            'priority' => ['nullable', Rule::enum(VacancyPriority::class)],
            'opened_at' => ['nullable', 'date'],
            'deadline' => ['nullable', 'date'],
        ];
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
