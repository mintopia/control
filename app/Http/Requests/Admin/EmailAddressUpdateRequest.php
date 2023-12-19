<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmailAddressUpdateRequest extends FormRequest
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
        $uniqueRule = 'unique:email_addresses,email';
        if ($this->email) {
            $uniqueRule = Rule::unique('email_addresses', 'email')->ignore($this->email->id);
        }
        return [
            'address' => [
                'required',
                'string',
                'email',
                $uniqueRule,
            ],
        ];
    }
}
