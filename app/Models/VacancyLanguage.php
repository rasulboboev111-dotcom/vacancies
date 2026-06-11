<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VacancyLanguage extends Model
{
    protected $fillable = ['vacancy_id', 'name'];

    protected $casts = ['vacancy_id' => 'integer'];

    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class);
    }
}
