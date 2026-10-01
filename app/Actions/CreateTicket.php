<?php

namespace App\Actions;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Opens a ticket on behalf of a user, with its attachments.
 * Shared by the web and API controllers; input must already be validated.
 */
class CreateTicket
{
    /**
     * @param  array{title: string, description: string, category_id: int|string, priority: string}  $data
     * @param  array<int, UploadedFile>  $attachments
     */
    public function handle(User $author, array $data, array $attachments = []): Ticket
    {
        return DB::transaction(function () use ($author, $data, $attachments) {
            $ticket = new Ticket($data);
            $ticket->client()->associate($author);
            $ticket->save();

            foreach ($attachments as $file) {
                $ticket->addAttachment($file, $author);
            }

            return $ticket;
        });
    }
}
