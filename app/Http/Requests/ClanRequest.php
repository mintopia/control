<?php

namespace App\Http\Requests;

use App\Models\Clan;
use Illuminate\Foundation\Http\FormRequest;
use function App\makePermalink;

class ClanRequest extends FormRequest
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
        return [
            'name' => [
                'required',
                'string',
                'min:3',
                'max:100',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $permalink = makePermalink($value);
                    if ($permalink === '') {
                        $fail('The clan name is not valid');
                        return;
                    }
                    $clan = Clan::whereCode($permalink)->first();
                    if ($clan && (!$this->clan || $clan->id !== $this->clan->id)) {
                        $fail('That clan name is not available');
                    }
                },
            ],
        ];
    }
}
