<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequestAttachmentRequest extends FormRequest
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
            'attachments' => ['required', 'array', 'min:1'],
            'attachments.*' => [
                'required',
                'file',
                'max:20480',
                'mimes:pdf,doc,docx,jpg,jpeg,png',
            ],
        ];
    }
}
