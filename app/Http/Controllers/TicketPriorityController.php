<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateTicketPriorityRequest;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;

class TicketPriorityController extends Controller
{
    public function __invoke(UpdateTicketPriorityRequest $request, Ticket $ticket): RedirectResponse
    {
        $ticket->update($request->validated());

        return back()->with('success', __('Priority changed to ":priority".', ['priority' => $ticket->priority->label()]));
    }
}
