<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
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
        // Safely obtain the current user id (can be null when running unit tests)
        $ignoreId = optional($this->user())->id;

        // Build the unique rule and only apply ignore when we have an id to ignore.
        $uniqueNickname = Rule::unique('users', 'nickname');
        if ($ignoreId) {
            $uniqueNickname = $uniqueNickname->ignore($ignoreId);
        }

        return [
            'nickname' => [
                'required',
                'string',
                'max:255',
                'min:2',
                $uniqueNickname,
            ],
            'name' => 'required|string|max:255|min:4',
        ];
    }
}
