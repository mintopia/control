<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TicketProviderUpdateRequest extends FormRequest
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
        $rules = [
            'enabled' => 'sometimes|nullable|boolean',
        ];
        foreach ($this->provider->settings as $setting) {
            if ($setting->validation) {
                $rules[$setting->code] = $setting->validation;
            }
        }
        return $rules;
    }
}
