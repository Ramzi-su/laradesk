<?php

namespace App\Http\Requests;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListTicketsRequest extends FormRequest
{
    /**
     * Visibility is enforced by Ticket::forUser(), not here.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(TicketStatus::class)],
            'priority' => ['nullable', Rule::enum(TicketPriority::class)],
            'category' => ['nullable', 'integer'],
            'agent' => ['nullable', 'integer'],
        ];
    }

    /**
     * The validated filters, typed for Ticket::filter().
     *
     * @return array{search: ?string, status: ?string, priority: ?string, category: ?int, agent: ?int}
     */
    public function filters(): array
    {
        return [
            'search' => $this->validated('search'),
            'status' => $this->validated('status'),
            'priority' => $this->validated('priority'),
            'category' => $this->filled('category') ? $this->integer('category') : null,
            'agent' => $this->filled('agent') ? $this->integer('agent') : null,
        ];
    }
}
