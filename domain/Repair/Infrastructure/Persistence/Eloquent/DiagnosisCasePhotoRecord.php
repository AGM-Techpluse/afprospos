<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $diagnosis_case_id
 * @property string $path
 * @property string|null $caption
 * @property int $uploaded_by_staff_id
 * @property Carbon $created_at
 */
final class DiagnosisCasePhotoRecord extends Model
{
    const UPDATED_AT = null;

    protected $table = 'diagnosis_case_photos';

    protected $fillable = ['diagnosis_case_id', 'path', 'caption', 'uploaded_by_staff_id'];
}
