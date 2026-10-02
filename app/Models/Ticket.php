<?php

namespace App\Models;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Observers\TicketObserver;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

// status, agent_id and client_id are not fillable: they change through dedicated,
// authorized actions, never through a generic form payload.
#[Fillable(['title', 'description', 'priority', 'category_id'])]
#[ObservedBy(TicketObserver::class)]
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Mirrors the database defaults so a new ticket has them in memory too.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'open',
        'priority' => 'medium',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'priority' => TicketPriority::class,
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * @return HasMany<Attachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /**
     * Assigns the ticket to the agent only if nobody took it in the meantime.
     *
     * The authorization check ("still unassigned?") and the write happen at different
     * moments, so two agents could both pass it. Locking the row (SELECT ... FOR UPDATE)
     * serializes concurrent claims: the second one waits, then sees the first agent and
     * gives up. The first claim wins instead of the last one.
     */
    public function claimFor(User $agent): bool
    {
        $claimed = DB::transaction(function () use ($agent): bool {
            $locked = static::query()->lockForUpdate()->findOrFail($this->getKey());

            if ($locked->agent_id !== null) {
                return false;
            }

            // Saved through Eloquent (not a raw UPDATE) so TicketObserver still runs.
            $locked->agent()->associate($agent);
            $locked->save();

            return true;
        });

        $this->refresh();

        return $claimed;
    }

    /**
     * Stores an uploaded file on the private "local" disk and records it.
     */
    public function addAttachment(UploadedFile $file, User $uploader): Attachment
    {
        $attachment = $this->attachments()->make([
            'original_name' => $file->getClientOriginalName(),
            // Random file name: the client-provided name is never used on disk.
            'path' => $file->store("tickets/{$this->getKey()}", 'local'),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);
        $attachment->user()->associate($uploader);
        $attachment->save();

        return $attachment;
    }

    /**
     * Tickets that still need work (open or in progress).
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function open(Builder $query): void
    {
        $query->whereIn('status', TicketStatus::active());
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function status(Builder $query, TicketStatus|string $status): void
    {
        $query->where('status', $status);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function priority(Builder $query, TicketPriority|string $priority): void
    {
        $query->where('priority', $priority);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function assignedTo(Builder $query, User|int $agent): void
    {
        $query->where('agent_id', $agent instanceof User ? $agent->getKey() : $agent);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function inCategory(Builder $query, Category|int $category): void
    {
        $query->where('category_id', $category instanceof Category ? $category->getKey() : $category);
    }

    /**
     * Applies the list filters shared by the web and the API.
     * Expects validated input (see ListTicketsRequest).
     *
     * @param  Builder<self>  $query
     * @param  array{search?: ?string, status?: ?string, priority?: ?string, category?: ?int, agent?: ?int}  $filters
     */
    #[Scope]
    protected function filter(Builder $query, array $filters): void
    {
        $query
            ->search($filters['search'] ?? null)
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->status($status))
            ->when($filters['priority'] ?? null, fn (Builder $query, string $priority) => $query->priority($priority))
            ->when($filters['category'] ?? null, fn (Builder $query, int $category) => $query->inCategory($category))
            ->when($filters['agent'] ?? null, fn (Builder $query, int $agent) => $query->assignedTo($agent));
    }

    /**
     * Restricts the query to the tickets the given user is allowed to see.
     * Every listing (web and API) must go through this scope.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function forUser(Builder $query, User $user): void
    {
        match ($user->role) {
            UserRole::Admin => null,
            UserRole::Agent => $query->where(fn (Builder $query) => $query
                ->where('agent_id', $user->getKey())
                ->orWhereNull('agent_id')),
            UserRole::Client => $query->where('client_id', $user->getKey()),
        };
    }

    /**
     * Searches the reference, title and description.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        // Escape LIKE wildcards so "%" or "_" typed by a user are matched literally.
        $escaped = addcslashes($term, '\\%_');

        // whereAny() wraps the ORs in parentheses, so they cannot bypass forUser().
        $query->whereAny(['reference', 'title', 'description'], 'like', "%{$escaped}%");
    }
}
