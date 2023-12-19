<?php

namespace App\Http\Requests;

use App\Models\Clan;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ClanMembershipRequest extends FormRequest
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
            'code' => [
                'required',
                'string',
                function (string $attribute, mixed $value, Closure $fail) {
                    if (Clan::whereInviteCode(strtoupper($value))->count() === 0) {
                        $fail('The invite code is invalid');
                    }
                },
            ]
        ];
    }
}
