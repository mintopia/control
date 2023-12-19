<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SocialProviderUpdateRequest extends FormRequest
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
        $config = $this->provider->configMapping();
        $rules = [
            'enabled' => 'sometimes|nullable|boolean',
            'auth_enabled' => 'sometimes|nullable|boolean',
        ];
        foreach ($config as $field => $data) {
            $rules[$field] = $data->validation;
        }
        return $rules;
    }
}
