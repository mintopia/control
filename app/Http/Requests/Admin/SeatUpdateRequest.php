<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SeatUpdateRequest extends FormRequest
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
            'x' => 'required|integer|min:0',
            'y' => 'required|integer|min:0',
            'row' => 'required|string|max:8',
            'number' => 'required|integer|min:0',
            'label' => 'required|string|max:100',
            'description' => 'sometimes|string|nullable|max:255',
            'class' => 'sometimes|string|nullable|max:255',
            'disabled' => 'sometimes|boolean|nullable',
        ];
    }
}
