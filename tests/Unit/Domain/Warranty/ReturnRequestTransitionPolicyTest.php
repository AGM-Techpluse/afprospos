<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Warranty;

use Domain\Warranty\Domain\Policies\ReturnRequestTransitionPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReturnRequestTransitionPolicyTest extends TestCase
{
    #[DataProvider('legalTransitions')]
    public function test_legal_transitions_are_allowed(string $from, string $to): void
    {
        $this->assertTrue((new ReturnRequestTransitionPolicy)->canTransition($from, $to));
    }

    #[DataProvider('illegalTransitions')]
    public function test_illegal_transitions_are_rejected(string $from, string $to): void
    {
        $this->assertFalse((new ReturnRequestTransitionPolicy)->canTransition($from, $to));
    }

    public static function legalTransitions(): array
    {
        return [
            'requested -> under_assessment' => ['requested', 'under_assessment'],
            'under_assessment -> approved' => ['under_assessment', 'approved'],
            'under_assessment -> denied' => ['under_assessment', 'denied'],
            'approved -> resolved' => ['approved', 'resolved'],
            'denied -> approved (WAR-BR-12 override)' => ['denied', 'approved'],
        ];
    }

    public static function illegalTransitions(): array
    {
        return [
            'requested -> approved (skips assessment)' => ['requested', 'approved'],
            'requested -> resolved (skips everything)' => ['requested', 'resolved'],
            'denied -> resolved (must go through approved)' => ['denied', 'resolved'],
            'denied -> denied' => ['denied', 'denied'],
            'resolved -> anything' => ['resolved', 'requested'],
        ];
    }
}
