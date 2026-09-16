<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $repair_job_id
 * @property string $path
 * @property string|null $caption
 * @property int $uploaded_by_staff_id
 * @property Carbon $created_at
 */
final class RepairJobPhotoRecord extends Model
{
    const UPDATED_AT = null;

    protected $table = 'repair_job_photos';

    protected $fillable = ['repair_job_id', 'path', 'caption', 'uploaded_by_staff_id'];
}
