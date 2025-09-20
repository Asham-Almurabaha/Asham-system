<?php

namespace Modules\Contracts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContractNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('note')) {
            $this->merge([
                'note' => trim((string) $this->input('note')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'note_date' => ['required', 'date'],
            'note' => ['required', 'string', 'max:2000'],
        ];
    }
}
