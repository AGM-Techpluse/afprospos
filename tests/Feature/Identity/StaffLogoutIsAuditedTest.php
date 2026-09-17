<?php

declare(strict_types=1);

namespace Tests\Feature\Identity;

use Domain\Audit\Infrastructure\Persistence\Eloquent\AuditLogRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffLogoutIsAuditedTest extends TestCase
{
    use RefreshDatabase;

    public function test_logging_out_records_a_staff_logged_out_audit_event_with_the_actor_and_ip(): void
    {
        $staff = StaffRecord::factory()->create();

        $this->actingAs($staff, 'staff')->post('/staff/logout')->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'Identity',
            'event_type' => 'StaffLoggedOut',
            'subject_type' => 'staff',
            'subject_id' => $staff->id,
            'actor_staff_id' => $staff->id,
        ]);

        $event = AuditLogRecord::query()
            ->where('event_type', 'StaffLoggedOut')
            ->where('subject_id', $staff->id)
            ->firstOrFail();

        $this->assertNotNull($event->context['ip_address'] ?? null);
    }
}
