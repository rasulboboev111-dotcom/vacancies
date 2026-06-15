<?php

namespace App\Http\Requests\Position;

use App\Models\Position;
use App\Rules\UniqueCaseInsensitive;
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
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                new UniqueCaseInsensitive(Position::class, 'name', 'Чунин вазифа аллакай дар пойгоҳи додаҳо мавҷуд аст.', $this->route('id')),
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
