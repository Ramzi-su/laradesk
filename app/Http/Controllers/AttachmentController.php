<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    /**
     * Files live on the private disk: this authorized route is the only way to get them.
     * They are always sent as downloads (Content-Disposition: attachment), never rendered inline.
     */
    public function __invoke(Attachment $attachment): StreamedResponse
    {
        Gate::authorize('view', $attachment);

        return Storage::disk('local')->download($attachment->path, $attachment->original_name);
    }
}
