<?php

declare(strict_types=1);

namespace Domain\Warranty\Domain\Repositories;

use Domain\Warranty\Domain\Entities\ReturnRequest;
use Domain\Warranty\Domain\ValueObjects\ReturnRequestId;

interface ReturnRequestRepository
{
    public function get(ReturnRequestId $id): ReturnRequest;

    /** Locks the row with SELECT ... FOR UPDATE — the serialization point for a duplicate assess/approve/refund race. */
    public function lockForUpdate(ReturnRequestId $id): ReturnRequest;

    public function save(ReturnRequest $returnRequest): ReturnRequestId;
}
