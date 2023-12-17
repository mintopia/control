<?php

namespace App\Http\Requests\Admin;

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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $config = $this->provider->configMapping();
        $rules = [
            'enabled' => 'sometimes|nullable|boolean',
        ];
        foreach ($config as $field => $data) {
            $rules[$field] = $data->validation;
        }
        return $rules;
    }
}
