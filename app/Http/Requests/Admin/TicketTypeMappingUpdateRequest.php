<?php

namespace App\Http\Requests\Admin;

use App\Models\TicketProvider;
use Illuminate\Foundation\Http\FormRequest;

class TicketTypeMappingUpdateRequest extends FormRequest
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
            'external_id' => [
                'required',
                function (string $attribute, mixed $value, \Closure $fail) {
                    [$provider_id, $external_id] = explode(':', $value);
                    if (!$provider_id || !$external_id) {
                        $fail('Invalid ticket type specified');
                        return;
                    }
                    $provider = TicketProvider::whereId($provider_id)->first();
                    if (!$provider) {
                        $fail('Ticket Provider does not exist');
                        return;
                    }
                    $available = $this->event->getAvailableTicketMappings($this->mapping ?? null);
                    foreach ($available as $mapping) {
                        if ($mapping->provider->id !== $provider->id) {
                            continue;
                        }
                        foreach ($mapping->types as $type) {
                            if ($type->id === $external_id) {
                                return;
                            }
                        }
                    }
                    $fail('That provider ticket type is already mapped');
                },
            ]
        ];
    }
}
