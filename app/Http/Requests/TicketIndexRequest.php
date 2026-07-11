<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TicketIndexRequest extends FormRequest
{
    /**
     * Columns the tickets index may be sorted by.
     *
     * @var array<int, string>
     */
    public const SORTABLE = ['reference', 'type', 'seat', 'event'];

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
            'order' => ['nullable', Rule::in(self::SORTABLE)],
            'order_direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ];
    }
}
