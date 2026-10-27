<?php

declare(strict_types=1);

namespace Domain\Finance\Domain\Repositories;

use Domain\Finance\Domain\Entities\Expense;
use Domain\Finance\Domain\ValueObjects\ExpenseId;

interface ExpenseRepository
{
    public function get(ExpenseId $id): Expense;

    /** Create-or-update, matching the established save() shape elsewhere (e.g. CollectionCaseRepository). */
    public function save(Expense $expense): ExpenseId;
}
