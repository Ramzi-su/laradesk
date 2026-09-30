<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('assign', $this->route('ticket'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // An agent can only assign the ticket to themselves.
        if ($this->user()->isAgent()) {
            return [
                'agent_id' => ['required', 'integer', Rule::in([$this->user()->id])],
            ];
        }

        // Admins can assign any agent, or unassign with an empty value.
        return [
            'agent_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('role', UserRole::Agent->value),
            ],
        ];
    }
}
