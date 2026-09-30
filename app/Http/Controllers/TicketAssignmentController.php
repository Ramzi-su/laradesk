<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignTicketRequest;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;

class TicketAssignmentController extends Controller
{
    public function __invoke(AssignTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        $agentId = $request->validated('agent_id');

        $agentId === null ? $ticket->agent()->dissociate() : $ticket->agent()->associate($agentId);
        $ticket->save();

        return back()->with('success', $agentId === null ? __('Ticket unassigned.') : __('Ticket assigned.'));
    }
}
