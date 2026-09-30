<?php

namespace App\Http\Requests;

use App\Enums\TicketPriority;
use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
{
    public const ALLOWED_EXTENSIONS = ['pdf', 'png', 'jpg', 'jpeg', 'txt', 'docx'];

    public const MAX_FILE_KILOBYTES = 5 * 1024;

    public const MAX_FILES = 5;

    public function authorize(): bool
    {
        return $this->user()->can('create', Ticket::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'priority' => ['required', Rule::enum(TicketPriority::class)],
            'attachments' => ['nullable', 'array', 'max:'.self::MAX_FILES],
            // "mimes" inspects the file content, not only the extension the client sent.
            'attachments.*' => ['file', 'mimes:'.implode(',', self::ALLOWED_EXTENSIONS), 'max:'.self::MAX_FILE_KILOBYTES],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'category_id' => __('category'),
            'attachments.*' => __('attachment'),
        ];
    }
}
