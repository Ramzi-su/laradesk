<?php

namespace Tests\Feature;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use Tests\TestCase;

/**
 * Guards against adding an enum case without its labels: the UI would
 * otherwise display a raw key such as "enums.ticket_status.on_hold".
 */
class EnumTranslationsTest extends TestCase
{
    public function test_every_enum_case_has_a_french_and_an_english_label(): void
    {
        foreach (['fr', 'en'] as $locale) {
            app()->setLocale($locale);

            foreach ([...UserRole::cases(), ...TicketStatus::cases(), ...TicketPriority::cases()] as $case) {
                $this->assertStringStartsNotWith('enums.', $case->label(), "Missing {$locale} label for ".$case::class."::{$case->name}");
            }
        }
    }

    public function test_labels_are_in_french_by_default(): void
    {
        $this->assertSame('En cours', TicketStatus::InProgress->label());
        $this->assertSame('Urgente', TicketPriority::Urgent->label());
        $this->assertSame('Administrateur', UserRole::Admin->label());
    }
}
