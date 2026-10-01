<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateTicket;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListTicketsRequest;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class TicketController extends Controller
{
    /**
     * Same visibility scope and filters as the web list.
     */
    public function index(ListTicketsRequest $request): AnonymousResourceCollection
    {
        $tickets = Ticket::forUser($request->user())
            ->filter($request->filters())
            ->with(['category', 'client', 'agent'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return TicketResource::collection($tickets);
    }

    public function store(StoreTicketRequest $request, CreateTicket $createTicket): TicketResource
    {
        $ticket = $createTicket->handle(
            $request->user(),
            $request->safe()->except('attachments'),
            $request->file('attachments', []),
        );

        // A freshly created model makes the resource answer "201 Created".
        return TicketResource::make($ticket->load(['category', 'client', 'agent', 'attachments']));
    }

    public function show(Request $request, Ticket $ticket): TicketResource
    {
        Gate::authorize('view', $ticket);

        return TicketResource::make($ticket->load([
            'category',
            'client',
            'agent',
            'attachments',
            'comments' => fn ($query) => $query->visibleTo($request->user())->with('user')->oldest(),
        ]));
    }
}
