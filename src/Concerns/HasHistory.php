<?php

declare(strict_types=1);

namespace Egough\Holocron\Concerns;

use Egough\Holocron\Models\HolocronEntry;
use Egough\Holocron\Services\HistoryRecorder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;

trait HasHistory
{
    protected array $__holocronPendingChanges = [];

    public static function bootHasHistory(): void
    {
        static::creating(function (Model $model): void {
            $model->syncHolocronPendingChanges([]);
        });

        static::updating(function (Model $model): void {
            $model->syncHolocronPendingChanges($model->collectHolocronChanges());
        });

        static::created(function (Model $model): void {
            $model->recordAutomaticHistory('created');
        });

        static::updated(function (Model $model): void {
            $model->recordAutomaticHistory('updated', $model->getHolocronPendingChanges());
            $model->syncHolocronPendingChanges([]);
        });

        static::deleted(function (Model $model): void {
            $model->recordAutomaticHistory('deleted');
        });

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            static::restored(function (Model $model): void {
                $model->recordAutomaticHistory('restored');
            });
        }
    }

    public function history(): MorphMany
    {
        return $this->morphMany(HolocronEntry::class, 'subject');
    }

    public function latestHistory(): MorphMany
    {
        return $this->history()->latest('recorded_at');
    }

    public function recordHistory(
        string $event,
        ?string $message = null,
        ?Model $actor = null,
        array $meta = [],
        array $changes = [],
        ?string $category = null,
    ): HolocronEntry {
        /** @var HistoryRecorder $recorder */
        $recorder = app(HistoryRecorder::class);

        return $recorder->record(
            event: $event,
            subject: $this,
            actor: $actor,
            message: $message,
            category: $category,
            meta: $meta,
            changes: $changes,
        );
    }

    public function holocronTimeline(): array
    {
        return $this->latestHistory()
            ->get()
            ->map(fn (HolocronEntry $entry): array => $entry->toTimelineItem())
            ->all();
    }

    public function holocronTrackedAttributes(): array
    {
        if (property_exists($this, 'holocronTrack') && is_array($this->holocronTrack)) {
            return $this->holocronTrack;
        }

        return array_keys($this->getAttributes());
    }

    public function holocronExcludedAttributes(): array
    {
        $configured = config('holocron.exclude', []);
        $modelSpecific = property_exists($this, 'holocronExclude') && is_array($this->holocronExclude)
            ? $this->holocronExclude
            : [];

        return array_values(array_unique([...$configured, ...$modelSpecific]));
    }

    public function holocronEvents(): array
    {
        $configured = array_keys(array_filter(config('holocron.auto_record', [])));

        if (property_exists($this, 'holocronEvents') && is_array($this->holocronEvents)) {
            return $this->holocronEvents;
        }

        return $configured;
    }

    public function collectHolocronChanges(): array
    {
        $tracked = $this->holocronTrackedAttributes();
        $excluded = $this->holocronExcludedAttributes();
        $dirty = Arr::except($this->getDirty(), $excluded);

        if ($tracked !== []) {
            $dirty = Arr::only($dirty, $tracked);
        }

        $changes = [];

        foreach ($dirty as $attribute => $newValue) {
            $oldValue = $this->getOriginal($attribute);

            if ($oldValue === $newValue) {
                continue;
            }

            $changes[$attribute] = [
                'old' => $oldValue,
                'new' => $newValue,
            ];
        }

        return $changes;
    }

    protected function syncHolocronPendingChanges(array $changes): void
    {
        $this->__holocronPendingChanges = $changes;
    }

    protected function getHolocronPendingChanges(): array
    {
        return $this->__holocronPendingChanges;
    }

    protected function recordAutomaticHistory(string $event, array $changes = []): void
    {
        if (! in_array($event, $this->holocronEvents(), true)) {
            return;
        }

        if ($event === 'updated' && $changes === []) {
            return;
        }

        /** @var HistoryRecorder $recorder */
        $recorder = app(HistoryRecorder::class);

        $recorder->record(
            event: $event,
            subject: $this,
            actor: $recorder->resolveActor(),
            message: $this->holocronAutomaticMessage($event, $changes),
            changes: $changes,
            meta: [],
        );
    }

    protected function holocronAutomaticMessage(string $event, array $changes = []): string
    {
        $label = str(class_basename($this))->headline()->toString();

        return match ($event) {
            'created' => "{$label} created",
            'updated' => "{$label} updated",
            'deleted' => "{$label} deleted",
            'restored' => "{$label} restored",
            default => "{$label} {$event}",
        };
    }
}
