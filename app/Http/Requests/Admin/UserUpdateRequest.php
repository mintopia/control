<?php

namespace App\Http\Requests\Admin;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'primary_email_id.exists' => 'The email address is not valid',
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nickname' => [
                'required',
                'string',
                'max:255',
                'min:2',
                Rule::unique('users', 'nickname')->ignore($this->user->id),
            ],
            'name' => 'required|string|max:255|min:4',
            'primary_email_id' => [
                'required',
                'integer',
                Rule::exists('email_addresses', 'id')->where(function(Builder $query) {
                    return $query->where('user_id', $this->user->id);
                }),
            ],
            'roles' => 'sometimes|array',
            'roles.*' => 'string|exists:roles,code',
            'terms' => 'sometimes|boolean',
            'first_login' => 'sometimes|boolean',
            'suspended' => 'sometimes|boolean',
        ];
    }
}
