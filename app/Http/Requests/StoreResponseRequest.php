<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:20480', 'mimes:pdf,doc,docx,jpg,jpeg,png'],
        ];
    }
}
