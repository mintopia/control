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
            'order' => ['required', Rule::in(self::SORTABLE)],
            'order_direction' => ['required', Rule::in(['asc', 'desc'])],
        ];
    }

    /**
     * Coerce missing or unrecognised sort parameters to sensible defaults, so a
     * bad query string still renders the page rather than failing validation.
     */
    protected function prepareForValidation(): void
    {
        $order = $this->input('order');
        $order = in_array($order, self::SORTABLE, true) ? $order : 'event';

        $direction = strtolower((string) $this->input('order_direction'));
        if (!in_array($direction, ['asc', 'desc'], true)) {
            $direction = $order === 'event' ? 'desc' : 'asc';
        }

        $this->merge([
            'order' => $order,
            'order_direction' => $direction,
        ]);
    }
}
