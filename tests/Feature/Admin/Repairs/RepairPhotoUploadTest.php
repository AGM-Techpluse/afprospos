<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Repairs;

use Database\Seeders\RolePermissionSeeder;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobPhotoRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RepairPhotoUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_technician_can_upload_and_delete_a_repair_photo(): void
    {
        Storage::fake('public');
        $this->seed(RolePermissionSeeder::class);

        $technician = StaffRecord::factory()->create();
        $technician->assignRole('Technician');
        $job = RepairJobRecord::factory()->create();

        $response = $this->actingAs($technician, 'staff')->post("/admin/repairs/{$job->id}/photos", [
            'photo' => UploadedFile::fake()->create('device.jpg', 100, 'image/jpeg'),
            'caption' => 'Cracked screen on arrival',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('repair_job_photos', [
            'repair_job_id' => $job->id,
            'caption' => 'Cracked screen on arrival',
            'uploaded_by_staff_id' => $technician->id,
        ]);

        $photo = RepairJobPhotoRecord::query()->where('repair_job_id', $job->id)->firstOrFail();
        Storage::disk('public')->assertExists($photo->path);

        $showResponse = $this->actingAs($technician, 'staff')->get("/admin/repairs/{$job->id}");
        $showResponse->assertInertia(fn ($page) => $page
            ->where('repair.photos.0.caption', 'Cracked screen on arrival'));

        $deleteResponse = $this->actingAs($technician, 'staff')->delete("/admin/repairs/{$job->id}/photos/{$photo->id}");

        $deleteResponse->assertRedirect();
        $this->assertDatabaseMissing('repair_job_photos', ['id' => $photo->id]);
        Storage::disk('public')->assertMissing($photo->path);
    }

    public function test_a_non_image_upload_is_rejected(): void
    {
        Storage::fake('public');
        $this->seed(RolePermissionSeeder::class);

        $technician = StaffRecord::factory()->create();
        $technician->assignRole('Technician');
        $job = RepairJobRecord::factory()->create();

        $response = $this->actingAs($technician, 'staff')->post("/admin/repairs/{$job->id}/photos", [
            'photo' => UploadedFile::fake()->create('notes.pdf', 100),
        ]);

        $response->assertSessionHasErrors('photo');
        $this->assertDatabaseCount('repair_job_photos', 0);
    }
}
