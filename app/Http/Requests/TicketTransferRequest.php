<?php

namespace App\Http\Requests;

use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;

class TicketTransferRequest extends FormRequest
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
            'code' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $ticket = Ticket::whereTransferCode(strtoupper($value))->first();
                    if ($ticket === null) {
                        $fail('The transfer code is invalid');
                    } elseif (!$ticket->canTransfer()) {
                        $fail('It is not possible to transfer this ticket');
                    } elseif ($ticket->user_id === $this->user()->id) {
                        $fail('You already have the ticket in your account');
                    }
                },
            ]
        ];
    }
}