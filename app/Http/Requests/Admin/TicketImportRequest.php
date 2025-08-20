<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TicketImportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Accept common CSV mime types alongside the file requirement (see testCsvRuleContainsFileAndMimetypes)
            'csv' => 'required|file|mimetypes:text/csv,text/plain,application/vnd.ms-excel',
        ];
    }
}
