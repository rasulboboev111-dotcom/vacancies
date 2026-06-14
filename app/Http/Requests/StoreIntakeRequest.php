<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIntakeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // auth handled by the api.key middleware
    }

    protected function prepareForValidation(): void
    {
        $decoded = json_decode((string) $this->input('payload'), true);
        if (is_array($decoded)) {
            $this->merge([
                'external_id' => $decoded['external_id'] ?? null,
                'name' => $decoded['name'] ?? null,
                'email' => $decoded['email'] ?? null,
                'phone' => $decoded['phone'] ?? null,
                'vacancy' => $decoded['vacancy'] ?? null,
                'source' => $decoded['source'] ?? null,
                'summary' => $decoded['summary'] ?? null,
                'survey' => $decoded['survey'] ?? null,
                'created_at' => $decoded['created_at'] ?? null,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'payload' => ['required', 'string'],
            'external_id' => ['required', 'integer'],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'vacancy' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'max:32'],
            'summary' => ['nullable', 'string'],
            'survey' => ['nullable', 'array'],
            'created_at' => ['nullable', 'date'],
            'resume' => ['nullable', 'file', 'mimes:'.implode(',', config('intake.resume_mimes')), 'max:'.config('intake.resume_max_kb')],
        ];
    }
}
