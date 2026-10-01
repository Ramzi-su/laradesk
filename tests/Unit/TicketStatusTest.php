<?php

namespace Tests\Unit;

use App\Enums\TicketStatus;
use PHPUnit\Framework\TestCase;

class TicketStatusTest extends TestCase
{
    public function test_active_statuses_are_the_ones_still_needing_work(): void
    {
        $this->assertSame([TicketStatus::Open, TicketStatus::InProgress], TicketStatus::active());
    }

    public function test_values_match_the_database_strings(): void
    {
        $this->assertSame(
            ['open', 'in_progress', 'resolved', 'closed'],
            array_column(TicketStatus::cases(), 'value'),
        );
    }
}
