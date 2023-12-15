<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SeatPickRequest extends FormRequest
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
            'swap' => 'sometimes|boolean',
            'ticket_id' => [
                'required',
                'integer',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $ticketIds = $this->user()->getPickableTickets($this->seat->plan->event)->pluck('id');
                    if (!$ticketIds->contains($value)) {
                        $fail('You are not able to pick a seat for that ticket');
                    }
                },
            ]
        ];
    }
}
