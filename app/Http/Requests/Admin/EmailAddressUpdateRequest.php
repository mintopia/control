<?php

namespace App\Http\Requests\Admin;

use Illuminate\Database\Query\Builder;
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $uniqueRule = 'unique:email_addresses,email';
        if ($this->emailaddress) {
            $uniqueRule = Rule::unique('email_addresses')->ignore($this->emailaddress->id);
        }
        return [
            'email' => [
                'required',
                'string',
                'email',
                $uniqueRule,
            ],
        ];
    }
}
