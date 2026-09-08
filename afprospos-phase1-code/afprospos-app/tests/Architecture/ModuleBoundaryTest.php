<?php

declare(strict_types=1);

/**
 * CPNC §4.5-equivalent architectural gate. Kept intentionally small for
 * Phase 1 — this is the seed of the architectural test suite, not its
 * final form; extend it as each new module lands rather than replacing
 * it wholesale.
 */
arch('Domain layers never depend on Eloquent')
    ->expect('Domain\*\Domain')
    ->not->toUse('Illuminate\Database\Eloquent\Model');

arch('Domain layers never depend on Laravel facades')
    ->expect('Domain\*\Domain')
    ->not->toUse([
        'Illuminate\Support\Facades\DB',
        'Illuminate\Support\Facades\Auth',
        'Illuminate\Support\Facades\Session',
        'Illuminate\Support\Facades\Hash',
    ]);

arch('Domain layers never depend on Illuminate\Http')
    ->expect('Domain\*\Domain')
    ->not->toUse('Illuminate\Http\Request');

arch('Eloquent Records live only in Infrastructure\Persistence\Eloquent')
    ->expect('Domain\*\Infrastructure\Persistence\Eloquent')
    ->toExtend('Illuminate\Database\Eloquent\Model');

arch('Commands are readonly DTOs')
    ->expect('Domain\*\Application\Commands')
    ->toBeReadonly();
