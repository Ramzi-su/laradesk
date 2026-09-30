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
