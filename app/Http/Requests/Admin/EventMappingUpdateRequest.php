<?php

namespace App\Http\Requests\Admin;

use App\Models\TicketProvider;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class EventMappingUpdateRequest extends FormRequest
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
            'external_id' => [
                'required',
                function (string $attribute, mixed $value, Closure $fail) {
                    [$provider_id, $external_id] = explode(':', $value);
                    if (!$provider_id || !$external_id) {
                        $fail('Invalid event specified');
                        return;
                    }
                    $provider = TicketProvider::whereId($provider_id)->first();
                    if (!$provider) {
                        $fail('Ticket Provider does not exist');
                        return;
                    }
                    $available = $this->event->getAvailableEventMappings($this->mapping ?? null);
                    foreach ($available as $mapping) {
                        if ($mapping->provider->id != $provider->id) {
                            continue;
                        }
                        foreach ($mapping->events as $event) {
                            if ($event->id == $external_id) {
                                return;
                            }
                        }
                    }
                    $fail('That provider event is already mapped');
                },
            ]
        ];
    }
}
