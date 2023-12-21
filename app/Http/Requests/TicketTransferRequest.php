<?php

namespace App\Http\Requests;

use App\Models\Ticket;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                function (string $attribute, mixed $value, Closure $fail) {
                    $query = Ticket::whereTransferCode(strtoupper($value));
                    if (!$this->user()->hasRole('admin')) {
                        $query = $query->whereHas('event', function($query) {
                            $query->whereDraft(false);
                        });
                    }
                    $ticket = $query->first();
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
