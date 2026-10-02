<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignTicketRequest;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;

class TicketAssignmentController extends Controller
{
    public function __invoke(AssignTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        // An agent takes an unassigned ticket: a colleague may have clicked at the same time.
        if ($request->user()->isAgent()) {
            return $ticket->claimFor($request->user())
                ? back()->with('success', __('Ticket assigned.'))
                : back()->with('error', __('Another agent has just taken this ticket.'));
        }

        // An admin (re)assigns deliberately: their decision overrides the current agent.
        $agentId = $request->validated('agent_id');

        $agentId === null ? $ticket->agent()->dissociate() : $ticket->agent()->associate($agentId);
        $ticket->save();

        return back()->with('success', $agentId === null ? __('Ticket unassigned.') : __('Ticket assigned.'));
    }
}
