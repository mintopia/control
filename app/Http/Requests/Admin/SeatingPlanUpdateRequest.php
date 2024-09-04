<?php

namespace App\Http\Requests\Admin;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use function App\makePermalink;

class SeatingPlanUpdateRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:100',
                function (string $attribute, mixed $value, Closure $fail) {
                    $permalink = makePermalink($value);
                    $plan = $this->event->seatingPlans()->whereCode($permalink)->first();
                    if ($plan && (!$this->seatingplan || $plan->id !== $this->seatingplan->id)) {
                        $fail('The seating plan name is already in use');
                    }
                },
            ],
            'image_url' => 'sometimes|url:http,https|nullable',
            'scale' => 'sometimes|integer|numeric|min:25|max:200|nullable',
        ];
    }
}
