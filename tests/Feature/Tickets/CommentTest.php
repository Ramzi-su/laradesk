<?php

namespace Tests\Feature\Tickets;

use App\Models\Comment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_comment_their_ticket(): void
    {
        $ticket = Ticket::factory()->create();

        $this->actingAs($ticket->client)
            ->post(route('tickets.comments.store', $ticket), ['body' => 'Any news?'])
            ->assertRedirect(route('tickets.show', $ticket).'#comments');

        $comment = Comment::sole();
        $this->assertTrue($comment->user->is($ticket->client));
        $this->assertFalse($comment->is_internal);
    }

    public function test_client_cannot_post_an_internal_comment(): void
    {
        $ticket = Ticket::factory()->create();

        $this->actingAs($ticket->client)
            ->post(route('tickets.comments.store', $ticket), ['body' => 'Sneaky', 'is_internal' => '1'])
            ->assertForbidden();

        $this->assertSame(0, Comment::count());
    }

    public function test_client_cannot_comment_another_client_ticket(): void
    {
        $ticket = Ticket::factory()->create();

        $this->actingAs(User::factory()->client()->create())
            ->post(route('tickets.comments.store', $ticket), ['body' => 'Hello'])
            ->assertForbidden();
    }

    public function test_assigned_agent_can_post_an_internal_comment(): void
    {
        $agent = User::factory()->agent()->create();
        $ticket = Ticket::factory()->assignedTo($agent)->create();

        $this->actingAs($agent)
            ->post(route('tickets.comments.store', $ticket), ['body' => 'Check the logs', 'is_internal' => '1'])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Comment::sole()->is_internal);
    }

    public function test_agent_cannot_comment_a_ticket_not_assigned_to_them(): void
    {
        $ticket = Ticket::factory()->create();

        $this->actingAs(User::factory()->agent()->create())
            ->post(route('tickets.comments.store', $ticket), ['body' => 'Hello'])
            ->assertForbidden();
    }

    public function test_body_is_required(): void
    {
        $ticket = Ticket::factory()->create();

        $this->actingAs($ticket->client)
            ->post(route('tickets.comments.store', $ticket), ['body' => ''])
            ->assertSessionHasErrors('body');
    }
}
