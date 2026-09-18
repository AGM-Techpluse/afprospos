<?php

declare(strict_types=1);

namespace Tests\Feature\Customer\Profile;

use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomerCanUploadAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_upload_a_profile_photo(): void
    {
        Storage::fake('public');
        $customer = CustomerRecord::factory()->create();

        $response = $this->actingAs($customer, 'customer')->post('/customer/profile/avatar', [
            'avatar' => UploadedFile::fake()->create('photo.jpg', 500, 'image/jpeg'),
        ]);

        $response->assertRedirect(route('customer.profile.show'));
        $customer->refresh();
        $this->assertNotNull($customer->avatar_path);
        Storage::disk('public')->assertExists($customer->avatar_path);
    }

    public function test_uploading_a_new_photo_deletes_the_previous_one(): void
    {
        Storage::fake('public');
        $customer = CustomerRecord::factory()->create();

        $this->actingAs($customer, 'customer')->post('/customer/profile/avatar', [
            'avatar' => UploadedFile::fake()->create('first.jpg', 500, 'image/jpeg'),
        ]);
        $firstPath = $customer->refresh()->avatar_path;

        $this->actingAs($customer, 'customer')->post('/customer/profile/avatar', [
            'avatar' => UploadedFile::fake()->create('second.jpg', 500, 'image/jpeg'),
        ]);
        $secondPath = $customer->refresh()->avatar_path;

        $this->assertNotSame($firstPath, $secondPath);
        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($secondPath);
    }

    public function test_customer_can_remove_their_profile_photo(): void
    {
        Storage::fake('public');
        $customer = CustomerRecord::factory()->create();

        $this->actingAs($customer, 'customer')->post('/customer/profile/avatar', [
            'avatar' => UploadedFile::fake()->create('photo.jpg', 500, 'image/jpeg'),
        ]);
        $path = $customer->refresh()->avatar_path;

        $response = $this->actingAs($customer, 'customer')->delete('/customer/profile/avatar');

        $response->assertRedirect(route('customer.profile.show'));
        $this->assertNull($customer->refresh()->avatar_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_a_non_image_file_is_rejected(): void
    {
        Storage::fake('public');
        $customer = CustomerRecord::factory()->create();

        $response = $this->actingAs($customer, 'customer')->post('/customer/profile/avatar', [
            'avatar' => UploadedFile::fake()->create('document.pdf', 100),
        ]);

        $response->assertSessionHasErrors('avatar');
        $this->assertNull($customer->refresh()->avatar_path);
    }
}
