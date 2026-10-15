<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Queries;

use Domain\Shared\Domain\Contracts\SiteSettings;

/**
 * What happens to a repair when its quality check fails. The admin chooses:
 * `diagnosis` (send it back to diagnose the fault again) or `cancel` (close the
 * repair). Defaults to `diagnosis`, so a failed check never silently ends a job.
 */
final class QcFailPolicyQuery
{
    public const KEY = 'repairs.qc_fail_action';

    public const ACTIONS = ['diagnosis', 'cancel'];

    public function __construct(private readonly SiteSettings $settings) {}

    public function current(): string
    {
        $action = $this->settings->get(self::KEY, 'diagnosis');

        return in_array($action, self::ACTIONS, true) ? $action : 'diagnosis';
    }
}
