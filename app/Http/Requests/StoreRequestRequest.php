<?php

namespace App\Http\Requests;

use App\Enums\LegalProcess;
use App\Enums\NatureOfCase;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequestRequest extends FormRequest
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
            'request_type' => ['required', Rule::enum(LegalProcess::class)],
            'nature_of_case' => ['nullable', Rule::enum(NatureOfCase::class)],
            'additional_context' => ['nullable', 'string', 'max:10000'],
            'target_institution_id' => ['required', 'integer', 'exists:institutions,id'],
            'reference_number' => ['required', 'string', 'max:100', 'unique:requests,reference_number'],
            'legal_process_signed_at' => ['nullable', 'date'],
            'warrant_expires_at' => ['nullable', 'date', 'after_or_equal:legal_process_signed_at'],
            'records_from' => ['nullable', 'date'],
            'records_to' => ['nullable', 'date', 'after_or_equal:records_from'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:20480', 'mimes:pdf,doc,docx,jpg,jpeg,png'],
        ];
    }
}
