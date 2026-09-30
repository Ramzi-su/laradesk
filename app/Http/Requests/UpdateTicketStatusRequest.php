<?php

namespace App\Http\Requests;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('changeStatus', $this->route('ticket'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Ticket $ticket */
        $ticket = $this->route('ticket');

        $status = Rule::enum(TicketStatus::class)->except([$ticket->status]);

        // The policy lets a client act on their resolved ticket; closing it is the only move.
        // (only() takes precedence over except(), which is fine: closed !== resolved.)
        if ($this->user()->isClient()) {
            $status->only([TicketStatus::Closed]);
        }

        return [
            'status' => ['required', $status],
        ];
    }
}
