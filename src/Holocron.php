<?php

declare(strict_types=1);

namespace Egough\Holocron;

use Egough\Holocron\Services\HistoryRecorder;

class Holocron
{
    public function __construct(
        protected HistoryRecorder $recorder,
    ) {
    }

    public function record(string $event): PendingHistoryEntry
    {
        return PendingHistoryEntry::forEvent($event, $this->recorder);
    }
}
