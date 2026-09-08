<?php

declare(strict_types=1);

use Domain\Shared\Domain\ValueObjects\Money;

it('adds two money values in minor units', function (): void {
    $total = (new Money(1_000_00))->add(new Money(50_00));

    expect($total->minor)->toBe(1_050_00);
});

it('refuses to subtract more than is present', function (): void {
    (new Money(100))->subtract(new Money(200));
})->throws(InvalidArgumentException::class);

it('refuses a negative amount', function (): void {
    new Money(-1);
})->throws(InvalidArgumentException::class);

it('reports zero correctly', function (): void {
    expect(Money::zero()->isZero())->toBeTrue();
    expect((new Money(1))->isZero())->toBeFalse();
});
