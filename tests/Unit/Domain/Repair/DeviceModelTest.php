<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Repair;

use Domain\Repair\Domain\Entities\DeviceModel;
use Domain\Repair\Domain\ValueObjects\DeviceBrandId;
use PHPUnit\Framework\TestCase;

class DeviceModelTest extends TestCase
{
    public function test_create_starts_with_no_id_and_the_given_attributes(): void
    {
        $model = DeviceModel::create(new DeviceBrandId(1), 'iPhone 15 Pro', 2);

        $this->assertNull($model->id());
        $this->assertTrue($model->deviceBrandId()->equals(new DeviceBrandId(1)));
        $this->assertSame('iPhone 15 Pro', $model->name());
        $this->assertSame(2, $model->sortOrder());
    }

    public function test_rename_and_reorder_mutate_in_place(): void
    {
        $model = DeviceModel::create(new DeviceBrandId(1), 'iPhone 15 Pro', 0);

        $model->rename('iPhone 15 Pro Max');
        $model->reorder(3);

        $this->assertSame('iPhone 15 Pro Max', $model->name());
        $this->assertSame(3, $model->sortOrder());
    }

    public function test_reassign_brand_mutates_in_place(): void
    {
        $model = DeviceModel::create(new DeviceBrandId(1), 'iPhone 15 Pro', 0);

        $model->reassignBrand(new DeviceBrandId(2));

        $this->assertTrue($model->deviceBrandId()->equals(new DeviceBrandId(2)));
    }
}
