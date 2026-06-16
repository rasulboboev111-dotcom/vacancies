<?php

namespace App\Http\Requests\Concerns;

/**
 * Общие правила валидации файла резюме. Тип/размер берутся из config/intake,
 * чтобы лимиты жили в одном месте и не расходились между Store/Update/Intake.
 */
trait ResumeRules
{
    /**
     * @return list<string>
     */
    protected function resumeRules(): array
    {
        return [
            'nullable',
            'file',
            'mimes:'.implode(',', (array) config('intake.resume_mimes')),
            'max:'.config('intake.resume_max_kb'),
        ];
    }
}
