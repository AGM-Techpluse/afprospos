<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Payments;

use Domain\Payments\Domain\Policies\PaymentTransitionPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PaymentTransitionPolicyTest extends TestCase
{
    #[DataProvider('legalTransitions')]
    public function test_legal_transitions_are_allowed(string $from, string $to): void
    {
        $this->assertTrue((new PaymentTransitionPolicy)->canTransition($from, $to));
    }

    #[DataProvider('illegalTransitions')]
    public function test_illegal_transitions_are_rejected(string $from, string $to): void
    {
        $this->assertFalse((new PaymentTransitionPolicy)->canTransition($from, $to));
    }

    public static function legalTransitions(): array
    {
        return [
            'pending -> confirmed' => ['pending', 'confirmed'],
            'pending -> payment_pending_confirmation' => ['pending', 'payment_pending_confirmation'],
            'pending -> exception' => ['pending', 'exception'],
            'payment_pending_confirmation -> confirmed' => ['payment_pending_confirmation', 'confirmed'],
            'payment_pending_confirmation -> exception' => ['payment_pending_confirmation', 'exception'],
            'payment_pending_confirmation -> disputed' => ['payment_pending_confirmation', 'disputed'],
            'confirmed -> disputed' => ['confirmed', 'disputed'],
            'confirmed -> refunded' => ['confirmed', 'refunded'],
            'disputed -> confirmed' => ['disputed', 'confirmed'],
            'disputed -> refunded' => ['disputed', 'refunded'],
            'disputed -> exception' => ['disputed', 'exception'],
        ];
    }

    public static function illegalTransitions(): array
    {
        return [
            'confirmed -> confirmed (duplicate, handled by entity not policy)' => ['confirmed', 'confirmed'],
            'confirmed -> pending' => ['confirmed', 'pending'],
            'refunded -> confirmed' => ['refunded', 'confirmed'],
            'refunded -> anything' => ['refunded', 'disputed'],
            'exception -> confirmed' => ['exception', 'confirmed'],
            'exception -> anything' => ['exception', 'refunded'],
            'pending -> disputed' => ['pending', 'disputed'],
            'pending -> refunded' => ['pending', 'refunded'],
        ];
    }
}
