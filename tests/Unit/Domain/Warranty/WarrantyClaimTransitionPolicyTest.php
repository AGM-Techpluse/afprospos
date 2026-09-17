<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Warranty;

use Domain\Warranty\Domain\Policies\WarrantyClaimTransitionPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WarrantyClaimTransitionPolicyTest extends TestCase
{
    #[DataProvider('legalTransitions')]
    public function test_legal_transitions_are_allowed(string $from, string $to): void
    {
        $this->assertTrue((new WarrantyClaimTransitionPolicy)->canTransition($from, $to));
    }

    #[DataProvider('illegalTransitions')]
    public function test_illegal_transitions_are_rejected(string $from, string $to): void
    {
        $this->assertFalse((new WarrantyClaimTransitionPolicy)->canTransition($from, $to));
    }

    public static function legalTransitions(): array
    {
        return [
            'submitted -> under_assessment' => ['submitted', 'under_assessment'],
            'submitted -> eligible' => ['submitted', 'eligible'],
            'submitted -> not_eligible' => ['submitted', 'not_eligible'],
            'under_assessment -> eligible' => ['under_assessment', 'eligible'],
            'under_assessment -> not_eligible' => ['under_assessment', 'not_eligible'],
            'eligible -> remedy_selected' => ['eligible', 'remedy_selected'],
            'remedy_selected -> resolved' => ['remedy_selected', 'resolved'],
        ];
    }

    public static function illegalTransitions(): array
    {
        return [
            'submitted -> remedy_selected (skips assessment)' => ['submitted', 'remedy_selected'],
            'submitted -> resolved (skips everything)' => ['submitted', 'resolved'],
            'not_eligible -> remedy_selected' => ['not_eligible', 'remedy_selected'],
            'not_eligible -> anything' => ['not_eligible', 'eligible'],
            'eligible -> resolved (skips remedy selection)' => ['eligible', 'resolved'],
            'resolved -> anything' => ['resolved', 'submitted'],
        ];
    }
}
