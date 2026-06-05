<?php

namespace App\Http\Requests\Position;

use App\Models\Position;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdatePositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $position = Position::find($this->route('id'));

        return $position !== null && Gate::allows('update', $position);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['name' => trim((string) $this->input('name'))]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $ignoreId = $this->route('id');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($ignoreId) {
                    $exists = Position::whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($value))])
                        ->where('id', '!=', $ignoreId)
                        ->exists();
                    if ($exists) {
                        $fail('Чунин вазифа аллакай дар пойгоҳи додаҳо мавҷуд аст.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Номи вазифа ҳатмист.',
            'name.max' => 'Номи вазифа хеле дароз аст.',
        ];
    }
}
