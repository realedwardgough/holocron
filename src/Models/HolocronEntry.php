<?php

declare(strict_types=1);

namespace Egough\Holocron\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class HolocronEntry extends Model
{
    protected $guarded = [];

    protected $table = 'holocron_entries';

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'meta' => 'array',
            'recorded_at' => 'datetime',
        ];
    }

    public function getTable(): string
    {
        return config('holocron.table_name', parent::getTable());
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function actor(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForSubject(Builder $query, Model $subject): Builder
    {
        return $query
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey());
    }

    public function scopeCausedBy(Builder $query, Model $actor): Builder
    {
        return $query
            ->where('actor_type', $actor->getMorphClass())
            ->where('actor_id', $actor->getKey());
    }

    public function scopeEvent(Builder $query, string $event): Builder
    {
        return $query->where('event', $event);
    }

    public function scopeCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function isSystemEvent(): bool
    {
        return $this->actor === null;
    }

    public function hasRecordedChanges(): bool
    {
        return ! empty($this->changes);
    }

    public function formattedMessage(): string
    {
        return $this->message ?: str($this->event)->replace('_', ' ')->headline()->toString();
    }

    public function toTimelineItem(): array
    {
        return [
            'id' => $this->getKey(),
            'event' => $this->event,
            'category' => $this->category,
            'message' => $this->formattedMessage(),
            'changes' => $this->changes ?? [],
            'meta' => $this->meta ?? [],
            'recorded_at' => $this->recorded_at,
            'actor' => $this->actor,
        ];
    }
}
