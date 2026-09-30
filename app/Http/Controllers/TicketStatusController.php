<?php

namespace App\Http\Controllers;

use App\Enums\TicketStatus;
use App\Http\Requests\UpdateTicketStatusRequest;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;

class TicketStatusController extends Controller
{
    public function __invoke(UpdateTicketStatusRequest $request, Ticket $ticket): RedirectResponse
    {
        // status is not fillable: it is assigned explicitly, after authorization.
        $ticket->status = $request->enum('status', TicketStatus::class);
        $ticket->save();

        return back()->with('success', __('Status changed to ":status".', ['status' => $ticket->status->label()]));
    }
}
