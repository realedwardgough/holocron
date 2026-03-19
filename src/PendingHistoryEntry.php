<?php

declare(strict_types=1);

namespace Egough\Holocron;

use DateTimeInterface;
use Egough\Holocron\Models\HolocronEntry;
use Egough\Holocron\Services\HistoryRecorder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class PendingHistoryEntry
{
    protected ?Model $subject = null;

    protected ?Model $actor = null;

    protected ?string $message = null;

    protected ?string $category = null;

    protected array $meta = [];

    protected array $changes = [];

    protected ?Carbon $recordedAt = null;

    protected function __construct(
        protected string $event,
        protected HistoryRecorder $recorder,
    ) {}

    public static function forEvent(string $event, HistoryRecorder $recorder): self
    {
        return new self($event, $recorder);
    }

    public function on(Model $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function by(?Model $actor): self
    {
        $this->actor = $actor;

        return $this;
    }

    public function message(?string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function category(?string $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function with(array $meta): self
    {
        return $this->withMeta($meta);
    }

    public function withMeta(array $meta): self
    {
        $this->meta = array_replace_recursive($this->meta, $meta);

        return $this;
    }

    public function withChanges(array $changes): self
    {
        $this->changes = $changes;

        return $this;
    }

    public function at(DateTimeInterface|string|null $recordedAt): self
    {
        $this->recordedAt = $recordedAt === null ? null : Carbon::parse($recordedAt);

        return $this;
    }

    public function save(): HolocronEntry
    {
        if ($this->subject === null) {
            throw new InvalidArgumentException('A Holocron history entry must have a subject.');
        }

        return $this->recorder->record(
            event: $this->event,
            subject: $this->subject,
            actor: $this->actor,
            message: $this->message,
            category: $this->category,
            meta: $this->meta,
            changes: $this->changes,
            recordedAt: $this->recordedAt,
        );
    }
}
