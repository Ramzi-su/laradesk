<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateTicketStatusRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;

class TicketStatusController extends Controller
{
    public function __invoke(UpdateTicketStatusRequest $request, Ticket $ticket): TicketResource
    {
        $ticket->status = $request->enum('status', TicketStatus::class);
        $ticket->save();

        return TicketResource::make($ticket->load(['category', 'client', 'agent']));
    }
}
