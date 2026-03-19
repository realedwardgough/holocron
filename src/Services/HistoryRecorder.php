<?php

declare(strict_types=1);

namespace Egough\Holocron\Services;

use Closure;
use DateTimeInterface;
use Egough\Holocron\Models\HolocronEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class HistoryRecorder
{
    public function record(
        string $event,
        Model $subject,
        ?Model $actor = null,
        ?string $message = null,
        ?string $category = null,
        array $meta = [],
        array $changes = [],
        DateTimeInterface|string|null $recordedAt = null,
    ): HolocronEntry {
        if ($event === '') {
            throw new InvalidArgumentException('A Holocron history entry requires an event key.');
        }

        $entry = new HolocronEntry();

        $entry->forceFill([
            'event' => $event,
            'category' => $category,
            'message' => $message,
            'changes' => $changes,
            'meta' => $meta,
            'recorded_at' => $recordedAt ? Carbon::parse($recordedAt) : now(),
        ]);

        $entry->subject()->associate($subject);

        if ($actor !== null) {
            $entry->actor()->associate($actor);
        }

        $entry->save();

        return $entry->refresh();
    }

    public function resolveActor(): ?Model
    {
        $resolver = config('holocron.actor_resolver');

        if ($resolver instanceof Closure) {
            $actor = $resolver();

            return $actor instanceof Model ? $actor : null;
        }

        return null;
    }
}
