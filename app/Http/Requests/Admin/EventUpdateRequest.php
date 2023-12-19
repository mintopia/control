<?php

namespace App\Http\Requests\Admin;

use App\Models\Event;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use function App\makePermalink;

class EventUpdateRequest extends FormRequest
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
                    $event = Event::whereCode($permalink)->first();
                    if ($event && (!$this->event || $event->id !== $this->event->id)) {
                        $fail('The event name is already in use');
                    }
                },
            ],
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'boxoffice_url' => 'sometimes|url:http,https|nullable',
            'seating_locked' => 'sometimes|boolean|nullable',
        ];
    }
}
