<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Warranty;

use Domain\Warranty\Domain\Policies\TradeInAssessmentTransitionPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TradeInAssessmentTransitionPolicyTest extends TestCase
{
    #[DataProvider('legalTransitions')]
    public function test_legal_transitions_are_allowed(string $from, string $to): void
    {
        $this->assertTrue((new TradeInAssessmentTransitionPolicy)->canTransition($from, $to));
    }

    #[DataProvider('illegalTransitions')]
    public function test_illegal_transitions_are_rejected(string $from, string $to): void
    {
        $this->assertFalse((new TradeInAssessmentTransitionPolicy)->canTransition($from, $to));
    }

    public static function legalTransitions(): array
    {
        return [
            'submitted -> assessed' => ['submitted', 'assessed'],
            'assessed -> approved' => ['assessed', 'approved'],
            'assessed -> rejected' => ['assessed', 'rejected'],
            'approved -> applied' => ['approved', 'applied'],
        ];
    }

    public static function illegalTransitions(): array
    {
        return [
            'submitted -> approved (skips assessment)' => ['submitted', 'approved'],
            'submitted -> applied (skips everything)' => ['submitted', 'applied'],
            'rejected -> approved' => ['rejected', 'approved'],
            'applied -> anything' => ['applied', 'submitted'],
        ];
    }
}
