<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Collection;

use Domain\Collection\Domain\Policies\CollectionTransitionPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CollectionTransitionPolicyTest extends TestCase
{
    #[DataProvider('legalTransitions')]
    public function test_legal_transitions_are_allowed(string $from, string $to): void
    {
        $this->assertTrue((new CollectionTransitionPolicy)->canTransition($from, $to));
    }

    #[DataProvider('illegalTransitions')]
    public function test_illegal_transitions_are_rejected(string $from, string $to): void
    {
        $this->assertFalse((new CollectionTransitionPolicy)->canTransition($from, $to));
    }

    public static function legalTransitions(): array
    {
        return [
            'pending -> overdue' => ['pending', 'overdue'],
            'pending -> resolved' => ['pending', 'resolved'],
            'overdue -> abandoned' => ['overdue', 'abandoned'],
            'overdue -> resolved' => ['overdue', 'resolved'],
            'abandoned -> resolved' => ['abandoned', 'resolved'],
        ];
    }

    public static function illegalTransitions(): array
    {
        return [
            'pending -> abandoned (skips overdue)' => ['pending', 'abandoned'],
            'resolved -> anything' => ['resolved', 'pending'],
            'abandoned -> pending (backwards)' => ['abandoned', 'pending'],
            'abandoned -> overdue (backwards)' => ['abandoned', 'overdue'],
        ];
    }
}
