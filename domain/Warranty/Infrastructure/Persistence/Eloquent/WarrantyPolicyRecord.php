<?php

declare(strict_types=1);

namespace Domain\Warranty\Infrastructure\Persistence\Eloquent;

use Database\Factories\WarrantyPolicyRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property int $coverage_duration_days
 * @property string $coverage_start_point
 * @property array<int, string> $covered_scope
 * @property array<int, string> $exclusions
 * @property array<int, string> $available_remedies
 * @property string $coverage_extent
 * @property Carbon $created_at
 */
final class WarrantyPolicyRecord extends Model
{
    use HasFactory;

    protected $table = 'warranty_policies';

    protected $fillable = [
        'name',
        'coverage_duration_days',
        'coverage_start_point',
        'covered_scope',
        'exclusions',
        'available_remedies',
        'coverage_extent',
    ];

    protected function casts(): array
    {
        return [
            'covered_scope' => 'array',
            'exclusions' => 'array',
            'available_remedies' => 'array',
        ];
    }

    protected static function newFactory(): WarrantyPolicyRecordFactory
    {
        return WarrantyPolicyRecordFactory::new();
    }
}
