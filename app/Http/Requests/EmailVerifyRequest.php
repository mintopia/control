<?php

namespace App\Http\Requests;

use App\Exceptions\EmailVerificationException;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class EmailVerifyRequest extends FormRequest
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
                'alpha_num:ascii',
                function (string $attribute, mixed $value, Closure $fail) {
                    try {
                        $this->emailaddress->checkCode($value);
                    } catch (EmailVerificationException $ex) {
                        $fail($ex->getMessage());
                    }
                },
            ],
        ];
    }
}
