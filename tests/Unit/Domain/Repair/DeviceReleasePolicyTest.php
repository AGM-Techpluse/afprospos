<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Repair;

use Domain\Repair\Domain\Policies\DeviceReleasePolicy;
use Tests\TestCase;

class DeviceReleasePolicyTest extends TestCase
{
    public function test_fully_paid_may_release_without_an_override(): void
    {
        $this->assertTrue((new DeviceReleasePolicy)->canRelease('fully_paid', false));
    }

    public function test_unpaid_may_not_release_without_an_override(): void
    {
        $this->assertFalse((new DeviceReleasePolicy)->canRelease('unpaid', false));
    }

    public function test_partially_paid_may_not_release_without_an_override(): void
    {
        $this->assertFalse((new DeviceReleasePolicy)->canRelease('partially_paid', false));
    }

    public function test_unpaid_may_release_with_an_authorized_override(): void
    {
        $this->assertTrue((new DeviceReleasePolicy)->canRelease('unpaid', true));
    }
}
