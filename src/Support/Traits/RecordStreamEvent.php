<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\Traits;

use Generator;
use Illuminate\Support\Collection;
use Chronhub\Foundation\Reporter\ReportEvent;

trait RecordStreamEvent
{
    protected Collection $recordedStreams;

    public function __construct(protected ReportEvent $reporter)
    {
        $this->recordedStreams = new Collection();
    }

    protected function recordStreams(iterable $events): void
    {
        if ($events instanceof Generator) {
            $events = iterator_to_array($events);
        }

        $this->recordedStreams->push($events);
    }

    protected function publishEvents(iterable $events): void
    {
        if ($events instanceof Generator) {
            $events = iterator_to_array($events);
        }

        if (empty($events)) {
            return;
        }

        foreach ($events as $event) {
            $this->reporter->publish($event);
        }
    }

    protected function pullRecords(): array
    {
        $recordedStreams = $this->recordedStreams->flatten();

        $this->clearRecords();

        return $recordedStreams->toArray();
    }

    protected function clearRecords(): void
    {
        $this->recordedStreams = new Collection();
    }
}
