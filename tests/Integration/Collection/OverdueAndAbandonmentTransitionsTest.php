<?php

declare(strict_types=1);

namespace Tests\Integration\Collection;

use Domain\Collection\Domain\Repositories\CollectionCaseRepository;
use Domain\Collection\Infrastructure\Persistence\Eloquent\CollectionCaseRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OverdueAndAbandonmentTransitionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_pending_case_past_its_deadline_is_found_as_due_for_overdue(): void
    {
        $overdue = CollectionCaseRecord::factory()->create(['status' => 'pending', 'collection_deadline_at' => now()->subDay()]);
        $notYet = CollectionCaseRecord::factory()->create(['status' => 'pending', 'collection_deadline_at' => now()->addDay()]);

        $ids = app(CollectionCaseRepository::class)->findDueForOverdue(100);

        $this->assertContains($overdue->id, $ids);
        $this->assertNotContains($notYet->id, $ids);
    }

    public function test_an_overdue_case_past_its_abandonment_threshold_is_found_as_due_for_abandonment(): void
    {
        $abandonable = CollectionCaseRecord::factory()->create([
            'status' => 'overdue',
            'abandonment_threshold_at' => now()->subDay(),
        ]);
        $notYet = CollectionCaseRecord::factory()->create([
            'status' => 'overdue',
            'abandonment_threshold_at' => now()->addDay(),
        ]);
        $pendingNotOverdue = CollectionCaseRecord::factory()->create([
            'status' => 'pending',
            'abandonment_threshold_at' => now()->subDay(),
        ]);

        $ids = app(CollectionCaseRepository::class)->findDueForAbandonment(100);

        $this->assertContains($abandonable->id, $ids);
        $this->assertNotContains($notYet->id, $ids);
        $this->assertNotContains($pendingNotOverdue->id, $ids);
    }

    public function test_console_command_flips_pending_to_overdue_to_abandoned_in_one_pass(): void
    {
        $case = CollectionCaseRecord::factory()->create([
            'status' => 'pending',
            'collection_deadline_at' => now()->subDay(),
            'abandonment_threshold_at' => now()->subHour(),
        ]);

        $this->artisan('afprospos:collection:process-deadlines')->assertSuccessful();

        $this->assertDatabaseHas('collection_cases', ['id' => $case->id, 'status' => 'abandoned']);
        $this->assertDatabaseHas('collection_case_events', ['collection_case_id' => $case->id, 'event_type' => 'marked_overdue']);
    }
}
