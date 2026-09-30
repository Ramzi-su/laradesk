<?php

namespace App\Http\Controllers;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Http\Requests\ListTicketsRequest;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function index(ListTicketsRequest $request): View
    {
        $user = $request->user();

        $tickets = Ticket::forUser($user)
            ->filter($request->filters())
            ->with(['category', 'client', 'agent'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('tickets.index', [
            'tickets' => $tickets,
            'categories' => Category::orderBy('name')->get(),
            'agents' => $user->isStaff() ? User::role(UserRole::Agent)->orderBy('name')->get() : collect(),
            'statuses' => TicketStatus::cases(),
            'priorities' => TicketPriority::cases(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Ticket::class);

        return view('tickets.create', [
            'categories' => Category::orderBy('name')->get(),
            'priorities' => TicketPriority::cases(),
        ]);
    }

    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $user = $request->user();

        $ticket = DB::transaction(function () use ($request, $user) {
            $ticket = new Ticket($request->safe()->except('attachments'));
            $ticket->client()->associate($user);
            $ticket->save();

            foreach ($request->file('attachments', []) as $file) {
                $ticket->addAttachment($file, $user);
            }

            return $ticket;
        });

        return to_route('tickets.show', $ticket)
            ->with('success', __('Ticket :reference created.', ['reference' => $ticket->reference]));
    }

    public function show(Ticket $ticket): View
    {
        Gate::authorize('view', $ticket);

        $user = request()->user();

        $ticket->load([
            'category',
            'client',
            'agent',
            'attachments.user',
            'comments' => fn ($query) => $query->visibleTo($user)->with('user')->oldest(),
        ]);

        return view('tickets.show', [
            'ticket' => $ticket,
            'statuses' => TicketStatus::cases(),
            'priorities' => TicketPriority::cases(),
            'agents' => $user->isAdmin() ? User::role(UserRole::Agent)->orderBy('name')->get() : collect(),
        ]);
    }

    public function edit(Ticket $ticket): View
    {
        Gate::authorize('update', $ticket);

        return view('tickets.edit', ['ticket' => $ticket]);
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        $ticket->update($request->validated());

        return to_route('tickets.show', $ticket)->with('success', __('Ticket updated.'));
    }

    public function destroy(Ticket $ticket): RedirectResponse
    {
        Gate::authorize('delete', $ticket);

        $ticket->delete();

        return to_route('tickets.index')
            ->with('success', __('Ticket :reference deleted.', ['reference' => $ticket->reference]));
    }
}
